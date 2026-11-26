<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('sms_automation_rules', 'send_mode')) {
            return;
        }

        // Existing rules keep sending by themselves, exactly as before.
        Schema::table('sms_automation_rules', function (Blueprint $table): void {
            $table->string('send_mode', 20)->default('automatic')->after('status');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('sms_automation_rules', 'send_mode')) {
            Schema::table('sms_automation_rules', function (Blueprint $table): void {
                $table->dropColumn('send_mode');
            });
        }
    }
};
