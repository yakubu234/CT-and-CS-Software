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
                'body' => '<p>Dear {{member_name}},</p><p>Your membership registration with {{society_name}} has been completed successfully.</p><p><strong>Member No:</strong> {{member_no}}<br><strong>Branch:</strong> {{branch_name}}</p><p><a href="{{reset_url}}">Set your portal password</a></p><p>Thank you.</p>',
            ],
            [
                'name' => 'Loan Application Update',
                'slug' => 'loan-application-update',
                'category' => 'loan_updates',
                'description' => 'Sent when a member loan is approved.',
                'subject' => 'Your loan {{reference_code}} has been approved',
                'body' => '<p>Dear {{member_name}},</p><p>Your loan application {{reference_code}} for ₦{{loan_amount}} has been approved.</p><p>Please contact your branch, {{branch_name}}, for disbursement details.</p>',
            ],
            [
                'name' => 'Repayment Reminder',
                'slug' => 'repayment-reminder',
                'category' => 'repayment_reminders',
                'description' => 'Sent three days before an approved loan is due when a balance remains.',
                'subject' => 'Loan repayment reminder',
                'body' => '<p>Dear {{member_name}},</p><p>Your loan {{reference_code}} has a repayment due on {{due_date}}. Please review your repayment obligation with {{society_name}}.</p><p>Branch: {{branch_name}}</p>',
            ],
            [
                'name' => 'Account Verification',
                'slug' => 'account-verification',
                'category' => 'account_verification',
                'description' => 'Account verification and official confirmation email.',
                'subject' => 'Account verification for {{society_name}}',
                'body' => '<p>Dear {{member_name}},</p><p>Please verify your registered email address: <a href="{{verification_url}}">Verify email address</a>.</p><p>If you did not request this, please contact {{branch_name}}.</p>',
            ],
            [
                'name' => 'General Official Notice',
                'slug' => 'general-official-notice',
                'category' => 'general_notice',
                'description' => 'Reusable template for official society communications.',
                'subject' => 'Official notice from {{society_name}}',
                'body' => '<p>Dear {{member_name}},</p><p>This is an official communication from {{society_name}}.</p><p>Regards,<br>{{branch_name}}</p>',
            ],
            [
                'name' => 'Member Password Reset',
                'slug' => 'member-password-reset',
                'category' => 'password_resets',
                'description' => 'Secure link for a member who requests a password reset.',
                'subject' => 'Reset your {{society_name}} password',
                'body' => '<p>Dear {{member_name}},</p><p>Use this link to reset your password: <a href="{{reset_url}}">Reset password</a>.</p><p>If you did not request this, ignore this email.</p>',
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::query()->firstOrCreate(
                ['slug' => $template['slug']],
                array_merge($template, ['status' => true])
            );
        }
    }
}
