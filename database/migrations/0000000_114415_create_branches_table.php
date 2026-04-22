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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);  // Name of the branch
            $table->string('id_prefix', 255)->nullable();  // ID prefix (nullable) for loan
            $table->string('number_count', 255)->nullable();  // Number count (nullable) for member IDs            
            $table->string('prefix', 255)->nullable();  // Prefix (nullable) for member IDs            
            $table->string('loan_count', 255)->nullable();  // Loan count (nullable)
            $table->string('registration_number', 200)->nullable();  // Registration number (nullable)
            $table->string('year_of_registration', 200)->nullable();  // Year of registration (nullable)
            $table->string('contact_email', 191)->nullable();  // Contact email (nullable)
            $table->string('contact_phone', 191)->nullable();  // Contact phone (nullable)
            $table->text('address')->nullable();  // Address (nullable)
            $table->text('descriptions')->nullable();  // Descriptions (nullable)
            $table->text('photo')->nullable();  // Photo (nullable)
            $table->softDeletes();  // deleted_at (for soft deletes)
            $table->string('signature', 191)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
