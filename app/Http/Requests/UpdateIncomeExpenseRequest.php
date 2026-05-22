<?php

namespace App\Http\Requests;

use App\Models\Transaction;
use App\Models\TransactionCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateIncomeExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_category_id' => ['required', 'integer', 'exists:transaction_categories,id'],
            'trans_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $categoryId = (int) $this->input('transaction_category_id');

                $isValidExpenseCategory = TransactionCategory::query()
                    ->whereKey($categoryId)
                    ->where('type_to_transaction', 'expenses')
                    ->where('status', 1)
                    ->exists();

                if (! $isValidExpenseCategory) {
                    $validator->errors()->add(
                        'transaction_category_id',
                        'Please choose an active income or expense category.'
                    );
                    return;
                }

                /** @var Transaction|null $transaction */
                $transaction = $this->route('incomeExpense');

                if (! $transaction) {
                    return;
                }

            },
        ];
    }
}
