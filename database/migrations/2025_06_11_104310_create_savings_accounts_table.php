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
        Schema::create('savings_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_number', 30);  // Account number field
            $table->bigInteger('user_id')->unsigned();  // Foreign key to members
            $table->bigInteger('savings_product_id')->unsigned();  // Foreign key to savings_products
            $table->integer('status')->comment('1 = active | 0 = active');
            $table->decimal('opening_balance', 10, 2);  // Opening balance
            $table->text('description')->nullable();  // Description (optional)
            $table->bigInteger('created_user_id')->nullable();  // ID of user who created the record
            $table->bigInteger('updated_user_id')->nullable();  // ID of user who updated the record
            $table->tinyInteger('is_branch_acount')->default(0);  // Whether it's a branch account (default 0)
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('savings_product_id')->references('id')->on('savings_products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('savings_accounts');
    }
};
