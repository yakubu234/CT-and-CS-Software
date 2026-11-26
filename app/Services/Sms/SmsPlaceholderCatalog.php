<?php

namespace App\Services\Sms;

use App\Models\SmsAutomationRule;

/**
 * Single list of every SMS placeholder: which event provides it, what it means and a
 * sample value. Used by the template form, the test page and the template checker.
 */
class SmsPlaceholderCatalog
{
    /** The four member account types, keyed by the placeholder prefix. */
    public const ACCOUNT_TYPES = [
        'savings' => 'SAVINGS',
        'shares' => 'SHARES',
        'authentication' => 'AUTHENTICATION',
        'deposit' => 'DEPOSIT',
    ];

    public const PARENT_SOCIETY_NAME = 'Oreoluwapo CT&CU';

    public function eventLabels(): array
    {
        return [
            SmsAutomationRule::EVENT_TRANSACTION_CREDIT => 'Credit Alert',
            SmsAutomationRule::EVENT_TRANSACTION_DEBIT => 'Debit Alert',
            SmsAutomationRule::EVENT_LOAN_REPAYMENT => 'Loan Repayment Alert',
            SmsAutomationRule::EVENT_LOAN_APPROVED => 'Loan Approved Alert',
            SmsAutomationRule::EVENT_BIRTHDAY => 'Birthday Greeting',
            SmsAutomationRule::EVENT_MONTHLY_STATEMENT => 'Monthly Statement / Balance Alert',
            'manual' => 'Manual Campaign / Announcement',
        ];
    }

    /**
     * Placeholders available to every message.
     *
     * @return array<string, array{0: string, 1: string}> name => [description, sample]
     */
    public function common(): array
    {
        return [
            'society_name' => ["The member's affiliated society (the society the transaction or message is for)", 'ASEOLUWA CTCS ILARO LTD'],
            'branch_name' => ['Same as society_name', 'ASEOLUWA CTCS ILARO LTD'],
            'parent_society_name' => ['The parent union name', self::PARENT_SOCIETY_NAME],
            'member_name' => ["Member's full name", 'ANIWURA AJOKE'],
            'first_name' => ["Member's first name", 'ANIWURA'],
            'last_name' => ["Member's last name", 'AJOKE'],
            'member_id' => ["Member's number in that society", 'ASEOLU_0123'],
            'member_no' => ['Same as member_id', 'ASEOLU_0123'],
        ];
    }

    /** Balance of each of the four accounts plus the total, after the event. */
    public function balances(): array
    {
        $placeholders = [];
        foreach (self::ACCOUNT_TYPES as $key => $type) {
            $placeholders[$key . '_balance'] = [ucfirst(strtolower($type)) . ' account balance', '25,000.00'];
        }
        $placeholders['total_member_balance'] = ['Total of all four account balances', '100,000.00'];
        $placeholders['current_balance'] = ['Same as total_member_balance', '100,000.00'];
        $placeholders['balance_summary'] = ['Every account with its balance', 'Savings 25,000.00, Shares 25,000.00, Authentication 25,000.00, Deposit 25,000.00'];

        return $placeholders;
    }

