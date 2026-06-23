<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\User;
use App\Models\UserDetail;
use App\Services\BranchService;
use App\Services\MemberService;
use Mockery;
use Tests\TestCase;

class MemberServiceTest extends TestCase
{
    public function test_it_builds_the_first_member_number_from_zero_based_branch_counters(): void
    {
        $service = new MemberService(Mockery::mock(BranchService::class));

        $branch = new Branch();
        $branch->forceFill([
            'id' => 15,
            'prefix' => null,
            'number_count' => 0,
        ]);
        $branch->exists = true;

        $this->assertSame('BRANCH_15_0001', $service->nextMemberNumber($branch));
    }

    public function test_it_normalizes_malformed_existing_member_numbers_for_preview(): void
    {
        $service = new MemberService(Mockery::mock(BranchService::class));

        $branch = new Branch();
        $branch->forceFill([
            'id' => 15,
            'prefix' => null,
            'number_count' => 9,
        ]);
        $branch->exists = true;

        $member = new User();
        $member->forceFill([
            'member_no' => '_0002',
        ]);
        $member->setRelation('detail', new UserDetail([
            'member_no' => null,
        ]));

        $this->assertSame('BRANCH_15_0002', $service->memberNumberPreview($member, $branch));
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
