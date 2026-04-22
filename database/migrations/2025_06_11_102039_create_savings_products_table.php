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
        Schema::create('savings_products', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
             $table->string('name', 191);
            $table->string('account_number_prefix', 10)->nullable();
            $table->bigInteger('starting_account_number')->nullable();
            $table->bigInteger('currency_id')->unsigned();  // Foreign key to currency
            $table->decimal('interest_rate', 8, 2)->nullable();
            $table->string('interest_method', 30)->nullable();
            $table->integer('interest_period')->nullable();
            $table->integer('interest_posting_period')->nullable();
            $table->decimal('min_bal_interest_rate', 10, 2)->nullable();
            $table->tinyInteger('allow_withdraw')->default(1);
            $table->decimal('minimum_account_balance', 10, 2)->default(0.00);
            $table->decimal('minimum_deposit_amount', 10, 2)->default(0.00);
            $table->decimal('maintenance_fee', 10, 2)->default(0.00);
            $table->integer('maintenance_fee_posting_period')->nullable();
            $table->integer('status')->comment('1 = active | 2 = Deactivate');
            $table->tinyInteger('default_account')->default(0);
            $table->enum('type', ['SAVINGS', 'SHARES', 'AUTHENTICATION', 'DEPOSIT'])->default('SAVINGS');

            // Add foreign key constraint
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('savings_products');
    }
};
