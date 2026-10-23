<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Loan;
use App\Models\LoanDetail;
use App\Models\LoanPayment;
use App\Services\LoanPaymentService;
use App\Services\LoanService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class LoanPaymentServiceTest extends TestCase
{
    public function test_next_repayment_uses_member_schedule_and_last_relevant_repayment(): void
    {
        $service = new LoanPaymentService(Mockery::mock(LoanService::class));
        $branch = new Branch(['branch_meeting_days' => 'monthly']);
        $detail = new LoanDetail([
            'id' => 10,
            'release_date' => '2026-10-01',
            'interest_week_interval' => 'weekly',
        ]);
        $detail->id = 10;

        $lastPayment = new LoanPayment(['paid_at' => '2026-10-15', 'loan_details_id' => 10]);
        $lastPayment->id = 20;
        $otherSchedulePayment = new LoanPayment(['paid_at' => '2026-10-20', 'loan_details_id' => 99]);
        $otherSchedulePayment->id = 21;

        $loan = new Loan();
        $loan->setRelation('branch', $branch);
        $loan->setRelation('payments', collect([$lastPayment, $otherSchedulePayment]));

        $summary = $service->dueCycleSummary($detail, Carbon::parse('2026-10-22'), $loan);

        $this->assertTrue($summary['is_due']);
        $this->assertSame('2026-10-22', $summary['next_due_date']);
        $this->assertSame('weekly', $summary['interval']);
        $this->assertSame('last_repayment', $summary['based_on']);
    }

    public function test_branch_frequency_is_used_only_when_member_schedule_is_missing(): void
    {
        $service = new LoanPaymentService(Mockery::mock(LoanService::class));
        $branch = new Branch(['branch_meeting_days' => 'every 2 weeks']);
        $detail = new LoanDetail(['release_date' => '2026-10-01', 'interest_week_interval' => null]);
        $loan = new Loan();
        $loan->setRelation('branch', $branch);
        $loan->setRelation('payments', collect());

        $summary = $service->dueCycleSummary($detail, Carbon::parse('2026-10-15'), $loan);

        $this->assertTrue($summary['is_due']);
        $this->assertSame('every-2-weeks', $summary['interval']);
        $this->assertSame('2026-10-15', $summary['next_due_date']);
        $this->assertSame('release_date', $summary['based_on']);
    }

    public function test_interest_payment_does_not_reduce_loan_principal_balance(): void
    {
        $branch = new Branch(['id' => 1]);
        $loan = new Loan(['balanace' => 1000000]);

        $service = new class(Mockery::mock(LoanService::class)) extends LoanPaymentService
        {
            public array $context = [];

            public array $interestMeta = [];

            public function repaymentContext(Loan $loan, ?string $paidAt = null, $excludePayment = null): array
            {
                return $this->context;
            }

            public function suggestedInterest(Loan $loan, float $interestRate, ?string $paidAt = null, $excludePayment = null): array
            {
                return $this->interestMeta;
            }

            public function prepare(Branch $branch, Loan $loan, array $payload): array
            {
                return $this->prepareRepaymentPayload($branch, $loan, $payload);
            }
        };

        $service->context = [
            'detail' => null,
            'paid_at' => Carbon::parse('2026-06-23'),
            'current_balance' => 1000000.0,
        ];
        $service->interestMeta = [
            'current_interest_due' => 0.0,
            'total_interest_due' => 0.0,
            'pending_carry_forwards' => new Collection(),
            'due_cycle' => [],
        ];

        $prepared = $service->prepare($branch, $loan, [
            'paid_at' => '2026-06-23',
            'repayment_amount' => 12000,
            'interest_paid' => 3000,
            'interest_rate' => 0,
        ]);

        $this->assertSame(12000.0, $prepared['principal_applied']);
        $this->assertSame(3000.0, $prepared['interest_applied']);
        $this->assertSame(988000.0, $prepared['loan_balance_after']);
        $this->assertSame(0.0, $prepared['interest_remaining']);
    }

    public function test_entered_outstanding_interest_is_carried_forward(): void
    {
        $branch = new Branch(['id' => 1]);
        $loan = new Loan(['balanace' => 1000000]);

        $service = new class(Mockery::mock(LoanService::class)) extends LoanPaymentService
        {
            public array $context = [];

            public array $interestMeta = [];

            public function repaymentContext(Loan $loan, ?string $paidAt = null, $excludePayment = null): array
            {
                return $this->context;
            }

            public function suggestedInterest(Loan $loan, float $interestRate, ?string $paidAt = null, $excludePayment = null): array
            {
                return $this->interestMeta;
            }

            public function prepare(Branch $branch, Loan $loan, array $payload): array
            {
                return $this->prepareRepaymentPayload($branch, $loan, $payload);
            }
        };

        $service->context = [
            'detail' => null,
            'paid_at' => Carbon::parse('2026-06-23'),
            'current_balance' => 1000000.0,
        ];
        $service->interestMeta = [
            'current_interest_due' => 0.0,
            'total_interest_due' => 0.0,
            'pending_carry_forwards' => new Collection(),
            'due_cycle' => [],
        ];

        $prepared = $service->prepare($branch, $loan, [
            'paid_at' => '2026-06-23',
            'repayment_amount' => 0,
            'interest_paid' => 3000,
            'outstanding_interest' => 1500,
        ]);

        $this->assertSame(3000.0, $prepared['interest_applied']);
        $this->assertSame(1500.0, $prepared['interest_remaining']);
        $this->assertSame(4500.0, $prepared['interest_expected_total']);
        $this->assertSame(1, $prepared['carry_forward_flag']);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
