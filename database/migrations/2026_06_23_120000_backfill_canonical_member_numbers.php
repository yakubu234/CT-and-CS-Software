<?php

use App\Models\Branch;
use App\Models\User;
use App\Support\MemberNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Branch::query()
            ->withTrashed()
            ->orderBy('id')
            ->chunkById(100, function (Collection $branches): void {
                foreach ($branches as $branch) {
                    $members = User::query()
                        ->withTrashed()
                        ->with('detail')
                        ->where('branch_id', (string) $branch->id)
                        ->where('branch_account', false)
                        ->where(function ($query): void {
                            $query->where('user_type', 'customer')
                                ->orWhere('society_exco', true)
                                ->orWhere('former_exco', true);
                        })
                        ->orderBy('created_at')
                        ->orderBy('id')
                        ->get();

                    if ($members->isEmpty()) {
                        continue;
                    }

                    DB::transaction(function () use ($branch, $members): void {
                        $usedNumbers = [];
                        $highestNumber = 0;

                        foreach ($members as $member) {
                            $existingNumber = MemberNumber::normalize(
                                $member->detail?->member_no ?: $member->member_no,
                                $branch
                            );

                            $candidateNumber = MemberNumber::extractNumber($existingNumber);

                            if ($candidateNumber === null || isset($usedNumbers[$candidateNumber])) {
                                $candidateNumber = $this->nextAvailableNumber($usedNumbers, $highestNumber);
                            }

                            $usedNumbers[$candidateNumber] = true;
                            $highestNumber = max($highestNumber, $candidateNumber);
                            $canonicalNumber = MemberNumber::format($candidateNumber, $branch);

                            if ($member->member_no !== $canonicalNumber) {
                                $member->forceFill(['member_no' => $canonicalNumber])->save();
                            }

                            if ($member->detail) {
                                if ($member->detail->member_no !== $canonicalNumber || (int) $member->detail->branch_id !== (int) $branch->id) {
                                    $member->detail->forceFill([
                                        'branch_id' => $branch->id,
                                        'member_no' => $canonicalNumber,
                                    ])->save();
                                }

                                continue;
                            }

                            $member->detail()->create([
                                'branch_id' => $branch->id,
                                'member_no' => $canonicalNumber,
                            ]);
                        }

                        DB::table('branches')
                            ->where('id', $branch->id)
                            ->update([
                                'number_count' => str_pad((string) $highestNumber, 4, '0', STR_PAD_LEFT),
                            ]);
                    });
                }
            });
    }

    public function down(): void
    {
        // Keep repaired member numbers in place.
    }

    private function nextAvailableNumber(array $usedNumbers, int $highestNumber): int
    {
        $candidateNumber = max($highestNumber, 0) + 1;

        while (isset($usedNumbers[$candidateNumber])) {
            $candidateNumber++;
        }

        return $candidateNumber;
    }
};
