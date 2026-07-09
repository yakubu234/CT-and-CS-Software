<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Member Registration Confirmation',
                'slug' => 'member-registration-confirmation',
                'category' => 'member_registration',
                'description' => 'Welcome email for newly registered cooperative members.',
                'subject' => 'Welcome to {{society_name}}, {{first_name}}',
                'body' => '<p>Dear {{member_name}},</p><p>Your membership registration with {{society_name}} has been completed successfully.</p><p><strong>Member No:</strong> {{member_no}}<br><strong>Branch:</strong> {{branch_name}}</p><p>Thank you.</p>',
            ],
            [
                'name' => 'Loan Application Update',
                'slug' => 'loan-application-update',
                'category' => 'loan_updates',
                'description' => 'General loan application or approval update.',
                'subject' => 'Loan application update from {{society_name}}',
                'body' => '<p>Dear {{member_name}},</p><p>This is an update about your loan application with {{society_name}}.</p><p>Please contact your branch, {{branch_name}}, if you need more information.</p>',
            ],
            [
                'name' => 'Repayment Reminder',
                'slug' => 'repayment-reminder',
                'category' => 'repayment_reminders',
                'description' => 'Reminder for upcoming or overdue loan repayments.',
                'subject' => 'Loan repayment reminder',
                'body' => '<p>Dear {{member_name}},</p><p>This is a reminder to review your loan repayment obligation with {{society_name}}.</p><p>Branch: {{branch_name}}</p>',
            ],
            [
                'name' => 'Account Verification',
                'slug' => 'account-verification',
                'category' => 'account_verification',
                'description' => 'Account verification and official confirmation email.',
                'subject' => 'Account verification for {{society_name}}',
                'body' => '<p>Dear {{member_name}},</p><p>Your account verification reference is <strong>{{reference_code}}</strong>.</p><p>If you did not request this, please contact {{branch_name}}.</p>',
            ],
            [
                'name' => 'General Official Notice',
                'slug' => 'general-official-notice',
                'category' => 'general_notice',
                'description' => 'Reusable template for official society communications.',
                'subject' => 'Official notice from {{society_name}}',
                'body' => '<p>Dear {{member_name}},</p><p>This is an official communication from {{society_name}}.</p><p>Regards,<br>{{branch_name}}</p>',
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::query()->updateOrCreate(
                ['slug' => $template['slug']],
                array_merge($template, ['status' => true])
            );
        }
    }
}
