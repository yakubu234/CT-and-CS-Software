<?php

namespace Database\Seeders;

use App\Models\AssetCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AssetCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Land and Buildings',
            'Office Furniture',
            'Computers and Laptops',
            'Printers',
            'Vehicles',
            'Generators',
            'Office Equipment',
            'Other Fixed Assets',
        ];

        foreach ($categories as $category) {
            AssetCategory::query()->updateOrCreate(
                ['slug' => Str::slug($category, '_')],
                [
                    'name' => $category,
                    'description' => null,
                    'status' => true,
                ]
            );
        }
    }
}
