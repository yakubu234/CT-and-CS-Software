<?php

namespace Tests\Feature;

use App\Http\Controllers\SmsTestController;
use App\Models\LoanPayment;
use App\Models\SmsAutomationRule;
use App\Models\SmsMessage;
use App\Models\Transaction;
use App\Services\Sms\SmsAutomationService;
use App\Services\Sms\SmsCampaignService;
use App\Services\Sms\SmsDispatchService;
use App\Services\Sms\SmsPlaceholderCatalog;
use App\Services\Sms\SmsSettingsService;
use App\Models\Branch;
use App\Models\SmsCampaign;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Credit/debit/loan repayment alerts, manual sending, duplicate protection, the test
 * page's preview data and campaign society names, against a minimal in-memory schema.
 */
class SmsAlertsTest extends TestCase
{
    /** Message bodies as configured by management (loan one with its braces fixed). */
    private const CREDIT_TEMPLATE = 'CR Alert: {{society_name}} {{member_name}} {{member_id}} your {{credited_account_details}} was credited with NGN {{total_credited}} on {{transaction_date}}. Total Member Balance: NGN {{total_member_balance}}.';
    private const LOAN_TEMPLATE = 'Loan repayment: {{society_name}} {{member_name}} {{member_id}} PR:NGN {{principal_paid}} IP: {{interest_paid}} Total Amount paid: NGN {{total_repayment}} OLB: {{outstanding_loan_balance}} {{transaction_date}}. Thank you for payment.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();
        $this->seedData();

