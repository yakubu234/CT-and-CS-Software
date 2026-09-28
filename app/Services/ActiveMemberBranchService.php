<?php

namespace App\Services;

use App\Models\MemberBranchMembership;
use App\Models\User;
use Illuminate\Support\Collection;

class ActiveMemberBranchService
{
    private const SESSION_KEY = 'customer_active_membership_id';

    public function memberships(User $user): Collection
    {
        return $user->branchMemberships()
            ->with('branch')
            ->where('status', true)
            ->whereHas('branch', fn ($query) => $query->where('status', true)->whereNull('deleted_at'))
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get();
    }

    public function current(User $user): ?MemberBranchMembership
    {
        $memberships = $this->memberships($user);
        $requestedId = (int) session(self::SESSION_KEY);
        $membership = $memberships->firstWhere('id', $requestedId) ?: $memberships->first();

        if ($membership) {
            session([self::SESSION_KEY => $membership->id]);
            $user->setRelation('activeMembership', $membership);
            $user->setRelation('branch', $membership->branch);
        }

        return $membership;
    }

    public function switch(User $user, int $membershipId): ?MemberBranchMembership
    {
        $membership = $this->memberships($user)->firstWhere('id', $membershipId);

        if (! $membership) {
            return null;
        }

        session([self::SESSION_KEY => $membership->id]);
        $user->setRelation('activeMembership', $membership);
        $user->setRelation('branch', $membership->branch);

        return $membership;
    }
}
