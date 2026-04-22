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
            $table->string('transaction_id')->nullable()->comment('Store ID where its debitted from branch account.');
            $table->string('former_applied_amount')->nullable()->comment('Store total payable before this loan was added');
            $table->string('former_total_payable')->nullable()->comment('Store total payable before this loan was added');
            $table->string('former_amount_due')->nullable()->comment('Store amount before this loan was added');
            $table->string('former_balanace')->nullable()->comment('Store balance before this loan was added');
            $table->string('former_total_paid')->nullable()->comment('Store total paid before this loan was added');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_details', function (Blueprint $table) {
            $table->dropColumn('transaction_id');
            $table->dropColumn('former_applied_amount');
            $table->dropColumn('former_total_payable');
            $table->dropColumn('former_amount_due');
            $table->dropColumn('former_balanace');
            $table->dropColumn('former_total_paid');
        });
    }
};
