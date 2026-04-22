<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ActiveBranchService
{
    private const SESSION_KEY = 'active_branch_id';

    public function currentBranch(?User $user = null): ?Branch
    {
        $user ??= Auth::user();

        if (! $user) {
            return null;
        }

        $branchId = Session::get(self::SESSION_KEY);

        if ($branchId) {
            $branch = $this->availableBranchesQuery($user)->find($branchId);

            if ($branch) {
                return $branch;
            }
        }

        if ($user->last_branch_id) {
            $branch = $this->availableBranchesQuery($user)->find($user->last_branch_id);

            if ($branch) {
                Session::put(self::SESSION_KEY, $branch->id);

                return $branch;
            }
        }

        return $this->defaultBranch($user);
    }

    public function ensureActiveBranch(?User $user = null): ?Branch
    {
        $user ??= Auth::user();

        if (! $user) {
            return null;
        }

        $branch = $this->currentBranch($user);

        if (! $branch) {
            Session::forget(self::SESSION_KEY);

            if ($user->last_branch_id !== null) {
                $user->forceFill(['last_branch_id' => null])->save();
            }

            return null;
        }

        if ((int) Session::get(self::SESSION_KEY) !== (int) $branch->id) {
            Session::put(self::SESSION_KEY, $branch->id);
        }

        if ((int) $user->last_branch_id !== (int) $branch->id) {
            $user->forceFill(['last_branch_id' => $branch->id])->save();
        }

        return $branch;
    }

    public function setCurrentBranch(Branch|int $branch, ?User $user = null): ?Branch
    {
        $user ??= Auth::user();

        if (! $user) {
            return null;
        }

        $branchModel = $branch instanceof Branch
            ? $this->availableBranchesQuery($user)->find($branch->id)
            : $this->availableBranchesQuery($user)->find($branch);

        if (! $branchModel) {
            return null;
        }

        Session::put(self::SESSION_KEY, $branchModel->id);

        if ((int) $user->last_branch_id !== (int) $branchModel->id) {
            $user->forceFill(['last_branch_id' => $branchModel->id])->save();
        }

        return $branchModel;
    }

    public function availableBranches(?User $user = null): Collection
    {
        $user ??= Auth::user();

        if (! $user) {
            return collect();
        }

        return $this->availableBranchesQuery($user)
            ->orderBy('name')
            ->get();
    }

    public function availableBranchesQuery(?User $user = null): Builder
    {
        $user ??= Auth::user();

        $query = Branch::query()
            ->whereNull('deleted_at')
            ->where('status', 1);

        if (! $user) {
            return $query;
        }

        $allowedBranchIds = $this->allowedBranchIds($user);

        if ($allowedBranchIds !== null) {
            $query->whereIn('id', $allowedBranchIds);
        }

        return $query;
    }

    private function defaultBranch(User $user): ?Branch
    {
        $branch = $this->availableBranchesQuery($user)
            ->orderBy('id')
            ->first();

        if (! $branch) {
            return null;
        }

        Session::put(self::SESSION_KEY, $branch->id);

        if ((int) $user->last_branch_id !== (int) $branch->id) {
            $user->forceFill(['last_branch_id' => $branch->id])->save();
        }

        return $branch;
    }

    private function allowedBranchIds(User $user): ?array
    {
        $assignedBranch = $user->assigned_branch;

        if ($assignedBranch === null || $assignedBranch === '') {
            return null;
        }

        $decoded = json_decode($assignedBranch, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $values = is_array($decoded) ? $decoded : [$decoded];
        } else {
            $values = preg_split('/[\s,]+/', $assignedBranch, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        $branchIds = collect($values)
            ->filter(static fn ($value): bool => is_numeric($value))
            ->map(static fn ($value): int => (int) $value)
            ->unique()
            ->values()
            ->all();

        return $branchIds === [] ? null : $branchIds;
    }
}
