<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('transactions')
            ->update(['tracking_id' => 'regular']);

        Schema::table('transactions', function (Blueprint $table) {

            // Modify the column
            $table->enum('tracking_id', ['income', 'expenses', 'loan', 'loan_repayment', 'regular'])
                ->default('regular')
                ->comment('Used to track the type of transaction for easy access and filtering')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('tracking_id')->default(null)->comment(null)->change();
        });
    }
};
