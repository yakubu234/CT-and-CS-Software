<?php

namespace App\Services;

use App\Models\MemberBranchMembership;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Repairs society memberships that registration wrongly attached to an existing
 * member's login (fuzzy phone match + "link existing" checkbox). The membership keeps
 * its member number, savings accounts, transactions and loans, and is moved to a new
 * member record for the real person.
 */
class MembershipDetachService
{
    /**
     * Secondary memberships, the member they are attached to, and the money/loans on them.
     * Memberships created by the deliberate merge tool are flagged so they can be skipped.
     */
    public function linkedMemberships(): Collection
    {
        return collect(DB::select(<<<'SQL'
            SELECT m.id AS membership_id,
                   m.member_number AS linked_member_number,
                   b.name AS society,
                   m.created_at AS linked_at,
                   u.id AS attached_user_id,
                   CONCAT_WS(' ', u.name, u.last_name) AS attached_to,
                   p.member_number AS attached_primary_number,
                   (SELECT COUNT(*) FROM transactions t WHERE t.member_branch_membership_id = m.id AND t.deleted_at IS NULL) AS live_transactions,
                   (SELECT COALESCE(SUM(CASE WHEN t.dr_cr = 'cr' THEN t.amount ELSE -t.amount END), 0) FROM transactions t WHERE t.member_branch_membership_id = m.id AND t.deleted_at IS NULL) AS net_amount,
                   (SELECT COUNT(*) FROM transactions t WHERE t.member_branch_membership_id = m.id AND t.deleted_at IS NOT NULL) AS deleted_transactions,
                   (SELECT COUNT(*) FROM loans l WHERE l.member_branch_membership_id = m.id) AS loans,
                   EXISTS (SELECT 1 FROM member_merges mm WHERE mm.canonical_user_id = m.user_id) AS from_merge_tool
            FROM member_branch_memberships m
            JOIN users u ON u.id = m.user_id
            LEFT JOIN branches b ON b.id = m.branch_id
            LEFT JOIN member_branch_memberships p ON p.user_id = m.user_id AND p.is_primary = 1
            WHERE m.is_primary = 0
            ORDER BY m.created_at
        SQL));
    }

    /**
     * NOTE: in production users, user_details, member_branch_memberships and the loan tables
     * are MyISAM, so a database transaction cannot roll them back. All validation therefore
     * runs before any write, and --dry-run never writes at all (it only counts).
     *
     * @param  array{first_name:string,last_name:string,email:string,mobile:string}  $person
     */
    public function detach(int $membershipId, array $person, bool $dryRun = false): array
    {
        $membership = MemberBranchMembership::query()->findOrFail($membershipId);
        $previousOwner = User::withTrashed()->findOrFail($membership->user_id);

        if ($membership->is_primary) {
            throw new RuntimeException("Membership {$membership->member_number} is the member's primary membership and cannot be detached.");
        }

        $email = Str::lower(trim($person['email']));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("{$email} is not a valid email address.");
        }
        if (User::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            throw new RuntimeException("The email {$email} is already in use. Provide the real member's own email.");
        }

        $loanIds = DB::table('loans')->where('member_branch_membership_id', $membership->id)->pluck('id');
        $transactionIds = DB::table('transactions')
            ->where('user_id', $previousOwner->id)
            ->where(fn ($query) => $query
                ->where('member_branch_membership_id', $membership->id)
                ->when($loanIds->isNotEmpty(), fn ($q) => $q->orWhereIn('loan_id', $loanIds)))
            ->pluck('id');
        $mirrorQuery = fn () => DB::table('transactions')
            ->where('is_branch', 1)
            ->where(fn ($query) => $query
                ->whereIn('parent_id', $transactionIds)
                ->orWhereIn(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(transaction_details, '$.source_transaction_id'))"), $transactionIds->map(fn ($id) => (string) $id)));

        $summary = [
            'membership_id' => $membership->id,
            'member_number' => $membership->member_number,
            'detached_from_user_id' => $previousOwner->id,
            'detached_from' => $previousOwner->name,
            'new_user_id' => null,
            'new_member' => trim($person['first_name'] . ' ' . $person['last_name']),
            'dry_run' => $dryRun,
            'moved' => [
                'savings_accounts' => DB::table('savings_accounts')->where('member_branch_membership_id', $membership->id)->count(),
                'transactions' => $transactionIds->count(),
                'branch_mirror_transactions' => $transactionIds->isEmpty() ? 0 : $mirrorQuery()->count(),
                'loans' => $loanIds->count(),
                'loan_details' => $loanIds->isEmpty() ? 0 : DB::table('loan_details')->whereIn('loan_id', $loanIds)->count(),
                'loan_payments' => $loanIds->isEmpty() ? 0 : DB::table('loan_payments')->whereIn('loan_id', $loanIds)->count(),
            ],
        ];

        if ($dryRun) {
            return $summary;
        }

        $summary['new_user_id'] = DB::transaction(function () use ($membership, $person, $email, $loanIds, $transactionIds, $mirrorQuery): int {
            $member = User::create([
                'name' => trim($person['first_name']),
                'last_name' => trim($person['last_name']),
                'email' => $email,
                'password' => Hash::make(Str::password(24)),
                'must_change_password' => true,
                'user_type' => 'customer',
                'role_id' => null,
                'branch_id' => (string) $membership->branch_id,
                'status' => 1,
                'branch_account' => false,
                'is_verified' => true,
                'member_no' => $membership->member_number,
                'designation' => 'Member',
            ]);

            $member->detail()->create([
                'branch_id' => $membership->branch_id,
                'user_id' => $member->id,
                'mobile' => trim($person['mobile']),
                'member_no' => $membership->member_number,
            ]);

            DB::table('savings_accounts')
                ->where('member_branch_membership_id', $membership->id)
                ->update(['user_id' => $member->id, 'updated_at' => now()]);

            if ($transactionIds->isNotEmpty()) {
                $pdo = DB::getPdo();
                DB::table('transactions')->whereIn('id', $transactionIds)->update([
                    'user_id' => $member->id,
                    'member_branch_membership_id' => $membership->id,
                    'transaction_details' => DB::raw("JSON_SET(COALESCE(transaction_details, '{}'), '$.member_no', " . $pdo->quote($membership->member_number) . ')'),
                ]);
                $mirrorQuery()->update([
                    'transaction_details' => DB::raw(
                        "JSON_SET(COALESCE(transaction_details, '{}'), '$.member_id', " . (int) $member->id
                        . ", '$.member_name', " . $pdo->quote($member->name)
                        . ", '$.member_no', " . $pdo->quote($membership->member_number) . ')'
                    ),
                ]);
            }

            if ($loanIds->isNotEmpty()) {
                DB::table('loans')->whereIn('id', $loanIds)->update(['borrower_id' => $member->id]);
                DB::table('loan_details')->whereIn('loan_id', $loanIds)->update(['borrower_id' => $member->id]);
                DB::table('loan_payments')->whereIn('loan_id', $loanIds)->update(['user_id' => $member->id]);
            }

            $membership->forceFill(['user_id' => $member->id, 'is_primary' => true])->save();

            return $member->id;
        });

        Log::warning('Detached wrongly linked society membership into its own member record', $summary);

        return $summary;
    }
}
