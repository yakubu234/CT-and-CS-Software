<?php

namespace App\Services;

use App\Models\MemberBranchMembership;
use App\Models\MemberMerge;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MemberMergeService
{
    /** @var array<string, string> */
    private const MEMBER_REFERENCES = [
        'savings_accounts' => 'user_id',
        'transactions' => 'user_id',
        'loans' => 'borrower_id',
        'loan_details' => 'borrower_id',
        'loan_payments' => 'user_id',
        'member_documents' => 'user_id',
        'bookings' => 'user_id',
        'sms_messages' => 'user_id',
        'email_messages' => 'user_id',
        'customer_support_requests' => 'user_id',
        'support_request_messages' => 'sender_id',
    ];

    public function merge(array $data, ?User $actor = null): User
    {
        return DB::transaction(function () use ($data, $actor): User {
            $users = User::query()
                ->whereIn('id', [$data['canonical_user_id'], $data['merged_user_id']])
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            /** @var User|null $canonical */
            $canonical = $users->get((int) $data['canonical_user_id']);
            /** @var User|null $merged */
            $merged = $users->get((int) $data['merged_user_id']);

            $this->assertMergeable($canonical, $merged);

            $memberships = MemberBranchMembership::query()
                ->whereIn('user_id', [$canonical->id, $merged->id])
                ->lockForUpdate()
                ->get();

            $overlappingBranches = $memberships->groupBy('branch_id')->filter(fn ($items) => $items->count() > 1);
            if ($overlappingBranches->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'merged_user_id' => 'These accounts share a society already. Resolve that duplicate society membership manually before merging.',
                ]);
            }

            $primaryMembership = $memberships->firstWhere('id', (int) $data['primary_membership_id']);
            if (! $primaryMembership) {
                throw ValidationException::withMessages([
                    'primary_membership_id' => 'The selected default society does not belong to either member.',
                ]);
            }

            $emailOwner = $users->get((int) $data['email_source_user_id']);
            $mobileOwner = $users->get((int) $data['mobile_source_user_id']);
            $selectedEmail = Str::lower(trim((string) $emailOwner->email));
            $selectedMobile = $mobileOwner->detail?->mobile;

            $emailConflict = User::withTrashed()
                ->whereRaw('LOWER(email) = ?', [$selectedEmail])
                ->whereNotIn('id', [$canonical->id, $merged->id])
                ->exists();
            if ($emailConflict) {
                throw ValidationException::withMessages(['email_source_user_id' => 'That email is already used by another account.']);
            }

            $canonicalSnapshot = $this->snapshot($canonical);
            $mergedSnapshot = $this->snapshot($merged);
            $placeholderEmail = 'merged-' . $merged->id . '-' . Str::lower(Str::random(12)) . '@invalid.local';

            // Release a selected source email before assigning it to the canonical row.
            $merged->forceFill([
                'email' => $placeholderEmail,
                'status' => 0,
                'remember_token' => null,
                'provider' => null,
                'provider_id' => null,
            ])->save();

            $recordsMoved = [];
            foreach (self::MEMBER_REFERENCES as $table => $column) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                    $recordsMoved[$table] = DB::table($table)
                        ->where($column, $merged->id)
                        ->update([$column => $canonical->id]);
                }
            }

            $recordsMoved['member_branch_memberships'] = MemberBranchMembership::query()
                ->where('user_id', $merged->id)
                ->update(['user_id' => $canonical->id, 'is_primary' => false, 'updated_at' => now()]);

            $this->mergeEmailPreferences($canonical->id, $merged->id, $recordsMoved);

            MemberBranchMembership::query()
                ->where('user_id', $canonical->id)
                ->update(['is_primary' => false, 'updated_at' => now()]);
            MemberBranchMembership::query()
                ->whereKey($primaryMembership->id)
                ->update(['is_primary' => true, 'updated_at' => now()]);

            $canonicalDetail = $canonical->detail;
            $mergedDetail = $merged->detail;
            if (! $canonicalDetail && $mergedDetail) {
                $canonicalDetail = $canonical->detail()->create(
                    collect($mergedDetail->getAttributes())
                        ->except(['id', 'user_id', 'created_at', 'updated_at'])
                        ->all()
                );
            }
            if ($canonicalDetail) {
                $canonicalDetail->forceFill([
                    'mobile' => $selectedMobile,
                    'branch_id' => $primaryMembership->branch_id,
                    'member_no' => $primaryMembership->member_number,
                ])->save();
            }

            $canonical->forceFill([
                'email' => $selectedEmail,
                'branch_id' => (string) $primaryMembership->branch_id,
                'last_branch_id' => $primaryMembership->branch_id,
                'member_no' => $primaryMembership->member_number,
                'status' => 1,
            ])->save();

            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $merged->id)->delete();
            }
            if (Schema::hasTable('password_reset_tokens')) {
                DB::table('password_reset_tokens')->whereIn('email', [$canonicalSnapshot['email'], $mergedSnapshot['email']])->delete();
            }

            MemberMerge::create([
                'canonical_user_id' => $canonical->id,
                'merged_user_id' => $merged->id,
                'primary_membership_id' => $primaryMembership->id,
                'merged_by' => $actor?->id,
                'selected_email' => $selectedEmail,
                'selected_mobile' => $selectedMobile,
                'canonical_snapshot' => $canonicalSnapshot,
                'merged_snapshot' => $mergedSnapshot,
                'records_moved' => $recordsMoved,
            ]);

            $merged->delete();

            return $canonical->fresh(['detail', 'branchMemberships.branch']);
        }, 3);
    }

    private function assertMergeable(?User $canonical, ?User $merged): void
    {
        foreach (['canonical_user_id' => $canonical, 'merged_user_id' => $merged] as $field => $user) {
            if (! $user || $user->user_type !== 'customer' || $user->branch_account) {
                throw ValidationException::withMessages([$field => 'Only active customer/member accounts can be merged.']);
            }
        }
    }

    private function mergeEmailPreferences(int $canonicalId, int $mergedId, array &$recordsMoved): void
    {
        if (! Schema::hasTable('email_preferences')) {
            return;
        }

        $moved = 0;
        $preferences = DB::table('email_preferences')->where('user_id', $mergedId)->get();
        foreach ($preferences as $preference) {
            $conflict = DB::table('email_preferences')
                ->where('user_id', $canonicalId)
                ->where('branch_id', $preference->branch_id)
                ->exists();
            if ($conflict) {
                DB::table('email_preferences')->where('id', $preference->id)->delete();
            } else {
                $moved += DB::table('email_preferences')->where('id', $preference->id)->update(['user_id' => $canonicalId]);
            }
        }
        $recordsMoved['email_preferences'] = $moved;
    }

    private function snapshot(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->getRawOriginal('name'),
            'last_name' => $user->last_name,
            'email' => $user->email,
            'branch_id' => $user->branch_id,
            'member_no' => $user->member_no,
            'detail' => $user->detail?->getAttributes(),
        ];
    }
}
