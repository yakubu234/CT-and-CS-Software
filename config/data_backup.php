<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Exportable application modules
    |--------------------------------------------------------------------------
    |
    | Only business data is exposed here. Framework cache, session, queue and
    | password-reset tables are deliberately excluded.
    |
    */
    'modules' => [
        'branches' => [
            'label' => 'Branches',
            'tables' => ['branches'],
        ],
        'members' => [
            'label' => 'Members',
            'tables' => ['users', 'user_details', 'member_documents', 'document_types', 'custom_fields', 'designations'],
        ],
        'accounts' => [
            'label' => 'Savings Accounts',
            'tables' => ['savings_products', 'savings_accounts', 'currencies'],
        ],
        'transactions' => [
            'label' => 'Transactions & Income/Expenses',
            'tables' => ['transaction_categories', 'transactions'],
        ],
        'loans' => [
            'label' => 'Loans & Repayments',
            'tables' => ['loans', 'loan_details', 'loan_payments', 'loan_attachments', 'interests'],
        ],
        'assets' => [
            'label' => 'Assets',
            'tables' => ['asset_categories', 'assets'],
        ],
        'communications' => [
            'label' => 'Email & SMS',
            'tables' => [
                'email_templates', 'email_campaigns', 'email_messages', 'email_smtp_accounts', 'email_preferences',
                'sms_templates', 'sms_campaigns', 'sms_messages', 'sms_automation_rules',
            ],
        ],
        'content_support' => [
            'label' => 'Blog & Customer Support',
            'tables' => ['blog_posts', 'customer_support_requests', 'support_request_messages'],
        ],
        'administration' => [
            'label' => 'Roles & Application Settings',
            'tables' => ['roles', 'settings'],
        ],
    ],

    'excluded_columns' => [
        'users' => ['password', 'remember_token'],
        'email_smtp_accounts' => ['password'],
        'settings' => ['value'],
    ],

    'storage_disk' => 'local',
    'storage_directory' => 'backups',
    'credentials_path' => 'backup-secrets/google-service-account.json',
];
