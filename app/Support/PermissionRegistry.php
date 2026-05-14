<?php

namespace App\Support;

class PermissionRegistry
{
    public static function definitions(): array
    {
        return [
            'Dashboard' => [
                'dashboard.view' => 'View dashboard',
                'dashboard.manage' => 'Manage dashboard features',
            ],
            'Branches' => [
                'branches.view' => 'View branches and switch active branch',
                'branches.manage' => 'Create, edit, and remove branches',
            ],
            'Accounts' => [
                'accounts.view' => 'View accounts and account types',
                'accounts.manage' => 'Manage accounts and account types',
            ],
            'Members' => [
                'members.view' => 'View members and member custom fields',
                'members.manage' => 'Create, edit, archive members and custom fields',
            ],
            'Blog' => [
                'blog.view' => 'View blog posts in the admin panel',
                'blog.manage' => 'Create, edit, publish, and delete blog posts',
            ],
            'Loans' => [
                'loans.view' => 'View loans and loan custom fields',
                'loans.manage' => 'Create, approve, edit, and delete loans',
            ],
            'Loan Repayments' => [
                'loan-payments.view' => 'View loan repayments',
                'loan-payments.manage' => 'Create, edit, and delete loan repayments',
            ],
            'Transactions' => [
                'transactions.view' => 'View transactions and categories',
                'transactions.manage' => 'Create, edit, and delete transactions and categories',
            ],
            'Income & Expenses' => [
                'income-expenses.view' => 'View income, expenses, and categories',
                'income-expenses.manage' => 'Create, edit, and delete income, expenses, and categories',
            ],
            'Reports' => [
                'reports.view' => 'View and export reports',
                'reports.manage' => 'Manage report access',
            ],
            'Bulk SMS' => [
                'sms.view' => 'View SMS settings, templates, campaigns, and logs',
                'sms.manage' => 'Manage SMS settings, templates, campaigns, and automations',
            ],
            'User Management' => [
                'users.view' => 'View staff users',
                'users.manage' => 'Create, edit, and archive staff users',
                'roles.view' => 'View roles',
                'roles.manage' => 'Create, edit, and delete roles',
            ],
        ];
    }

    public static function all(): array
    {
        return collect(self::definitions())
            ->flatMap(fn (array $group) => array_keys($group))
            ->values()
            ->all();
    }

    public static function labels(): array
    {
        return collect(self::definitions())->collapse()->all();
    }
}
