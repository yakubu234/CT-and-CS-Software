<?php

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Branch::query()
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunkById(100, function (Collection $branches): void {
                foreach ($branches as $branch) {
                    $lastUsedNumber = User::query()
                        ->withTrashed()
                        ->where('user_type', 'customer')
                        ->where('branch_account', false)
                        ->where('branch_id', (string) $branch->id)
                        ->get()
                        ->map(function (User $user) use ($branch): int {
                            $memberNumber = (string) ($user->member_no ?: $user->detail?->member_no ?: '');

                            if ($memberNumber === '') {
                                return 0;
                            }

                            $expectedPrefix = ($branch->prefix ?: ('BRANCH_' . $branch->id)) . '_';

                            if (Str::startsWith($memberNumber, $expectedPrefix)) {
                                return (int) Str::after($memberNumber, $expectedPrefix);
                            }

                            preg_match('/(\d+)(?!.*\d)/', $memberNumber, $matches);

                            return isset($matches[1]) ? (int) $matches[1] : 0;
                        })
                        ->max();

                    if (! $lastUsedNumber) {
                        $lastUsedNumber = User::query()
                            ->withTrashed()
                            ->where('user_type', 'customer')
                            ->where('branch_account', false)
                            ->where('branch_id', (string) $branch->id)
                            ->count();
                    }

                    DB::table('branches')
                        ->where('id', $branch->id)
                        ->update([
                            'number_count' => str_pad((string) $lastUsedNumber, 4, '0', STR_PAD_LEFT),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Preserve backfilled counters.
    }
};
