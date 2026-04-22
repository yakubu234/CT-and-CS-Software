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
          Schema::create('loan_repayment_for_update', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('loan_id');
            $table->unsignedBigInteger('lates_debit_id');
            $table->timestamps();

            // Indexes
            $table->index('loan_id', 'loan_repayment_for_update_loan_id_foreign');
            $table->index('lates_debit_id', 'loan_repayment_for_update_lates_debit_id_foreign');

            // Foreign keys
            $table->foreign('loan_id')
                ->references('id')
                ->on('loans')
                ->onDelete('cascade');

            $table->foreign('lates_debit_id')
                ->references('id')
                ->on('loan_payments')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_repayment_for_update');
    }
};
