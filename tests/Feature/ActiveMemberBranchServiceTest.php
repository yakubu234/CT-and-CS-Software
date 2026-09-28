<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ActiveMemberBranchService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ActiveMemberBranchServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('member_branch_memberships', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('member_number');
            $table->boolean('is_primary')->default(false);
            $table->boolean('status')->default(true);
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('member_branch_memberships');
        Schema::dropIfExists('branches');

        parent::tearDown();
    }

    public function test_member_can_switch_between_only_their_active_societies(): void
    {
        $now = now();
        DB::table('branches')->insert([
            ['id' => 10, 'name' => 'First Society', 'status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 20, 'name' => 'Second Society', 'status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 30, 'name' => 'Other Society', 'status' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('member_branch_memberships')->insert([
            ['id' => 101, 'user_id' => 1, 'branch_id' => 10, 'member_number' => 'FS-001', 'is_primary' => true, 'status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 102, 'user_id' => 1, 'branch_id' => 20, 'member_number' => 'SS-009', 'is_primary' => false, 'status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 103, 'user_id' => 2, 'branch_id' => 30, 'member_number' => 'OS-002', 'is_primary' => true, 'status' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $user = new User;
        $user->id = 1;
        $user->exists = true;
        $service = app(ActiveMemberBranchService::class);

        $this->assertSame(101, $service->current($user)?->id);
        $this->assertSame(102, $service->switch($user, 102)?->id);
        $this->assertSame('Second Society', $user->branch->name);
        $this->assertSame('SS-009', $user->display_member_no);
        $this->assertNull($service->switch($user, 103));
        $this->assertSame(102, session('customer_active_membership_id'));
    }
}