    /**
     * Placeholders for one event (common ones included).
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public function forEvent(?string $event): array
    {
        $specific = match ($event) {
            SmsAutomationRule::EVENT_TRANSACTION_CREDIT => $this->transaction('credited'),
            SmsAutomationRule::EVENT_TRANSACTION_DEBIT => $this->transaction('debited'),
            SmsAutomationRule::EVENT_LOAN_REPAYMENT => [
                'principal_paid' => ['Principal paid in this repayment', '20,000.00'],
                'interest_paid' => ['Interest paid in this repayment', '2,000.00'],
                'total_repayment' => ['Principal + interest paid', '22,000.00'],
                'outstanding_loan_balance' => ['Loan balance left after this repayment', '80,000.00'],
                'outstanding_interest' => ['Interest still owed after this repayment', '0.00'],
                'loan_id' => ['Loan reference', 'LN-00045'],
                'loan_amount' => ['Original loan amount', '100,000.00'],
                'transaction_date' => ['Repayment date', '22 Nov 2026'],
            ],
            SmsAutomationRule::EVENT_LOAN_APPROVED => [
                'loan_id' => ['Loan reference', 'LN-00045'],
                'loan_amount' => ['Approved loan amount', '100,000.00'],
                'release_date' => ['Release date', '22 Nov 2026'],
                'due_date' => ['Due date', '22 Nov 2027'],
            ],
            SmsAutomationRule::EVENT_BIRTHDAY => [
                'birth_day' => ['Birthday (day and month)', '22 Nov'],
            ],
            SmsAutomationRule::EVENT_MONTHLY_STATEMENT => $this->statement(),
            'manual' => [
                'month_label' => ['Current month', 'November 2026'],
                'statement_summary' => ['Every account with its balance', 'Savings 25,000.00, Shares 25,000.00, Authentication 25,000.00, Deposit 25,000.00'],
                ...$this->balances(),
            ],
            default => [],
        };

        return [...$this->common(), ...$specific];
    }

    /**
     * Monthly statement: figures for the statement month, worked out from the member's
     * transactions and loans as at month-end (not the balance on the day it is sent).
     * "Savings" totals cover all four member accounts together.
     */
    protected function statement(): array
    {
        $placeholders = [
            'statement_month' => ['Statement month (the last full month before sending)', 'October 2026'],
            'month_label' => ['Same as statement_month', 'October 2026'],
            'statement_period' => ['First and last day of the statement month', '01 Oct 2026 - 31 Oct 2026'],
            'opening_savings_balance' => ['All four accounts combined, at the start of the month', '95,000.00'],
            'total_savings_deposits' => ['All money paid into the four accounts during the month', '7,000.00'],
            'total_savings_withdrawals' => ['All money taken out of the four accounts during the month', '2,000.00'],
            'closing_savings_balance' => ['All four accounts combined, at month-end', '100,000.00'],
            'total_loan_balance' => ['Outstanding loan balance at month-end', '80,000.00'],
            'total_principal_paid' => ['Loan principal repaid during the month', '20,000.00'],
            'total_interest_paid' => ['Loan interest paid during the month', '2,000.00'],
            'net_balance' => ['closing_savings_balance minus total_loan_balance', '20,000.00'],
            'transaction_count' => ['Number of account transactions and loan repayments in the month', '5'],
        ];

        foreach (self::ACCOUNT_TYPES as $key => $type) {
            $label = ucfirst(strtolower($type));
            $placeholders[$key . '_opening_balance'] = [$label . ' balance at the start of the month', '25,000.00'];
            $placeholders[$key . '_deposits'] = ['Paid into ' . $label . ' during the month', '2,000.00'];
            $placeholders[$key . '_withdrawals'] = ['Taken out of ' . $label . ' during the month', '0.00'];
            $placeholders[$key . '_closing_balance'] = [$label . ' balance at month-end', '27,000.00'];
        }

        $balances = $this->balances();
        foreach (self::ACCOUNT_TYPES as $key => $type) {
            $balances[$key . '_balance'][0] .= ' at month-end';
        }
        $balances['total_member_balance'][0] = 'Same as closing_savings_balance';
        $balances['current_balance'][0] = 'Same as closing_savings_balance';
        $balances['balance_summary'][0] = 'Every account with its month-end balance';

        return [
            ...$placeholders,
            ...$balances,
            'statement_summary' => ['Same as balance_summary', 'Savings 25,000.00, Shares 25,000.00, Authentication 25,000.00, Deposit 25,000.00'],
            'statement_compact' => ['Short month-end account summary', 'Sav 25,000.00 | Shr 25,000.00 | Auth 25,000.00 | Dep 25,000.00'],
            'statement_total_balance' => ['Same as closing_savings_balance', '100,000.00'],
        ];
    }

    protected function transaction(string $verb): array
    {
        $placeholders = [
            'total_' . $verb => ['Total amount ' . $verb . ' in this posting (all accounts)', '7,000.00'],
            $verb . '_account_details' => ['Each account ' . $verb . ' with its amount', 'Savings 5,000.00, Shares 2,000.00'],
            'amount' => ['Same as total_' . $verb, '7,000.00'],
            'account_type' => ['Account type(s) ' . $verb, 'Savings, Shares'],
            'account_number' => ['Account number(s) ' . $verb, '1000123, 1000124'],
            'transaction_type' => ['CR or DR', $verb === 'credited' ? 'CR' : 'DR'],
            'transaction_date' => ['Posting date', '22 Nov 2026'],
            $verb . '_account_balances' => ['New balance of each account ' . $verb, 'Savings 55,000.00, Shares 32,000.00'],
        ];

        foreach (self::ACCOUNT_TYPES as $key => $type) {
            $placeholders[$key . '_' . $verb] = ['Amount ' . $verb . ' to ' . ucfirst(strtolower($type)) . ' (0.00 if none)', $key === 'savings' ? '5,000.00' : ($key === 'shares' ? '2,000.00' : '0.00')];
        }

        return [...$placeholders, ...$this->balances()];
    }

    /** Every placeholder name known for any event. */
    public function allNames(): array
    {
        $names = [];
        foreach (array_keys($this->eventLabels()) as $event) {
            $names = [...$names, ...array_keys($this->forEvent($event))];
        }

        return array_values(array_unique($names));
    }

    /**
     * Check a template body for problems before it is used.
     *
     * @return array{unknown: string[], other_event: string[], malformed: string[]}
     */
    public function analyze(string $body, ?string $event): array
    {
        preg_match_all('/{{\s*([a-zA-Z0-9_\.]+)\s*}}/', $body, $matches);
        $used = array_values(array_unique($matches[1]));
        $forEvent = array_keys($this->forEvent($event));
        $known = $this->allNames();

        // Strip valid placeholders, then look for leftover brace fragments like {name}} or {{name}.
        $remaining = preg_replace('/{{\s*[a-zA-Z0-9_\.]+\s*}}/', '', $body) ?? $body;
        preg_match_all('/\{+\s*[a-zA-Z0-9_\.]*\s*\}+|\{+\s*[a-zA-Z0-9_\.]+|[a-zA-Z0-9_\.]+\s*\}+/', $remaining, $broken);

        return [
            'unknown' => array_values(array_filter($used, fn ($name) => ! in_array($name, $known, true))),
            'other_event' => array_values(array_filter($used, fn ($name) => in_array($name, $known, true) && ! in_array($name, $forEvent, true))),
            'malformed' => array_values(array_unique(array_map('trim', $broken[0]))),
        ];
    }

    /** Sample values for an event, used when no real member data is available. */
    public function samples(?string $event): array
    {
        return array_map(fn ($entry) => $entry[1], $this->forEvent($event));
    }
}
