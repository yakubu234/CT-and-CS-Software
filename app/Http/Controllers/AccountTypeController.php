<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAccountTypeRequest;
use App\Models\SavingsAccount;
use App\Models\SavingsProduct;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountTypeController extends Controller
{
    public function index(Request $request): View
    {
        $products = TableListing::paginate(
            TableListing::applySearch(
                SavingsProduct::query()->latest('id'),
                $request->string('search')->toString(),
                ['name', 'type', 'account_number_prefix']
            ),
            $request,
            10
        );

        $currencyMap = $this->currencyMap();

        $products->getCollection()->transform(function (SavingsProduct $product) use ($currencyMap) {
            $product->setAttribute('next_account_number', $this->nextAccountNumber($product));
            $product->setAttribute('currency_name', $currencyMap[$product->currency_id] ?? 'N/A');

            return $product;
        });

        return view('account-types.index', [
            'products' => $products,
        ]);
    }

    public function show(SavingsProduct $accountType): View
    {
        $accountType->setAttribute('next_account_number', $this->nextAccountNumber($accountType));
        $accountType->setAttribute('currency_name', $this->currencyMap()[$accountType->currency_id] ?? 'N/A');

        return view('account-types.show', [
            'accountType' => $accountType,
            'currencies' => $this->currencies(),
            'interestMethodOptions' => $this->interestMethodOptions(),
            'interestPeriodOptions' => $this->interestPeriodOptions(),
            'yesNoOptions' => $this->yesNoOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function edit(SavingsProduct $accountType): View
    {
        $accountType->setAttribute('next_account_number', $this->nextAccountNumber($accountType));

        return view('account-types.edit', [
            'accountType' => $accountType,
            'currencies' => $this->currencies(),
            'interestMethodOptions' => $this->interestMethodOptions(),
            'interestPeriodOptions' => $this->interestPeriodOptions(),
            'yesNoOptions' => $this->yesNoOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(UpdateAccountTypeRequest $request, SavingsProduct $accountType): RedirectResponse
    {
        $accountType->update($request->validated());

        return redirect()
            ->route('account-types.show', $accountType)
            ->with('status', "{$accountType->name} account type updated successfully.");
    }

    protected function nextAccountNumber(SavingsProduct $product): string
    {
        $prefix = (string) ($product->account_number_prefix ?? '');

        $maxUsedValue = SavingsAccount::query()
            ->where('savings_product_id', $product->id)
            ->when($prefix !== '', function ($query) use ($prefix): void {
                $query->where('account_number', 'like', $prefix . '%');
            })
            ->pluck('account_number')
            ->map(function (string $accountNumber) use ($prefix): int {
                if ($prefix === '') {
                    return (int) preg_replace('/\D+/', '', $accountNumber);
                }

                return (int) substr($accountNumber, strlen($prefix));
            })
            ->max() ?? 0;

        $nextNumber = max((int) $product->starting_account_number, $maxUsedValue + 1);

        return $prefix . $nextNumber;
    }

    protected function currencies()
    {
        return DB::table('currencies')
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    protected function currencyMap(): array
    {
        return DB::table('currencies')
            ->pluck('name', 'id')
            ->all();
    }

    protected function interestMethodOptions(): array
    {
        return [
            'daily_outstanding_balance' => 'Daily Outstanding Balance',
            'average_daily_balance' => 'Average Daily Balance',
            'minimum_balance' => 'Minimum Balance',
            'flat_rate' => 'Flat Rate',
        ];
    }

    protected function interestPeriodOptions(): array
    {
        return [
            1 => 'Every 1 month',
            2 => 'Every 2 months',
            3 => 'Every 3 months',
            6 => 'Every 6 months',
            12 => 'Every 12 months',
        ];
    }

    protected function yesNoOptions(): array
    {
        return [
            1 => 'Yes',
            0 => 'No',
        ];
    }

    protected function statusOptions(): array
    {
        return [
            1 => 'Active',
            2 => 'Inactive',
        ];
    }
}
