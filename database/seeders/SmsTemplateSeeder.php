<?php

namespace Database\Seeders;

use App\Models\SmsTemplate;
use Illuminate\Database\Seeder;

class SmsTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Holiday Greeting',
                'slug' => 'holiday-greeting',
                'category' => 'holiday',
                'description' => 'For festive greetings across a branch or selected members.',
                'body' => 'Happy celebration from {{society_name}}, {{member_name}}. Wishing you peace, joy, and prosperity from {{branch_name}} branch.',
            ],
            [
                'name' => 'Birthday Greeting',
                'slug' => 'birthday-greeting',
                'category' => 'birthday',
                'description' => 'Sent automatically on member birthdays.',
                'body' => 'Happy birthday, {{member_name}}. {{society_name}} celebrates you today and wishes you a fruitful new year ahead.',
            ],
            [
                'name' => 'Celebration Announcement',
                'slug' => 'celebration-announcement',
                'category' => 'celebration',
                'description' => 'General congratulatory or event message.',
                'body' => 'Dear {{member_name}}, this is to celebrate with you from {{society_name}}. Thank you for being part of {{branch_name}} branch.',
            ],
            [
                'name' => 'Member Credited Alert',
                'slug' => 'member-credited-alert',
                'category' => 'transaction_credit',
                'description' => 'For account credit transactions.',
                'body' => 'CR Alert: {{member_name}} your {{account_type}} account {{account_number}} was credited with NGN {{amount}} on {{transaction_date}}. New balance: NGN {{current_balance}}.',
            ],
            [
                'name' => 'Member Debited Alert',
                'slug' => 'member-debited-alert',
                'category' => 'transaction_debit',
                'description' => 'For account debit transactions.',
                'body' => 'DR Alert: {{member_name}} your {{account_type}} account {{account_number}} was debited by NGN {{amount}} on {{transaction_date}}. Balance: NGN {{current_balance}}.',
            ],
            [
                'name' => 'Loan Approved Alert',
                'slug' => 'loan-approved-alert',
                'category' => 'loan_approved',
                'description' => 'Sent when a pending loan gets approved.',
                'body' => 'Loan Approved: {{member_name}}, your loan {{loan_id}} of NGN {{loan_amount}} has been approved. Release: {{release_date}}. Due: {{due_date}}.',
            ],
            [
                'name' => 'Monthly Statement Compact',
                'slug' => 'monthly-statement-compact',
                'category' => 'monthly_statement',
                'description' => 'Compact SMS statement summary for month-end or scheduled monthly notifications.',
                'body' => '{{society_name}} {{month_label}} statement for {{member_name}} ({{member_no}}): {{statement_compact}}. Total: NGN {{statement_total_balance}}.',
            ],
            [
                'name' => 'Manual Bulk SMS',
                'slug' => 'manual-bulk-sms',
                'category' => 'manual',
                'description' => 'Starter template for general branch communication.',
                'body' => 'Dear {{member_name}}, this is an important update from {{society_name}} {{branch_name}} branch.',
            ],
        ];

        foreach ($templates as $template) {
            SmsTemplate::query()->updateOrCreate(
                ['slug' => $template['slug']],
                [
                    'name' => $template['name'],
                    'category' => $template['category'],
                    'description' => $template['description'],
                    'body' => $template['body'],
                    'status' => true,
                    'created_by' => null,
                    'updated_by' => null,
                ]
            );
        }
    }
}
