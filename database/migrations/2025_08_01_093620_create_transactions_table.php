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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
             $table->unsignedBigInteger('user_id');
            $table->dateTime('trans_date');
            $table->unsignedBigInteger('savings_account_id')->nullable();
            $table->decimal('charge', 10, 2)->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('gateway_amount', 10, 2)->default(0.00);
            $table->string('dr_cr', 2);
            $table->string('type', 30);
            $table->string('method', 20);
            $table->tinyInteger('status');
            $table->text('note')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('loan_id')->nullable();
            $table->bigInteger('ref_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable()->comment('Parent transaction id');
            $table->bigInteger('gateway_id')->nullable()->comment('PayPal | Stripe | Other Gateway');
            $table->bigInteger('created_user_id')->nullable();
            $table->bigInteger('updated_user_id')->nullable();
            $table->bigInteger('branch_id')->nullable();
            $table->text('transaction_details')->nullable();
            $table->string('tracking_id', 191)->nullable();
            $table->timestamps(); // created_at and updated_at
            $table->string('detail_id', 191)->nullable()->comment('store id or either loan or expensis in case of update');
            $table->tinyInteger('is_branch')->default(0)->comment('if checked then that means it is a duplicate transaction(meaning it a credit done by customer.)');

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('savings_account_id')->references('id')->on('savings_accounts')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('transactions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
