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
            $table->string('former_first_payment_date')->nullable()->comment('Store former_first_payment_date before this loan was added.');
            $table->string('former_remarks')->nullable()->comment('Store former_remarks before this loan was added');
            $table->string('former_interest_rate')->nullable()->comment('Store former_interest_rate before this loan was added');
            $table->string('former_recent_added_amount')->nullable()->comment('Store former_recent_added_amount before this loan was added');
            $table->text('former_custom_fields')->nullable()->comment('Store former_custom_fields before this loan was added');
            $table->string('former_late_payment_penalties')->nullable()->comment('Store former_late_payment_penalties before this loan was added');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_details', function (Blueprint $table) {
            $table->dropColumn('former_first_payment_date');
            $table->dropColumn('former_remarks');
            $table->dropColumn('former_interest_rate');
            $table->dropColumn('former_recent_added_amount');
            $table->dropColumn('former_custom_fields');
            $table->dropColumn('former_late_payment_penalties');
        });
    }
};
