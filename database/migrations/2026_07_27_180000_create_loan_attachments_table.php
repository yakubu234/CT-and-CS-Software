<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_detail_id')->constrained('loan_details')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::table('loan_details')
            ->whereNotNull('attachment')
            ->where('attachment', '!=', '')
            ->orderBy('id')
            ->chunkById(500, function ($details): void {
                $rows = [];
                $now = now();

                foreach ($details as $detail) {
                    $rows[] = [
                        'loan_detail_id' => $detail->id,
                        'path' => $detail->attachment,
                        'original_name' => basename($detail->attachment),
                        'mime_type' => null,
                        'size' => null,
                        'uploaded_by' => $detail->created_user_id,
                        'created_at' => $detail->created_at ?? $now,
                        'updated_at' => $detail->updated_at ?? $now,
                    ];
                }

                if ($rows !== []) {
                    DB::table('loan_attachments')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_attachments');
    }
};
