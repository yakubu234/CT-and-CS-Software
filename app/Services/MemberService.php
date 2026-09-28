<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\CustomField;
use App\Models\MemberDocument;
use App\Models\MemberBranchMembership;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\EmailTemplate;
use App\Models\EmailSmtpAccount;
use App\Services\Email\EmailAutomationService;
use App\Support\MemberNumber;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MemberService
{
    public function __construct(
        protected BranchService $branchService,
    ) {
    }

    public function nextMemberNumber(Branch $branch): string
    {
        $nextNumber = ((int) $branch->number_count) + 1;

        return $this->formatMemberNumber($branch, $nextNumber);
    }

    public function memberNumberPreview(?User $member, Branch $branch): string
    {
        $membershipNumber = $member?->exists && Schema::hasTable('member_branch_memberships')
            ? $member->branchMemberships()->where('branch_id', $branch->id)->value('member_number')
            : null;

        if ($membershipNumber) {
            return $membershipNumber;
        }

        $existingNumber = $this->normalizeExistingMemberNumber(
            $member?->detail?->member_no ?: $member?->member_no,
            $branch
        );

        return $existingNumber ?: $this->nextMemberNumber($branch);
    }

    public function create(array $data, Branch $branch): User
    {
        return DB::transaction(function () use ($data, $branch): User {
            $normalizedEmail = Str::lower(trim($data['email']));
            $normalizedMobile = preg_replace('/\D+/', '', (string) $data['mobile']);
            $existing = User::query()
                ->where('user_type', 'customer')
                ->where('branch_account', false)
                ->where(function ($query) use ($normalizedEmail, $normalizedMobile): void {
                    $query->whereRaw('LOWER(email) = ?', [$normalizedEmail]);
                    if ($normalizedMobile !== '') {
                        $query->orWhereHas('detail', function ($detail) use ($normalizedMobile): void {
                            $detail->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(mobile, ' ', ''), '+', ''), '-', ''), '(', '') LIKE ?", ['%' . ltrim($normalizedMobile, '0')]);
                        });
                    }
                })
                ->first();

            if ($existing) {
                if (! ($data['link_existing'] ?? false)) {
                    throw ValidationException::withMessages([
                        'email' => 'A member with this email or phone already exists. Confirm that this is the same person to add the current society membership.',
                    ]);
                }

                if ($existing->branchMemberships()->where('branch_id', $branch->id)->exists()) {
                    throw ValidationException::withMessages([
                        'email' => 'This person is already a member of the selected society.',
                    ]);
                }

                $membership = MemberBranchMembership::create([
                    'user_id' => $existing->id,
                    'branch_id' => $branch->id,
                    'member_number' => $this->reserveNextMemberNumber($branch),
                    'is_primary' => false,
                    'status' => true,
                    'joined_at' => now(),
                ]);
                $this->branchService->ensureMembershipAccounts($existing, $membership);
                $existing->setRelation('activeMembership', $membership);
                $existing->setRelation('branch', $branch);

                return $existing;
            }

            $memberNumber = $this->reserveNextMemberNumber($branch);
            $verificationRequired = EmailTemplate::query()->where('category', 'account_verification')->where('status', true)->exists()
                && EmailSmtpAccount::query()->where('is_active', true)->exists();

            $member = User::create([
                'name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $normalizedEmail,
                'password' => Hash::make(Str::password(24)),
                'user_type' => 'customer',
                'role_id' => null,
                'branch_id' => (string) $branch->id,
                'status' => 1,
                'profile_picture' => $this->storeOptionalFile($data['picture'] ?? null, 'members/pictures'),
                'society_role' => null,
                'society_exco' => false,
                'former_exco' => false,
                'user_level' => null,
                'branch_account' => false,
                'is_verified' => ! $verificationRequired,
                'email_verification_required_at' => $verificationRequired ? now() : null,
                'signature' => $this->storeOptionalFile($data['signature'] ?? null, 'members/signatures'),
                'member_no' => $memberNumber,
                'designation' => 'Member',
                'former_designation' => null,
            ]);

            $detail = $member->detail()->create([
                'branch_id' => $branch->id,
                'user_id' => $member->id,
                'mobile' => $data['mobile'],
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'business_name' => null,
                'member_no' => $memberNumber,
                'occupation' => $data['occupation'] ?? null,
                'gender' => $data['gender'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'address' => $data['address'] ?? null,
                'custom_fields' => $this->prepareCustomFieldValues($data['custom_fields'] ?? [], []),
            ]);

            $membership = MemberBranchMembership::create([
                'user_id' => $member->id,
                'branch_id' => $branch->id,
                'member_number' => $memberNumber,
                'is_primary' => true,
                'status' => true,
                'joined_at' => now(),
            ]);

            $this->syncDocuments($member, $data, false);
            $this->branchService->ensureMembershipAccounts($member, $membership);

            DB::afterCommit(function () use ($member, $verificationRequired): void {
                try {
                    app(EmailAutomationService::class)->memberRegistered($member->fresh(['detail', 'branch']));
                } catch (\Throwable $exception) {
                    Log::error('Member registration email could not be prepared', ['member_id' => $member->id, 'error' => $exception->getMessage()]);
                }

                if ($verificationRequired) {
                    try {
                        app(EmailAutomationService::class)->accountVerificationRequested($member->fresh(['detail', 'branch']));
                    } catch (\Throwable $exception) {
                        Log::error('Member verification email could not be prepared', ['member_id' => $member->id, 'error' => $exception->getMessage()]);
                    }
                }
            });

            return $member->load(['detail', 'documents', 'savingsAccounts']);
        });
    }

    public function update(User $member, array $data, Branch $branch): User
    {
        return DB::transaction(function () use ($member, $data, $branch): User {
            $membership = $member->branchMemberships()->where('branch_id', $branch->id)->firstOrFail();
            $memberPayload = [
                'name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'profile_picture' => $this->storeOptionalFile(
                    $data['picture'] ?? null,
                    'members/pictures',
                    $member->profile_picture
                ),
                'signature' => $this->storeOptionalFile(
                    $data['signature'] ?? null,
                    'members/signatures',
                    $member->signature
                ),
                'designation' => $member->designation ?: 'Member',
            ];
            $member->update($memberPayload);

            $detail = $member->detail ?: new UserDetail([
                'user_id' => $member->id,
                'member_no' => $member->member_no,
            ]);

            $detailPayload = [
                'mobile' => $data['mobile'],
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'occupation' => $data['occupation'] ?? null,
                'gender' => $data['gender'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'address' => $data['address'] ?? null,
                'custom_fields' => $this->prepareCustomFieldValues(
                    $data['custom_fields'] ?? [],
                    $detail->custom_fields ?? []
                ),
            ];
            if ($membership->is_primary) {
                $detailPayload['branch_id'] = $branch->id;
                $detailPayload['member_no'] = $detail->member_no ?: $membership->member_number;
            }
            $detail->fill($detailPayload);
            $detail->save();

            $this->syncDocuments($member, $data, true);
            $this->branchService->ensureMembershipAccounts($member, $membership);

            $member->setRelation('activeMembership', $membership);
            $member->setRelation('branch', $branch);

            return $member->fresh()->load(['detail', 'documents', 'savingsAccounts']);
        });
    }

    public function archive(User $member, Branch $branch): void
    {
        DB::transaction(function () use ($member, $branch): void {
            $archivedAt = now();
            $membership = $member->branchMemberships()->where('branch_id', $branch->id)->firstOrFail();

            $member->savingsAccounts()
                ->where('member_branch_membership_id', $membership->id)
                ->where('is_branch_acount', false)
                ->where('status', 1)
                ->update([
                    'status' => 0,
                    'disabled_at' => $archivedAt,
                    'archived_with_member_at' => $archivedAt,
                    'updated_at' => $archivedAt,
                ]);

            $membership->update(['status' => false]);

            if (! $member->branchMemberships()->where('status', true)->exists()) {
                $member->update(['status' => 0]);
                $member->delete();
            }
        });
    }

    public function restore(User $member, Branch $branch): void
    {
        DB::transaction(function () use ($member, $branch): void {
            $membership = $member->branchMemberships()->where('branch_id', $branch->id)->firstOrFail();
            abort_if($membership->status, 422, 'This society membership is already active.');

            if ($member->trashed()) {
                $member->restore();
            }
            $member->update(['status' => 1]);
            $membership->update(['status' => true]);

            $member->savingsAccounts()
                ->where('member_branch_membership_id', $membership->id)
                ->whereNotNull('archived_with_member_at')
                ->update([
                    'status' => 1,
                    'disabled_at' => null,
                    'archived_with_member_at' => null,
                    'updated_at' => now(),
                ]);
        });
    }

    public function prepareCustomFieldValues(array $values, array $existingValues = []): array
    {
        $fields = CustomField::query()
            ->forUsers()
            ->active()
            ->orderBy('order')
            ->orderBy('field_name')
            ->get()
            ->keyBy(fn (CustomField $field): string => (string) $field->id);

        $payload = [];

        foreach ($fields as $fieldId => $field) {
            if (! array_key_exists($fieldId, $values) && $field->field_type === CustomField::TYPE_FILE) {
                if (! empty($existingValues[$fieldId]['value'])) {
                    $payload[$fieldId] = $existingValues[$fieldId];
                }

                continue;
            }

            if (! array_key_exists($fieldId, $values)) {
                continue;
            }

            $value = $values[$fieldId];

            if ($field->field_type === CustomField::TYPE_FILE) {
                if ($value instanceof UploadedFile) {
                    $value = $value->store('members/custom-fields', 'public');
                } else {
                    $existing = $existingValues[$fieldId]['value'] ?? null;
                    $value = $existing ?: null;
                }
            }

            if ($value === null || $value === '') {
                continue;
            }

            $payload[$fieldId] = [
                'field_id' => (int) $field->id,
                'label' => $field->field_name,
                'type' => $field->field_type,
                'value' => $value,
            ];
        }

        return $payload;
    }

    protected function syncDocuments(User $member, array $data, bool $isUpdate): void
    {
        if ($isUpdate) {
            $existingDocuments = collect($data['existing_documents'] ?? [])
                ->keyBy(fn (array $document): string => (string) ($document['id'] ?? ''));

            foreach ($member->documents as $document) {
                $submitted = $existingDocuments->get((string) $document->id);

                if (! $submitted || empty($submitted['keep'])) {
                    $document->delete();
                    continue;
                }

                $document->update([
                    'name' => $submitted['name'] ?: $document->name,
                ]);
            }
        }

        foreach ($data['documents'] ?? [] as $documentData) {
            $name = $documentData['name'] ?? null;
            $file = $documentData['file'] ?? null;

            if (! $name || ! $file instanceof UploadedFile) {
                continue;
            }

            MemberDocument::create([
                'user_id' => $member->id,
                'name' => $name,
                'document' => $file->store('members/documents', 'public'),
            ]);
        }
    }

    protected function storeOptionalFile(?UploadedFile $file, string $path, ?string $existingPath = null): ?string
    {
        if (! $file) {
            return $existingPath;
        }

        return $file->store($path, 'public');
    }

    protected function reserveNextMemberNumber(Branch $branch): string
    {
        DB::update(
            "
                UPDATE branches
                SET number_count = LPAD(CAST(COALESCE(NULLIF(number_count, ''), '0') AS UNSIGNED) + ?, 4, '0')
                WHERE id = ?
            ",
            [1, $branch->id]
        );

        $branch->refresh();

        return $this->formatMemberNumber($branch, (int) $branch->number_count);
    }

    protected function resolveMemberNumber(User $member, UserDetail $detail, Branch $branch): string
    {
        $existingNumber = $this->normalizeExistingMemberNumber(
            $detail->member_no ?: $member->member_no,
            $branch
        );

        if ($existingNumber) {
            $this->syncBranchCounterFromMemberNumber($branch, $existingNumber);

            return $existingNumber;
        }

        return $this->reserveNextMemberNumber($branch);
    }

    protected function normalizeExistingMemberNumber(?string $memberNumber, Branch $branch): ?string
    {
        return MemberNumber::normalize($memberNumber, $branch);
    }

    protected function syncBranchCounterFromMemberNumber(Branch $branch, string $memberNumber): void
    {
        $number = MemberNumber::extractNumber($memberNumber);

        if ($number === null) {
            return;
        }

        $currentCount = (int) ($branch->number_count ?? 0);

        if ($number <= $currentCount) {
            return;
        }

        DB::table('branches')
            ->where('id', $branch->id)
            ->update([
                'number_count' => str_pad((string) $number, 4, '0', STR_PAD_LEFT),
            ]);

        $branch->refresh();
    }

    protected function formatMemberNumber(Branch $branch, int $number): string
    {
        return MemberNumber::format($number, $branch);
    }
}
