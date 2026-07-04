<?php

namespace Tests\Unit;

use App\Models\Transaction;
use App\Services\BalanceSyncService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class BalanceSyncServiceTest extends TestCase
{
    public function test_it_recalculates_member_transaction_snapshots_in_date_order(): void
    {
        $service = new BalanceSyncService();

        $olderDebit = $this->mockTransaction(10, '2026-01-05', 'dr', 30, [
            'balance_before' => 0,
            'balance_after' => 0,
        ], [
            'balance_before' => 100.0,
            'balance_after' => 70.0,
        ]);

        $newerCredit = $this->mockTransaction(20, '2026-01-20', 'cr', 10, [
            'balance_before' => 0,
            'balance_after' => 0,
        ], [
            'balance_before' => 70.0,
            'balance_after' => 80.0,
        ]);

        $endingBalance = $service->syncAccountTransactionCollection(
            new Collection([$olderDebit, $newerCredit]),
            100,
            'ACCT-001'
        );

        $this->assertSame(80.0, $endingBalance);
    }

    public function test_it_recalculates_branch_transaction_snapshots_in_date_order(): void
    {
        $service = new BalanceSyncService();

        $income = $this->mockTransaction(30, '2026-02-01', 'cr', 50, [
            'scope' => 'income-expense',
            'balance_before' => 999,
            'balance_after' => 999,
        ], [
            'scope' => 'income-expense',
            'balance_before' => 0.0,
            'balance_after' => 50.0,
        ]);

        $expense = $this->mockTransaction(40, '2026-02-03', 'dr', 20, [
            'scope' => 'income-expense',
            'balance_before' => 999,
            'balance_after' => 999,
        ], [
            'scope' => 'income-expense',
            'balance_before' => 50.0,
            'balance_after' => 30.0,
        ]);

        $endingBalance = $service->syncBranchTransactionCollection(
            new Collection([$income, $expense]),
            'Main Branch'
        );

        $this->assertSame(30.0, $endingBalance);
    }

    public function test_it_rejects_a_replay_that_would_push_balance_below_zero(): void
    {
        $service = new BalanceSyncService();

        $expense = Mockery::mock(Transaction::class)->makePartial();
        $expense->forceFill([
            'id' => 50,
            'trans_date' => Carbon::parse('2026-03-01'),
            'dr_cr' => 'dr',
            'amount' => 25,
            'transaction_details' => [],
        ]);
        $expense->exists = true;
        $expense->shouldReceive('update')->never();

        $this->expectExceptionMessage('cannot go below zero');

        $service->syncBranchTransactionCollection(new Collection([$expense]), 'Main Branch');
    }

    public function test_member_balance_error_explains_the_available_balance_and_shortfall(): void
    {
        $service = new BalanceSyncService();

        $debit = Mockery::mock(Transaction::class)->makePartial();
        $debit->forceFill([
            'id' => 60,
            'trans_date' => Carbon::parse('2026-07-04'),
            'dr_cr' => 'dr',
            'amount' => 5000,
            'type' => 'Savings',
            'transaction_details' => [],
        ]);
        $debit->exists = true;
        $debit->shouldReceive('update')->never();

        $this->expectExceptionMessage(
            'Insufficient balance for Savings account SAV10728 on 2026-07-04. '
            . 'When transactions are applied in date order, the available balance is ₦3,000.00, '
            . 'but the attempted debit is ₦5,000.00, leaving a shortfall of ₦2,000.00. '
            . 'Reduce the debit amount or credit the account before posting this transaction.'
        );

        $service->syncAccountTransactionCollection(
            new Collection([$debit]),
            3000,
            'SAV10728'
        );
    }

    protected function mockTransaction(
        int $id,
        string $date,
        string $drCr,
        float $amount,
        array $initialDetails,
        array $expectedDetails
    ): Transaction {
        $transaction = Mockery::mock(Transaction::class)->makePartial();
        $transaction->forceFill([
            'id' => $id,
            'trans_date' => Carbon::parse($date),
            'dr_cr' => $drCr,
            'amount' => $amount,
            'transaction_details' => $initialDetails,
        ]);
        $transaction->exists = true;
        $transaction->shouldReceive('update')->once()->with([
            'transaction_details' => $expectedDetails,
        ])->andReturnUsing(function (array $attributes) use ($transaction): bool {
            $transaction->forceFill($attributes);

            return true;
        });

        return $transaction;
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
