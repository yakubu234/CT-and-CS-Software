<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DesignationSeeder extends Seeder
{
    public function run(): void
    {
        $designations = [
            'President',
            'Vice President',
            'Vice President 1',
            'Vice President 2',
            'Secretary',
            'Secretrary',
            'Assistant Secretary',
            'Treasurer',
            'Financial Secretary',
            'Auditor',
            'PRO',
            'Public Relations Officer',
            'Welfare Officer',
            'Provost',
            'Legal Adviser',
            'Committee 1',
            'Committee 2',
            'Committee 3',
            'Chief Whip',
            'Staff',
            'Member',
        ];

        foreach ($designations as $index => $designation) {
            DB::table('designations')->updateOrInsert(
                ['slug' => Str::slug($designation)],
                [
                    'name' => $designation,
                    'slug' => Str::slug($designation),
                    'status' => 1,
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
