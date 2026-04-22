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
        Schema::create('loan_details', function (Blueprint $table) {
            $table->id();
            $table->decimal('applied_amount', 15, 2);
            $table->decimal('amount_repayed', 15, 2)->default(0.00)->comment('The total amount the borrower repayed before taking a new loan');
            $table->unsignedBigInteger('created_user_id')->nullable()->comment('User who created this loan detail');
            $table->unsignedBigInteger('loan_id');
            $table->unsignedBigInteger('borrower_id');
            $table->unsignedBigInteger('branch_id');
            $table->date('release_date');
            $table->date('due_date');
            $table->decimal('late_payment_penalties', 15, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->decimal('interest_rate', 5, 2)->default(0.0);
            $table->json('custom_fields')->nullable();
            $table->decimal('interest', 5, 2)->default(0.0);
            $table->boolean('status')->default(false)->comment('When true, that means it has been approved');
            $table->boolean('repayment_status')->default(false)->comment('When true, that means it has been fully repaid');
            $table->timestamps();

            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
            $table->foreign('borrower_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_details');
    }
};
