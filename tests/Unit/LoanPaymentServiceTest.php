<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Loan;
use App\Services\LoanPaymentService;
use App\Services\LoanService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class LoanPaymentServiceTest extends TestCase
{
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

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
