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
        Schema::create('user_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('branch_id')->unsigned()->nullable();  // Foreign key to branches (nullable)
            $table->bigInteger('user_id')->unsigned()->nullable();  // Foreign key to users (nullable)
            $table->string('country_code', 10)->nullable();  // Country code (nullable)
            $table->string('mobile', 50)->nullable();  // Mobile (nullable)
            $table->string('business_name', 100)->nullable();  // Business name (nullable)
            $table->string('member_no', 50)->nullable();  // Member number (nullable)
            $table->string('gender', 10)->nullable();  // Gender (nullable)
            $table->string('city', 191)->nullable();  // City (nullable)
            $table->string('state', 191)->nullable();  // State (nullable)
            $table->string('zip', 50)->nullable();  // ZIP code (nullable)
            $table->text('address')->nullable();  // Address (nullable)
            $table->string('credit_source', 191)->nullable();  // Credit source (nullable)
            $table->text('custom_fields')->nullable();  // Custom fields (nullable)
            // Add foreign key constraints if necessary (assuming relationships with `branches` and `users` tables)
              $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_details');
    }
};
