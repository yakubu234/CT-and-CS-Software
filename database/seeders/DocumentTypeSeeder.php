<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
           $documentTypes = [
            ['id' => 1, 'name' => 'NIN', 'status' => 'ENABLED', 'created_at' => '2024-10-07 16:26:35'],
            ['id' => 2, 'name' => 'MEMBERSHIP FORM', 'status' => 'ENABLED', 'created_at' => '2024-10-07 16:26:35'],
            ['id' => 3, 'name' => 'LOAN FORM', 'status' => 'ENABLED', 'created_at' => '2024-10-07 16:29:02'],
            ['id' => 4, 'name' => 'NEXT OF KIN FORM', 'status' => 'ENABLED', 'created_at' => '2024-10-07 16:29:02'],
        ];

        foreach ($documentTypes as $type) {
            DocumentType::updateOrCreate(
                ['id' => $type['id']],
                $type
            );
        }
    }
}
