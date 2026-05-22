<?php

namespace Tests\Unit;

use App\Models\Loan;
use App\Models\LoanDetail;
use App\Models\LoanPayment;
use App\Services\LoanPaymentService;
use App\Services\LoanService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class LoanDeletionRulesTest extends TestCase
{
    public function test_approved_loan_history_can_be_deleted_only_when_repayments_are_fully_cleared(): void
    {
        $detail = Mockery::mock(LoanDetail::class)->makePartial();
        $detail->decision_status = LoanDetail::STATUS_APPROVED;
        $detail->amount_repayed = 0;

        $emptyRelation = Mockery::mock(HasMany::class);
        $emptyRelation->shouldReceive('exists')->once()->andReturnFalse();
        $detail->shouldReceive('payments')->once()->andReturn($emptyRelation);

        $this->assertTrue($detail->canBeDeleted());

        $detailWithPaidAmount = Mockery::mock(LoanDetail::class)->makePartial();
        $detailWithPaidAmount->decision_status = LoanDetail::STATUS_APPROVED;
        $detailWithPaidAmount->amount_repayed = 15;

        $noRowsRelation = Mockery::mock(HasMany::class);
        $noRowsRelation->shouldReceive('exists')->once()->andReturnFalse();
        $detailWithPaidAmount->shouldReceive('payments')->once()->andReturn($noRowsRelation);

        $this->assertFalse($detailWithPaidAmount->canBeDeleted());

        $detailWithRepayment = Mockery::mock(LoanDetail::class)->makePartial();
        $detailWithRepayment->decision_status = LoanDetail::STATUS_APPROVED;
        $detailWithRepayment->amount_repayed = 0;

        $liveRelation = Mockery::mock(HasMany::class);
        $liveRelation->shouldReceive('exists')->once()->andReturnTrue();
        $detailWithRepayment->shouldReceive('payments')->once()->andReturn($liveRelation);

        $this->assertFalse($detailWithRepayment->canBeDeleted());
    }

    public function test_repayment_snapshots_are_resequenced_from_loan_histories(): void
    {
        $detailOne = new LoanDetail([
            'id' => 11,
            'applied_amount' => 100,
            'release_date' => Carbon::parse('2026-01-01'),
            'decision_status' => LoanDetail::STATUS_APPROVED,
        ]);
        $detailOne->exists = true;

        $detailTwo = new LoanDetail([
            'id' => 22,
            'applied_amount' => 50,
            'release_date' => Carbon::parse('2026-02-01'),
            'decision_status' => LoanDetail::STATUS_APPROVED,
        ]);
        $detailTwo->exists = true;

        $paymentOne = Mockery::mock(LoanPayment::class)->makePartial();
        $paymentOne->forceFill([
            'id' => 101,
            'paid_at' => Carbon::parse('2026-01-10'),
            'repayment_amount' => 30,
            'total_outstanding' => 999,
            'balance' => 999,
        ]);
        $paymentOne->exists = true;
        $paymentOne->shouldReceive('update')->once()->with([
            'total_outstanding' => 100.0,
            'balance' => 70.0,
        ])->andReturnUsing(function (array $attributes) use ($paymentOne): bool {
            $paymentOne->forceFill($attributes);

            return true;
        });

        $paymentTwo = Mockery::mock(LoanPayment::class)->makePartial();
        $paymentTwo->forceFill([
            'id' => 202,
            'paid_at' => Carbon::parse('2026-02-10'),
            'repayment_amount' => 20,
            'total_outstanding' => 999,
            'balance' => 999,
        ]);
        $paymentTwo->exists = true;
        $paymentTwo->shouldReceive('update')->once()->with([
            'total_outstanding' => 120.0,
            'balance' => 100.0,
        ])->andReturnUsing(function (array $attributes) use ($paymentTwo): bool {
            $paymentTwo->forceFill($attributes);

            return true;
        });

        $loan = Mockery::mock(Loan::class)->makePartial();
        $loan->setRelation('details', new Collection([$detailOne, $detailTwo]));
        $loan->setRelation('payments', new Collection([$paymentOne, $paymentTwo]));
        $loan->shouldReceive('load')->once()->andReturnSelf();

        $service = new class(Mockery::mock(LoanService::class)) extends LoanPaymentService
        {
            public function refreshSnapshots(Loan $loan): void
            {
                $this->refreshLoanPaymentSnapshots($loan);
            }
        };

        $service->refreshSnapshots($loan);

        $this->assertSame(100.0, round((float) $paymentOne->total_outstanding, 2));
        $this->assertSame(70.0, round((float) $paymentOne->balance, 2));
        $this->assertSame(120.0, round((float) $paymentTwo->total_outstanding, 2));
        $this->assertSame(100.0, round((float) $paymentTwo->balance, 2));
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
