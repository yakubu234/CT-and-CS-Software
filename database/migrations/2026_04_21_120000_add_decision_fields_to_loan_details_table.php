<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_details', function (Blueprint $table) {
            $table->string('decision_status', 20)
                ->default('pending')
                ->after('repayment_status');
            $table->string('attachment')
                ->nullable()
                ->after('decision_status');
            $table->timestamp('approved_at')
                ->nullable()
                ->after('attachment');
            $table->unsignedBigInteger('approved_by')
                ->nullable()
                ->after('approved_at');
            $table->timestamp('declined_at')
                ->nullable()
                ->after('approved_by');
            $table->unsignedBigInteger('declined_by')
                ->nullable()
                ->after('declined_at');
            $table->text('decline_reason')
                ->nullable()
                ->after('declined_by');

            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('declined_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('loan_details', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['declined_by']);
            $table->dropColumn([
                'decision_status',
                'attachment',
                'approved_at',
                'approved_by',
                'declined_at',
                'declined_by',
                'decline_reason',
            ]);
        });
    }
};
