<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\CustomField;
use App\Models\MemberDocument;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

    public function create(array $data, Branch $branch): User
    {
        return DB::transaction(function () use ($data, $branch): User {
            $memberNumber = $this->reserveNextMemberNumber($branch);

            $member = User::create([
                'name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
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
                'is_verified' => true,
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

            $this->syncDocuments($member, $data, false);
            $this->branchService->ensureMemberAccounts($member);

            return $member->load(['detail', 'documents', 'savingsAccounts']);
        });
    }

    public function update(User $member, array $data, Branch $branch): User
    {
        return DB::transaction(function () use ($member, $data, $branch): User {
            $member->update([
                'name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'branch_id' => (string) $branch->id,
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
                'designation' => 'Member',
            ]);

            $detail = $member->detail ?: new UserDetail([
                'user_id' => $member->id,
                'member_no' => $member->member_no ?: $this->reserveNextMemberNumber($branch),
            ]);

            $detail->fill([
                'branch_id' => $branch->id,
                'mobile' => $data['mobile'],
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'member_no' => $detail->member_no ?: $member->member_no,
                'occupation' => $data['occupation'] ?? null,
                'gender' => $data['gender'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'address' => $data['address'] ?? null,
                'custom_fields' => $this->prepareCustomFieldValues(
                    $data['custom_fields'] ?? [],
                    $detail->custom_fields ?? []
                ),
            ]);
            $detail->save();

            if (! $member->member_no && $detail->member_no) {
                $member->update(['member_no' => $detail->member_no]);
            }

            $this->syncDocuments($member, $data, true);
            $this->branchService->ensureMemberAccounts($member);

            return $member->fresh()->load(['detail', 'documents', 'savingsAccounts']);
        });
    }

    public function delete(User $member): void
    {
        DB::transaction(function () use ($member): void {
            $member->update([
                'status' => 0,
            ]);

            $member->delete();
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

    protected function formatMemberNumber(Branch $branch, int $number): string
    {
        $prefix = $branch->prefix ?: ('BRANCH_' . $branch->id);

        return $prefix . '_' . str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }
}
