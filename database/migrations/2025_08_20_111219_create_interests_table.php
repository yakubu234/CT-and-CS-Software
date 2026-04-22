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
        Schema::create('interests', function (Blueprint $table) {
            $table->id();
            $table->string('interest', 191)->nullable();
            $table->string('interest_rate', 191)->nullable();
            $table->string('amount_paid', 191)->nullable();
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_coppied')->default(false);
            $table->unsignedBigInteger('loan_id');
            $table->timestamps();

            // index and foreign key
            $table->foreign('loan_id')
                  ->references('id')
                  ->on('loans')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interests');
    }
};
