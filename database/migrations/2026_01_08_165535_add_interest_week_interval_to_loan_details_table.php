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
        Schema::table('loan_details', function (Blueprint $table) {
            $table->string('interest_week_interval', 200)->nullable();
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->string('branch_meeting_days', 200)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_details', function (Blueprint $table) {
            $table->dropColumn('interest_week_interval');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('branch_meeting_days');
        });
    }
};
