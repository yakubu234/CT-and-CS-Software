<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            $table->string('occupation', 191)->nullable()->after('member_no');
            $table->string('account_number', 191)->nullable()->after('address');
            $table->string('account_name', 191)->nullable()->after('account_number');
            $table->string('bank_name', 191)->nullable()->after('account_name');
        });
    }

    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            $table->dropColumn([
                'occupation',
                'account_number',
                'account_name',
                'bank_name',
            ]);
        });
    }
};
