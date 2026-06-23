<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Support\MemberNumber;
use Tests\TestCase;

class MemberNumberTest extends TestCase
{
    public function test_it_normalizes_member_numbers_using_branch_fallback_prefixes(): void
    {
        $branch = new Branch();
        $branch->forceFill([
            'id' => 15,
            'prefix' => null,
        ]);
        $branch->exists = true;

        $this->assertSame('BRANCH_15_0002', MemberNumber::normalize('_0002', $branch));
        $this->assertSame('BRANCH_15_0018', MemberNumber::normalize('0018', $branch));
        $this->assertSame('FIYIN_0009', MemberNumber::normalize('FIYIN_0009', new Branch([
            'id' => 15,
            'prefix' => 'FIYIN',
        ])));
    }
}
