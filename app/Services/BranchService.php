<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Designation;
use App\Models\SavingsAccount;
use App\Models\SavingsProduct;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class BranchService
{
    public function ensureBranchAccount(Branch $branch): array
    {
        return DB::transaction(function () use ($branch): array {
            $branchUser = User::query()
                ->where('branch_id', (string) $branch->id)
                ->where('branch_account', true)
                ->orderBy('id')
                ->first();

            $userCreated = false;
            $accountCreated = false;
            $branchLinked = false;

            if (! $branchUser) {
                $branchUser = User::create([
                    'name' => $branch->name,
                    'last_name' => 'Branch',
                    'email' => $this->makeSystemEmail($branch->name, 'branch'),
                    'password' => Hash::make(Str::password(32)),
                    'user_type' => 'branch',
                    'role_id' => null,
                    'branch_id' => (string) $branch->id,
                    'status' => 1,
                    'profile_picture' => null,
                    'society_role' => null,
                    'society_exco' => false,
                    'date_added_as_exco' => null,
                    'former_exco' => false,
                    'date_removed_as_exco' => null,
                    'user_level' => null,
                    'branch_account' => true,
                    'is_verified' => true,
                    'signature' => null,
                    'member_no' => null,
                    'designation' => 'Branch Account',
                    'former_designation' => null,
                ]);

                $userCreated = true;
            }

            if ((int) $branch->branch_user_id !== (int) $branchUser->id) {
                $branch->update([
                    'branch_user_id' => $branchUser->id,
                ]);

                $branchLinked = true;
            }

            $hasBranchSavingsAccount = SavingsAccount::query()
                ->where('user_id', $branchUser->id)
                ->where('is_branch_acount', 1)
                ->exists();

            if (! $hasBranchSavingsAccount) {
                $this->createBranchSavingsAccount($branch, $branchUser);
                $accountCreated = true;
            }

            return [
                'branch' => $branch->fresh(['branchUser']),
                'branch_user' => $branchUser->fresh(),
                'user_created' => $userCreated,
                'account_created' => $accountCreated,
                'branch_linked' => $branchLinked,
            ];
        });
    }

    public function ensureMemberAccounts(User $user): void
    {
        $this->createMemberAccounts($user, $user->name);
    }

    public function nextAccountNumberForProduct(SavingsProduct $product): string
    {
        return $this->generateAccountNumber($product);
    }

    public function create(array $data): Branch
    {
        return DB::transaction(function () use ($data): Branch {
            $branch = Branch::create([
                'name' => $data['branch_name'],
                'prefix' => $data['branch_prefix'],
                'id_prefix' => $data['loan_prefix'],
                'number_count' => 1,
                'loan_count' => 1,
                'registration_number' => $data['registration_number'] ?: null,
                'year_of_registration' => $data['year_of_registration'] ?: null,
                'contact_email' => $data['contact_email'],
                'contact_phone' => $data['contact_phone'] ?: null,
                'address' => $data['address'],
                'branch_meeting_days' => $data['branch_meeting_days'],
                'status' => 1,
            ]);

            $branchUser = User::create([
                'name' => $data['branch_name'],
                'last_name' => 'Branch',
                'email' => $this->makeSystemEmail($data['branch_name'], 'branch'),
                'password' => Hash::make(Str::password(32)),
                'user_type' => 'branch',
                'role_id' => null,
                'branch_id' => (string) $branch->id,
                'status' => 1,
                'profile_picture' => null,
                'society_role' => null,
                'society_exco' => false,
                'date_added_as_exco' => null,
                'former_exco' => false,
                'date_removed_as_exco' => null,
                'user_level' => null,
                'branch_account' => true,
                'is_verified' => true,
                'signature' => null,
                'member_no' => null,
                'designation' => 'Branch Account',
                'former_designation' => null,
            ]);

            $branch->update([
                'branch_user_id' => $branchUser->id,
                'photo' => $this->storeOptionalFile($data['photo'] ?? null, 'branches/logos'),
                'signature' => $this->storeOptionalFile($data['signature'] ?? null, 'branches/signatures'),
            ]);

            $this->createBranchSavingsAccount($branch, $branchUser);

            foreach ($data['excos'] as $index => $excoData) {
                $designation = Designation::query()->findOrFail($excoData['designation_id']);

                $excoUser = User::create([
                    'name' => $excoData['first_name'],
                    'last_name' => $excoData['last_name'],
                    'email' => $this->makeSystemEmail(
                        $excoData['first_name'] . '-' . $excoData['last_name'] . '-' . $excoData['phone'] . '-' . $index,
                        'exco'
                    ),
                    'password' => Hash::make(Str::password(24)),
                    'user_type' => 'staff',
                    'role_id' => null,
                    'branch_id' => (string) $branch->id,
                    'status' => 1,
                    'profile_picture' => $this->storeOptionalFile($excoData['image'] ?? null, 'branches/excos'),
                    'society_role' => $designation->name,
                    'society_exco' => true,
                    'date_added_as_exco' => now(),
                    'former_exco' => false,
                    'date_removed_as_exco' => null,
                    'user_level' => null,
                    'branch_account' => false,
                    'is_verified' => true,
                    'signature' => null,
                    'member_no' => null,
                    'designation' => $designation->name,
                    'former_designation' => null,
                ]);

                $this->createMemberAccounts($excoUser, $excoUser->name);
            }

            return $branch->load(['branchUser', 'excos']);
        });
    }

    public function update(Branch $branch, array $data): Branch
    {
        return DB::transaction(function () use ($branch, $data): Branch {
            $branch->update([
                'name' => $data['branch_name'],
                'prefix' => $data['branch_prefix'],
                'id_prefix' => $data['loan_prefix'],
                'registration_number' => $data['registration_number'] ?: null,
                'year_of_registration' => $data['year_of_registration'] ?: null,
                'contact_email' => $data['contact_email'],
                'contact_phone' => $data['contact_phone'] ?: null,
                'address' => $data['address'],
                'branch_meeting_days' => $data['branch_meeting_days'],
                'photo' => $this->storeOptionalFile($data['photo'] ?? null, 'branches/logos', $branch->photo),
                'signature' => $this->storeOptionalFile($data['signature'] ?? null, 'branches/signatures', $branch->signature),
            ]);

            if ($branch->branchUser) {
                $branch->branchUser->update([
                    'name' => $data['branch_name'],
                    'branch_id' => (string) $branch->id,
                ]);
            }

            $existingExcos = $branch->excos()->get()->keyBy('id');
            $submittedExcoIds = [];

            foreach ($data['excos'] as $index => $excoData) {
                $designation = Designation::query()->findOrFail($excoData['designation_id']);
                $userId = isset($excoData['user_id']) && $excoData['user_id'] !== '' ? (int) $excoData['user_id'] : null;

                if ($userId && $existingExcos->has($userId)) {
                    $existingExco = $existingExcos->get($userId);
                    $submittedExcoIds[] = $existingExco->id;

                    $existingExco->update([
                        'name' => $excoData['first_name'],
                        'last_name' => $excoData['last_name'],
                        'branch_id' => (string) $branch->id,
                        'profile_picture' => $this->storeOptionalFile(
                            $excoData['image'] ?? null,
                            'branches/excos',
                            $existingExco->profile_picture
                        ),
                        'society_role' => $designation->name,
                        'society_exco' => true,
                        'date_added_as_exco' => $existingExco->date_added_as_exco ?? now(),
                        'former_exco' => false,
                        'date_removed_as_exco' => null,
                        'designation' => $designation->name,
                    ]);

                    $this->createMemberAccounts($existingExco, $existingExco->name);

                    continue;
                }

                $newExco = User::create([
                    'name' => $excoData['first_name'],
                    'last_name' => $excoData['last_name'],
                    'email' => $this->makeSystemEmail(
                        $excoData['first_name'] . '-' . $excoData['last_name'] . '-' . $excoData['phone'] . '-' . $index,
                        'exco'
                    ),
                    'password' => Hash::make(Str::password(24)),
                    'user_type' => 'staff',
                    'role_id' => null,
                    'branch_id' => (string) $branch->id,
                    'status' => 1,
                    'profile_picture' => $this->storeOptionalFile($excoData['image'] ?? null, 'branches/excos'),
                    'society_role' => $designation->name,
                    'society_exco' => true,
                    'date_added_as_exco' => now(),
                    'former_exco' => false,
                    'date_removed_as_exco' => null,
                    'user_level' => null,
                    'branch_account' => false,
                    'is_verified' => true,
                    'signature' => null,
                    'member_no' => null,
                    'designation' => $designation->name,
                    'former_designation' => null,
                ]);

                $this->createMemberAccounts($newExco, $newExco->name);
                $submittedExcoIds[] = $newExco->id;
            }

            $removedExcos = $existingExcos->reject(
                fn (User $user): bool => in_array($user->id, $submittedExcoIds, true)
            );

            foreach ($removedExcos as $removedExco) {
                $removedExco->update([
                    'society_role' => null,
                    'society_exco' => false,
                    'former_exco' => true,
                    'date_removed_as_exco' => now(),
                    'designation' => null,
                    'former_designation' => $removedExco->designation ?: $removedExco->society_role,
                ]);
            }

            return $branch->fresh()->load(['branchUser', 'excos']);
        });
    }

    public function delete(Branch $branch): void
    {
        DB::transaction(function () use ($branch): void {
            $branch->update([
                'status' => 0,
            ]);

            $branch->delete();
        });
    }

    protected function createBranchSavingsAccount(Branch $branch, User $branchUser): SavingsAccount
    {
        $product = SavingsProduct::query()
            ->where('default_account', 1)
            ->where('status', 1)
            ->first();

        if (! $product) {
            throw new RuntimeException('No active default savings product was found for branch account creation.');
        }

        return SavingsAccount::create([
            'account_number' => $this->generateAccountNumber($product),
            'user_id' => $branchUser->id,
            'savings_product_id' => $product->id,
            'status' => 1,
            'opening_balance' => 0,
            'balance' => 0,
            'description' => "account creation for {$branch->name} branch",
            'created_user_id' => auth()->id(),
            'updated_user_id' => auth()->id(),
            'is_branch_acount' => 1,
        ]);
    }

    protected function createMemberAccounts(User $user, string $accountHolderName): void
    {
        $products = SavingsProduct::query()
            ->where('status', 1)
            ->whereIn('type', ['SAVINGS', 'SHARES', 'AUTHENTICATION', 'DEPOSIT'])
            ->orderByRaw("
                CASE type
                    WHEN 'SAVINGS' THEN 1
                    WHEN 'SHARES' THEN 2
                    WHEN 'AUTHENTICATION' THEN 3
                    WHEN 'DEPOSIT' THEN 4
                    ELSE 5
                END
            ")
            ->get();

        foreach ($products as $product) {
            $alreadyExists = SavingsAccount::query()
                ->where('user_id', $user->id)
                ->where('savings_product_id', $product->id)
                ->exists();

            if ($alreadyExists) {
                continue;
            }

            SavingsAccount::create([
                'account_number' => $this->generateAccountNumber($product),
                'user_id' => $user->id,
                'savings_product_id' => $product->id,
                'status' => 1,
                'opening_balance' => 0,
                'balance' => 0,
                'description' => "account creation for {$accountHolderName}",
                'created_user_id' => auth()->id(),
                'updated_user_id' => auth()->id(),
                'is_branch_acount' => 0,
            ]);
        }
    }

    protected function generateAccountNumber(SavingsProduct $product): string
    {
        $prefix = $product->account_number_prefix ?? '';
        $maxUsedValue = SavingsAccount::query()
            ->where('account_number', 'like', $prefix . '%')
            ->get()
            ->map(function (SavingsAccount $account) use ($prefix): int {
                return (int) Str::after($account->account_number, $prefix);
            })
            ->max() ?? 0;

        $nextNumber = max((int) $product->starting_account_number, $maxUsedValue + 1);

        return $prefix . $nextNumber;
    }

    protected function makeSystemEmail(string $value, string $context): string
    {
        $base = Str::slug(Str::limit($value, 40, ''), '.');
        $base = $base !== '' ? $base : $context;

        do {
            $email = "{$context}.{$base}." . Str::lower(Str::random(6)) . '@system.local';
        } while (User::query()->where('email', $email)->exists());

        return $email;
    }

    protected function storeOptionalFile(?UploadedFile $file, string $path, ?string $existingPath = null): ?string
    {
        if (! $file) {
            return $existingPath;
        }

        return $file->store($path, 'public');
    }
}