        // Never contact a provider: record dispatches and mark them sent.
        app()->instance(SmsDispatchService::class, new class extends SmsDispatchService {
            public array $dispatched = [];
            public function __construct() {}
            public function dispatch(SmsMessage $message): SmsMessage
            {
                $this->dispatched[] = $message->id;
                $message->update(['status' => SmsMessage::STATUS_SENT, 'sent_at' => now()]);

                return $message->fresh();
            }
        });
        app()->instance(SmsSettingsService::class, new class extends SmsSettingsService {
            public function __construct() {}
            public function activeProvider(): ?string { return 'fake'; }
            public function senderId(): ?string { return 'TEST'; }
        });
    }

    public function test_one_consolidated_credit_alert_per_posting_with_correct_society_and_balances(): void
    {
        $this->rule('transaction_credit', self::CREDIT_TEMPLATE);
        $transactions = $this->posting([['savings', 'cr', 5000], ['shares', 'cr', 2000]]);

        $service = app(SmsAutomationService::class);
        $service->handleTransactionBatch($transactions);

        $messages = SmsMessage::query()->get();
        $this->assertCount(1, $messages, 'Two credited accounts in one posting must give ONE alert.');
        $this->assertSame(
            'CR Alert: ASEOLUWA CTCS ILARO LTD ANIWURA AJOKE ASEOLU_0123 your Savings 5,000.00, Shares 2,000.00 was credited with NGN 7,000.00 on 22 Nov 2026. Total Member Balance: NGN 111,000.00.',
            $messages->first()->message
        );
        $this->assertSame(SmsMessage::STATUS_SENT, $messages->first()->status);

        // Re-triggering (per entry, or the whole batch again) never sends a duplicate.
        foreach ($transactions as $transaction) {
            $service->handleTransaction($transaction);
        }
        $service->handleTransactionBatch($transactions);
        $this->assertSame(1, SmsMessage::query()->count());
    }

    public function test_every_account_type_placeholder_maps_to_its_own_account(): void
    {
        $this->rule('transaction_credit', 'S{{savings_credited}}/{{savings_balance}} H{{shares_credited}}/{{shares_balance}} A{{authentication_credited}}/{{authentication_balance}} D{{deposit_credited}}/{{deposit_balance}} T{{total_credited}}/{{total_member_balance}} [{{account_type}}]');
        app(SmsAutomationService::class)->handleTransactionBatch(
            $this->posting([['authentication', 'cr', 300], ['deposit', 'cr', 400]])
        );

        // Balances are this society's accounts only (the 999,999 account in another society is excluded).
        $this->assertSame(
            'S0.00/50,000.00 H0.00/30,000.00 A300.00/20,000.00 D400.00/11,000.00 T700.00/111,000.00 [Authentication, Deposit]',
            SmsMessage::query()->value('message')
        );
    }

    public function test_debits_in_the_same_posting_get_their_own_debit_alert(): void
    {
        $this->rule('transaction_credit', 'CR {{total_credited}}');
        $this->rule('transaction_debit', 'DR {{total_debited}} {{debited_account_details}}');

        app(SmsAutomationService::class)->handleTransactionBatch(
            $this->posting([['savings', 'cr', 1000], ['deposit', 'dr', 250]])
        );

        $this->assertEqualsCanonicalizing(['CR 1,000.00', 'DR 250.00 Deposit 250.00'], SmsMessage::query()->pluck('message')->all());
    }

    public function test_manual_rule_prepares_the_alert_and_waits_for_staff(): void
    {
        $this->rule('transaction_credit', 'CR {{total_credited}}', SmsAutomationRule::SEND_MANUAL);
        $service = app(SmsAutomationService::class);
        $service->handleTransactionBatch($this->posting([['savings', 'cr', 1000]]));

        $message = SmsMessage::query()->sole();
        $this->assertSame(SmsMessage::STATUS_AWAITING, $message->status);
        $this->assertSame([], app(SmsDispatchService::class)->dispatched, 'Manual alerts must not be sent automatically.');

        $this->assertSame(SmsMessage::STATUS_SENT, $service->releaseAwaiting($message)->status);
    }

    public function test_inactive_rule_or_other_branch_rule_sends_nothing(): void
    {
        $this->rule('transaction_credit', 'CR', SmsAutomationRule::SEND_AUTOMATIC, ['status' => false]);
        $this->rule('transaction_credit', 'CR', SmsAutomationRule::SEND_AUTOMATIC, ['branch_id' => 20]);

        app(SmsAutomationService::class)->handleTransactionBatch($this->posting([['savings', 'cr', 1000]]));

        $this->assertSame(0, SmsMessage::query()->count());
    }

    public function test_loan_repayment_alert_uses_the_repayment_figures(): void
    {
        $this->rule('loan_repayment', self::LOAN_TEMPLATE);
        DB::table('loans')->insert(['id' => 1, 'loan_id' => 'LN-0045', 'borrower_id' => 1, 'branch_id' => 10, 'member_branch_membership_id' => 101, 'applied_amount' => 100000, 'balanace' => 80000]);
        DB::table('loan_payments')->insert(['id' => 7, 'loan_id' => 1, 'paid_at' => '2026-11-22', 'repayment_amount' => 20000, 'interest_paid' => 2000, 'total_amount' => 22000, 'balance' => 80000, 'outstanding_interest' => 0]);

        $service = app(SmsAutomationService::class);
        $service->handleLoanRepayment(LoanPayment::query()->find(7));
        $service->handleLoanRepayment(LoanPayment::query()->find(7));

        $this->assertSame(
            ['Loan repayment: ASEOLUWA CTCS ILARO LTD ANIWURA AJOKE ASEOLU_0123 PR:NGN 20,000.00 IP: 2,000.00 Total Amount paid: NGN 22,000.00 OLB: 80,000.00 22 Nov 2026. Thank you for payment.'],
            SmsMessage::query()->pluck('message')->all()
        );
    }

    public function test_monthly_statement_uses_month_end_figures_for_active_members_only(): void
    {
        $this->rule('monthly_statement', implode('|', [
            '{{society_name}}', '{{member_id}}', '{{statement_month}}', '{{opening_savings_balance}}', '{{total_savings_deposits}}',
            '{{total_savings_withdrawals}}', '{{closing_savings_balance}}', '{{total_loan_balance}}', '{{total_interest_paid}}',
            '{{net_balance}}', '{{transaction_count}}', '{{savings_opening_balance}}', '{{savings_closing_balance}}', '{{shares_closing_balance}}',
        ]), SmsAutomationRule::SEND_AUTOMATIC, ['schedule_time' => '08:00', 'day_of_month' => 1]);

        // Opening balances, then savings history: September (before), October (statement month), November (after).
        foreach ([201 => 40000, 202 => 30000, 203 => 20000, 204 => 11000] as $accountId => $opening) {
            DB::table('savings_accounts')->where('id', $accountId)->update(['opening_balance' => $opening]);
        }
        foreach ([['2026-09-15', 'cr', 5000], ['2026-10-05', 'cr', 7000], ['2026-10-20', 'dr', 2000], ['2026-11-02', 'cr', 999]] as [$date, $drCr, $amount]) {
            DB::table('transactions')->insert(['user_id' => 1, 'member_branch_membership_id' => 101, 'savings_account_id' => 201, 'trans_date' => $date, 'amount' => $amount, 'dr_cr' => $drCr, 'branch_id' => 10, 'is_branch' => 0, 'tracking_id' => 'regular']);
        }
        // A deleted transaction and a branch mirror never count.
        DB::table('transactions')->insert(['user_id' => 1, 'savings_account_id' => 201, 'trans_date' => '2026-10-10', 'amount' => 50000, 'dr_cr' => 'cr', 'branch_id' => 10, 'is_branch' => 0, 'tracking_id' => 'regular', 'deleted_at' => now()]);

        // Loan of 100,000 released in September; principal repaid 10,000 in Sep, 10,000 (+1,500 interest) in Oct, 5,000 in Nov.
        DB::table('loans')->insert(['id' => 1, 'loan_id' => 'LN-1', 'borrower_id' => 1, 'branch_id' => 10, 'member_branch_membership_id' => 101, 'applied_amount' => 100000, 'balanace' => 75000]);
        DB::table('loan_details')->insert(['id' => 1, 'loan_id' => 1, 'applied_amount' => 100000, 'release_date' => '2026-09-01', 'decision_status' => 'approved']);
        foreach ([['2026-09-30', 10000, 0], ['2026-10-31', 10000, 1500], ['2026-11-15', 5000, 500]] as [$paid, $principal, $interest]) {
            DB::table('loan_payments')->insert(['loan_id' => 1, 'loan_details_id' => 1, 'paid_at' => $paid, 'repayment_amount' => $principal, 'interest_paid' => $interest]);
        }

        // An archived member of the same society must not receive it.
        DB::table('users')->insert(['id' => 2, 'name' => 'ARCHIVED', 'last_name' => 'MEMBER', 'user_type' => 'customer', 'branch_account' => 0, 'branch_id' => 10, 'status' => 1]);
        DB::table('user_details')->insert(['user_id' => 2, 'mobile' => '08030000001']);
        DB::table('member_branch_memberships')->insert(['id' => 103, 'user_id' => 2, 'branch_id' => 10, 'member_number' => 'ASEOLU_0999', 'is_primary' => 1, 'status' => 0]);

        $service = app(SmsAutomationService::class);
        $this->assertSame(0, $service->processDateBasedAutomations(\Illuminate\Support\Carbon::parse('2026-11-01 07:59')));
        $this->assertSame(1, $service->processDateBasedAutomations(\Illuminate\Support\Carbon::parse('2026-11-01 08:00')));

        // Sent 1 Nov -> October statement, as at 31 Oct (the November entries are excluded).
        $this->assertSame(
            ['ASEOLUWA CTCS ILARO LTD|ASEOLU_0123|October 2026|106,000.00|7,000.00|2,000.00|111,000.00|80,000.00|1,500.00|31,000.00|3|45,000.00|50,000.00|30,000.00'],
            SmsMessage::query()->pluck('message')->all()
        );
    }

    public function test_credited_account_balances_lists_only_the_posted_accounts(): void
    {
        $this->rule('transaction_credit', '{{credited_account_balances}}');
        app(SmsAutomationService::class)->handleTransactionBatch($this->posting([['shares', 'cr', 2000], ['savings', 'cr', 5000]]));

        $this->assertSame('Savings 50,000.00, Shares 30,000.00', SmsMessage::query()->value('message'));
    }

    public function test_delivery_reports_mark_messages_delivered_or_failed(): void
    {
        $token = app(SmsSettingsService::class)->deliveryReportToken();
        foreach (['MSG-1', 'MSG-2', 'MSG-3'] as $externalId) {
            SmsMessage::create(['message' => 'x', 'phone' => '2348030000000', 'status' => SmsMessage::STATUS_SENT, 'external_id' => $externalId]);
        }

        // BulkSMSNigeria/generic style (form), Termii style (JSON), and a list of reports.
        $this->post('/sms/delivery-report/' . $token, ['message_id' => 'MSG-1', 'status' => 'DELIVRD'])->assertOk()->assertJson(['updated' => 1]);
        $this->postJson('/sms/delivery-report/' . $token, ['messageId' => 'MSG-2', 'status' => 'DND Active on Phone Number'])->assertOk();
        $this->postJson('/sms/delivery-report/' . $token, [['id' => 'MSG-3', 'delivery_status' => 'Delivered']])->assertOk();

        $this->assertSame(
            ['MSG-1' => 'delivered', 'MSG-2' => 'failed', 'MSG-3' => 'delivered'],
            SmsMessage::query()->orderBy('id')->pluck('status', 'external_id')->all()
        );
        $this->assertSame('Delivery failed: DND Active on Phone Number', SmsMessage::query()->where('external_id', 'MSG-2')->value('error_message'));

        // A wrong token is rejected.
        $this->post('/sms/delivery-report/wrong-token', ['message_id' => 'MSG-1', 'status' => 'Failed'])->assertNotFound();
        $this->assertSame('delivered', SmsMessage::query()->where('external_id', 'MSG-1')->value('status'));
    }

    public function test_catalog_flags_broken_and_unknown_placeholders(): void
    {
        $issues = app(SmsPlaceholderCatalog::class)->analyze(
            'PR {{principal_paid}} IP: {interest_paid}} X {{made_up}} {{total_credited}}',
            'loan_repayment'
        );

        $this->assertSame(['{interest_paid}}'], $issues['malformed']);
        $this->assertSame(['made_up'], $issues['unknown']);
        $this->assertSame(['total_credited'], $issues['other_event']);

        $clean = app(SmsPlaceholderCatalog::class)->analyze(self::CREDIT_TEMPLATE, 'transaction_credit');
        $this->assertSame(['unknown' => [], 'other_event' => [], 'malformed' => []], $clean);
    }

    public function test_test_page_sample_preview_adds_sample_amounts_to_real_balances(): void
    {
        $controller = app(SmsTestController::class);
        $build = new \ReflectionMethod($controller, 'buildContext');
        $build->setAccessible(true);

        [$context] = $build->invoke($controller, [
            'event' => 'transaction_credit', 'data_source' => 'sample', 'transaction_date' => '2026-11-22',
            'amounts' => ['savings' => 5000, 'shares' => 0, 'authentication' => 0, 'deposit' => 1000],
        ], Branch::query()->find(10), User::query()->find(1));

        $this->assertSame('ASEOLUWA CTCS ILARO LTD', $context['society_name']);
        $this->assertSame('Savings 5,000.00, Deposit 1,000.00', $context['credited_account_details']);
        $this->assertSame('55,000.00', $context['savings_balance']);
        $this->assertSame('117,000.00', $context['total_member_balance']);

        // Without a member, a stand-in with zero starting balances is used.
        [$sample] = $build->invoke($controller, [
            'event' => 'transaction_credit', 'data_source' => 'sample', 'transaction_date' => '2026-11-22',
            'amounts' => ['savings' => 5000],
        ], Branch::query()->find(10), null);
        $this->assertSame('TEST MEMBER', $sample['member_name']);
        $this->assertSame('5,000.00', $sample['total_member_balance']);
    }

    public function test_campaign_society_name_is_the_affiliated_society(): void
    {
        $service = app(SmsCampaignService::class);
        $context = new \ReflectionMethod($service, 'contextForUser');
        $context->setAccessible(true);

        $values = $context->invoke($service, User::query()->find(1), Branch::query()->find(10));

        $this->assertSame('ASEOLUWA CTCS ILARO LTD', $values['society_name']);
        $this->assertSame('Oreoluwapo CT&CU', $values['parent_society_name']);
        $this->assertSame('111,000.00', $values['total_member_balance']);
    }

    // ---------------------------------------------------------------- helpers

    private function rule(string $event, string $body, string $mode = SmsAutomationRule::SEND_AUTOMATIC, array $overrides = []): SmsAutomationRule
    {
        $templateId = DB::table('sms_templates')->insertGetId(['name' => $event, 'category' => $event, 'body' => $body, 'status' => true]);

        return SmsAutomationRule::create([
            'name' => $event, 'event' => $event, 'template_id' => $templateId, 'status' => true,
            'send_mode' => $mode, 'branch_id' => 10, ...$overrides,
        ]);
    }

    /** Save a posting like TransactionService::createBatch does (one batch_id). */
    private function posting(array $entries)
    {
        $accounts = ['savings' => 201, 'shares' => 202, 'authentication' => 203, 'deposit' => 204];
        $batch = 'batch-' . uniqid();
        $ids = [];
        foreach ($entries as [$account, $drCr, $amount]) {
            $ids[] = DB::table('transactions')->insertGetId([
                'user_id' => 1, 'member_branch_membership_id' => 101, 'savings_account_id' => $accounts[$account],
                'trans_date' => '2026-11-22', 'amount' => $amount, 'dr_cr' => $drCr, 'branch_id' => 10,
                'is_branch' => 0, 'batch_id' => $batch, 'status' => 2,
            ]);
        }

        return Transaction::query()->with(['user.detail', 'account.product'])->whereIn('id', $ids)->get();
    }

    private function seedData(): void
    {
        DB::table('branches')->insert([
            ['id' => 10, 'name' => 'ASEOLUWA CTCS ILARO LTD', 'status' => 1],
            ['id' => 20, 'name' => 'OTHER SOCIETY', 'status' => 1],
        ]);
        DB::table('users')->insert(['id' => 1, 'name' => 'ANIWURA', 'last_name' => 'AJOKE', 'email' => 'a@example.test', 'user_type' => 'customer', 'branch_account' => 0, 'branch_id' => 10, 'status' => 1]);
        DB::table('user_details')->insert(['user_id' => 1, 'mobile' => '08030000000', 'member_no' => 'ASEOLU_0123']);
        DB::table('member_branch_memberships')->insert([
            ['id' => 101, 'user_id' => 1, 'branch_id' => 10, 'member_number' => 'ASEOLU_0123', 'is_primary' => 1, 'status' => 1],
            ['id' => 102, 'user_id' => 1, 'branch_id' => 20, 'member_number' => 'OTHER_0009', 'is_primary' => 0, 'status' => 1],
        ]);
        DB::table('savings_products')->insert([
            ['id' => 7, 'name' => 'SAVINGS', 'type' => 'SAVINGS'],
            ['id' => 9, 'name' => 'SHARES', 'type' => 'SHARES'],
            ['id' => 10, 'name' => 'AUTHENTICATION', 'type' => 'AUTHENTICATION'],
            ['id' => 11, 'name' => 'DEPOSIT', 'type' => 'DEPOSIT'],
        ]);
        // Balances already include the posting (they are synced before the alert is sent).
        DB::table('savings_accounts')->insert([
            ['id' => 201, 'user_id' => 1, 'member_branch_membership_id' => 101, 'savings_product_id' => 7, 'account_number' => '1000201', 'balance' => 50000, 'status' => 1, 'is_branch_acount' => 0],
            ['id' => 202, 'user_id' => 1, 'member_branch_membership_id' => 101, 'savings_product_id' => 9, 'account_number' => '1000202', 'balance' => 30000, 'status' => 1, 'is_branch_acount' => 0],
            ['id' => 203, 'user_id' => 1, 'member_branch_membership_id' => 101, 'savings_product_id' => 10, 'account_number' => '1000203', 'balance' => 20000, 'status' => 1, 'is_branch_acount' => 0],
            ['id' => 204, 'user_id' => 1, 'member_branch_membership_id' => 101, 'savings_product_id' => 11, 'account_number' => '1000204', 'balance' => 11000, 'status' => 1, 'is_branch_acount' => 0],
            ['id' => 299, 'user_id' => 1, 'member_branch_membership_id' => 102, 'savings_product_id' => 7, 'account_number' => '2000299', 'balance' => 999999, 'status' => 1, 'is_branch_acount' => 0],
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('branches', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('prefix')->nullable(); $t->boolean('status')->default(1); $t->softDeletes(); $t->timestamps(); });
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('last_name')->nullable(); $t->string('email')->nullable(); $t->string('user_type')->nullable(); $t->boolean('branch_account')->default(0); $t->string('branch_id')->nullable(); $t->string('member_no')->nullable(); $t->integer('status')->default(1); $t->boolean('society_exco')->default(0); $t->boolean('former_exco')->default(0); $t->softDeletes(); $t->timestamps(); });
        Schema::create('user_details', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('user_id'); $t->string('mobile')->nullable(); $t->string('member_no')->nullable(); $t->date('date_of_birth')->nullable(); $t->unsignedBigInteger('branch_id')->nullable(); $t->timestamps(); });
        Schema::create('member_branch_memberships', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('branch_id'); $t->string('member_number'); $t->boolean('is_primary')->default(0); $t->boolean('status')->default(1); $t->timestamp('joined_at')->nullable(); $t->timestamps(); });
        Schema::create('savings_products', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('type'); $t->integer('status')->default(1); $t->timestamps(); });
        Schema::create('savings_accounts', function (Blueprint $t) { $t->id(); $t->string('account_number')->nullable(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('member_branch_membership_id')->nullable(); $t->unsignedBigInteger('branch_id')->nullable(); $t->unsignedBigInteger('savings_product_id'); $t->integer('status')->default(1); $t->decimal('opening_balance', 15, 2)->default(0); $t->decimal('balance', 15, 2)->default(0); $t->boolean('is_branch_acount')->default(0); $t->timestamps(); });
        Schema::create('transactions', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('user_id')->nullable(); $t->unsignedBigInteger('member_branch_membership_id')->nullable(); $t->unsignedBigInteger('savings_account_id')->nullable(); $t->date('trans_date')->nullable(); $t->decimal('amount', 15, 2); $t->string('dr_cr'); $t->unsignedBigInteger('branch_id')->nullable(); $t->boolean('is_branch')->default(0); $t->string('batch_id')->nullable(); $t->string('tracking_id')->default('regular'); $t->integer('status')->default(2); $t->json('transaction_details')->nullable(); $t->softDeletes(); $t->timestamps(); });
        Schema::create('loans', function (Blueprint $t) { $t->id(); $t->string('loan_id')->nullable(); $t->unsignedBigInteger('borrower_id'); $t->unsignedBigInteger('branch_id')->nullable(); $t->unsignedBigInteger('member_branch_membership_id')->nullable(); $t->decimal('applied_amount', 15, 2)->default(0); $t->decimal('balanace', 15, 2)->default(0); $t->softDeletes(); $t->timestamps(); });
        Schema::create('loan_details', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('loan_id'); $t->decimal('applied_amount', 15, 2)->default(0); $t->date('release_date')->nullable(); $t->string('decision_status')->default('approved'); $t->timestamps(); });
        Schema::create('loan_payments', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('loan_id'); $t->unsignedBigInteger('loan_details_id')->nullable(); $t->date('paid_at')->nullable(); $t->decimal('repayment_amount', 15, 2)->default(0); $t->decimal('interest_paid', 15, 2)->default(0); $t->decimal('total_amount', 15, 2)->default(0); $t->decimal('balance', 15, 2)->default(0); $t->decimal('outstanding_interest', 15, 2)->default(0); $t->softDeletes(); $t->timestamps(); });
        Schema::create('sms_templates', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('slug')->nullable(); $t->string('category'); $t->string('description')->nullable(); $t->text('body'); $t->boolean('status')->default(1); $t->unsignedBigInteger('created_by')->nullable(); $t->unsignedBigInteger('updated_by')->nullable(); $t->timestamps(); });
        Schema::create('sms_automation_rules', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('branch_id')->nullable(); $t->unsignedBigInteger('template_id')->nullable(); $t->string('name'); $t->string('event'); $t->boolean('status')->default(1); $t->string('send_mode', 20)->default('automatic'); $t->string('schedule_time', 5)->nullable(); $t->unsignedTinyInteger('day_of_month')->nullable(); $t->json('filters')->nullable(); $t->unsignedBigInteger('created_by')->nullable(); $t->unsignedBigInteger('updated_by')->nullable(); $t->timestamps(); });
        Schema::create('sms_messages', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('campaign_id')->nullable(); $t->unsignedBigInteger('automation_rule_id')->nullable(); $t->unsignedBigInteger('user_id')->nullable(); $t->unsignedBigInteger('branch_id')->nullable(); $t->string('phone')->nullable(); $t->string('recipient_name')->nullable(); $t->text('message'); $t->string('provider')->nullable(); $t->string('sender_id')->nullable(); $t->string('status', 20)->default('pending'); $t->string('external_id')->nullable(); $t->text('error_message')->nullable(); $t->string('related_type')->nullable(); $t->unsignedBigInteger('related_id')->nullable(); $t->string('reference_key')->nullable(); $t->timestamp('scheduled_for')->nullable(); $t->timestamp('processed_at')->nullable(); $t->timestamp('sent_at')->nullable(); $t->json('meta')->nullable(); $t->timestamps(); });
    }
}
