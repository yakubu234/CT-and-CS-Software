<?php

namespace Database\Seeders;

use App\Models\CustomField;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomFieldsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $fields = [
            [
                'field_name' => 'Account Number',
                'field_type' => 'number',
                'default_value' => null,
                'field_width' => 'col-lg-6',
                'max_size' => 2,
                'is_required' => 'nullable',
                'table' => 'users',
                'allow_for_signup' => 0,
                'allow_to_list_view' => 0,
                'status' => 1,
                'order' => 0,
                'created_at' => Carbon::parse('2024-09-17 03:42:12'),
                'updated_at' => Carbon::parse('2024-10-08 16:24:46'),
            ],
            [
                'field_name' => 'Account Name',
                'field_type' => 'text',
                'default_value' => null,
                'field_width' => 'col-lg-6',
                'max_size' => 2,
                'is_required' => 'nullable',
                'table' => 'users',
                'allow_for_signup' => 0,
                'allow_to_list_view' => 0,
                'status' => 1,
                'order' => 0,
                'created_at' => Carbon::parse('2024-10-03 01:26:15'),
                'updated_at' => Carbon::parse('2024-10-08 16:24:34'),
            ],
            [
                'field_name' => 'Bank Name',
                'field_type' => 'text',
                'default_value' => null,
                'field_width' => 'col-lg-6',
                'max_size' => 2,
                'is_required' => 'nullable',
                'table' => 'users',
                'allow_for_signup' => 0,
                'allow_to_list_view' => 0,
                'status' => 1,
                'order' => 0,
                'created_at' => Carbon::parse('2024-10-03 16:33:48'),
                'updated_at' => Carbon::parse('2024-10-08 16:25:01'),
            ]
            ];

            foreach ($fields as $field) {
            CustomField::updateOrCreate(
                ['field_name' => $field['field_name']], // Match by `id`, you can use ['name' => $role['name']] instead
                [
                    'field_name' => $field['field_name'],
                    'field_type' => $field['field_type'],
                    'default_value' => $field['default_value'],
                    'field_width' => $field['field_width'],
                    'max_size' => $field['max_size'],
                    'is_required' => $field['is_required'],
                    'table' => $field['table'],
                    'allow_for_signup' => $field['allow_for_signup'],
                    'allow_to_list_view' => $field['allow_to_list_view'],
                    'status' => $field['status'],
                    'order' => $field['order'],
                    'created_at' => $field['created_at'],
                    'updated_at' => $field['updated_at'],
                ]
            );
        }
    }
}
