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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name', 3);  // Currency name (e.g., 'NGN', 'USD')
            $table->decimal('exchange_rate', 10, 6);  // Exchange rate with 6 decimal precision
            $table->tinyInteger('base_currency')->default(0);  // Indicates if it's the base currency (0 or 1)
            $table->tinyInteger('status')->default(1);  // Currency status (active or inactive)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
