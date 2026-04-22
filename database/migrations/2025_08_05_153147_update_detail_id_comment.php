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
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('detail_id', 191)
                ->nullable()
                ->comment('Store ID or either loan or expenses in case of update. Also used to store branch ID if it is a branch account.')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('detail_id', 191)
                ->nullable()
                ->comment('Store ID or either loan or expenses in case of update')
                ->change();
        });
    }
};
