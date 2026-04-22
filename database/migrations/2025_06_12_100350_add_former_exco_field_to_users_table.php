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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('date_added_as_exco')->nullable()->after('society_exco'); // Adding date_added to track when the user was added as an exco
            $table->boolean('former_exco')->default(false)->after('date_added_as_exco'); // Adding former_exco to track if the user was a former executive committee member
            $table->timestamp('date_removed_as_exco')->nullable()->after('former_exco'); // Adding date_removed to track when the user was removed as an exco
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
           $table->dropColumn(['date_added_as_exco']);
           $table->dropColumn(['former_exco']);
           $table->dropColumn(['date_removed_as_exco']);
        });
    }
};
