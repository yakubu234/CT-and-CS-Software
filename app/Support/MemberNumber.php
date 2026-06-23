<?php

namespace App\Support;

use App\Models\Branch;

class MemberNumber
{
    public static function normalize(?string $memberNumber, ?Branch $branch = null, int|string|null $branchId = null): ?string
    {
        $memberNumber = trim((string) $memberNumber);

        if ($memberNumber === '') {
            return null;
        }

        if (! preg_match('/(\d+)(?!.*\d)/', $memberNumber, $matches)) {
            return null;
        }

        return self::format((int) $matches[1], $branch, $branchId);
    }

    public static function format(int $number, ?Branch $branch = null, int|string|null $branchId = null): string
    {
        return self::prefix($branch, $branchId) . '_' . str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }

    public static function extractNumber(?string $memberNumber): ?int
    {
        $memberNumber = trim((string) $memberNumber);

        if ($memberNumber === '') {
            return null;
        }

        if (! preg_match('/(\d+)(?!.*\d)/', $memberNumber, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    public static function prefix(?Branch $branch = null, int|string|null $branchId = null): string
    {
        $prefix = trim((string) ($branch?->prefix ?? ''));

        if ($prefix !== '') {
            return $prefix;
        }

        $resolvedBranchId = $branch?->id ?? $branchId;

        return 'BRANCH_' . $resolvedBranchId;
    }
}
