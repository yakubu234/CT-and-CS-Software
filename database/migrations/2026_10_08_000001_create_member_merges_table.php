<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_merges', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('canonical_user_id')->index();
            $table->unsignedBigInteger('merged_user_id')->index();
            $table->unsignedBigInteger('primary_membership_id')->index();
            $table->unsignedBigInteger('merged_by')->nullable()->index();
            $table->string('selected_email');
            $table->string('selected_mobile', 50)->nullable();
            $table->json('canonical_snapshot');
            $table->json('merged_snapshot');
            $table->json('records_moved')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_merges');
    }
};
