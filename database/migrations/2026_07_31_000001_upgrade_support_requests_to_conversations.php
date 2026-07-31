<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_support_requests', function (Blueprint $table): void {
            $table->unsignedBigInteger('created_by')->nullable()->index()->after('branch_id');
            $table->unsignedBigInteger('recipient_user_id')->nullable()->index()->after('created_by');
            $table->string('conversation_type', 30)->default('customer_admin')->index()->after('recipient_user_id');
            $table->string('priority', 20)->default('normal')->index()->after('category');
            $table->timestamp('last_message_at')->nullable()->index()->after('resolved_at');
        });

        Schema::create('support_request_messages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('support_request_id')->index();
            $table->unsignedBigInteger('sender_id')->nullable()->index();
            $table->text('message');
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });

        DB::table('customer_support_requests')->orderBy('id')->chunkById(200, function ($requests): void {
            foreach ($requests as $request) {
                $createdAt = $request->created_at ?: now();

                DB::table('customer_support_requests')->where('id', $request->id)->update([
                    'created_by' => $request->user_id,
                    'conversation_type' => 'customer_admin',
                    'last_message_at' => $request->updated_at ?: $createdAt,
                ]);

                DB::table('support_request_messages')->insert([
                    'support_request_id' => $request->id,
                    'sender_id' => $request->user_id,
                    'message' => $request->message,
                    'read_at' => $request->admin_response ? $request->updated_at : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                if ($request->admin_response) {
                    DB::table('support_request_messages')->insert([
                        'support_request_id' => $request->id,
                        'sender_id' => null,
                        'message' => $request->admin_response,
                        'read_at' => null,
                        'created_at' => $request->updated_at ?: $createdAt,
                        'updated_at' => $request->updated_at ?: $createdAt,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_request_messages');
        Schema::table('customer_support_requests', function (Blueprint $table): void {
            $table->dropColumn(['created_by', 'recipient_user_id', 'conversation_type', 'priority', 'last_message_at']);
        });
    }
};
