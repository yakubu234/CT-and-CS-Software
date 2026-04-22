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
        Schema::create('loan_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->date('paid_at')->nullable();
            $table->decimal('late_penalties', 10, 2)->nullable();
            $table->decimal('interest', 10, 2)->nullable();
            $table->decimal('repayment_amount', 10, 2)->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->bigInteger('transaction_id')->nullable();
            $table->unsignedBigInteger('repayment_id')->nullable();
            $table->string('balance')->nullable();
            $table->string('interest_rate')->nullable();
            $table->string('is_interest_piad')->nullable();
            $table->enum('transation_type', ['dr', 'cr'])->default('cr');
            $table->string('interest_paid')->nullable();
            $table->string('applied_amount')->nullable()->comment('the newly added amount');
            $table->boolean('is_approved')->default(0)->comment('to show if this added amount has been approved, that shows if it should be added to outstanding');
            $table->string('is_calculated')->default('0')->comment('denote if the amount has been added to balance, if not, it adds in the next repayment.');
            $table->string('total_outstanding')->nullable();
            $table->timestamps();
            // Foreign keys
            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_payments');
    }
};
