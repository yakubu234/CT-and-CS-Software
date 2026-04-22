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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_id', 30)->nullable();
            $table->unsignedBigInteger('loan_product_id');
            $table->unsignedBigInteger('borrower_id');
            $table->date('first_payment_date');
            $table->date('release_date')->nullable();
            $table->string('applied_amount', 200);
            $table->string('total_payable', 200)->nullable();
            $table->string('due_date', 255)->nullable();
            $table->string('interest_rate', 255)->nullable();
            $table->string('interest', 255)->nullable();
            $table->string('total_paid', 200)->nullable();
            $table->string('late_payment_penalties', 200)->nullable();
            $table->text('attachment')->nullable();
            $table->text('description')->nullable();
            $table->text('remarks')->nullable();
            $table->integer('status')->default(0);
            $table->date('approved_date')->nullable();
            $table->unsignedBigInteger('approved_user_id')->nullable();
            $table->unsignedBigInteger('created_user_id')->nullable();
            $table->unsignedBigInteger('updated_user_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->text('custom_fields')->nullable();
            $table->enum('repayment_status', ['refunded', 'defaulted', 'active'])->default('active');
            $table->string('recent_added_amount', 191)->nullable();
            $table->string('amount_due', 191)->nullable();
            $table->string('balanace', 191)->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
