<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->text('assigned_branch')->nullable()->after('designation'); // Adding assigned_branch to track the branch the user is currently assigned to, its an array of branch IDs
            $table->text('last_branch_id')->nullable()->after('assigned_branch'); // Adding last_branch_id to track the last branch the user was assigned to/visited
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
           $table->dropColumn(['assigned_branch']);
           $table->dropColumn(['last_branch_id']);
        });
    }
};
