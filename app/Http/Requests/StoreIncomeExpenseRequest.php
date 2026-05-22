<?php

namespace App\Http\Requests;

use App\Models\TransactionCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreIncomeExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trans_date' => ['required', 'date'],
            'entries' => ['required', 'array', 'min:1', 'max:20'],
            'entries.*.transaction_category_id' => ['required', 'integer', 'exists:transaction_categories,id'],
            'entries.*.amount' => ['required', 'numeric', 'min:0.01'],
            'entries.*.description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $entries = $this->input('entries', []);

                foreach ($entries as $index => $entry) {
                    $categoryId = (int) ($entry['transaction_category_id'] ?? 0);

                    $category = TransactionCategory::query()
                        ->whereKey($categoryId)
                        ->where('type_to_transaction', 'expenses')
                        ->where('status', 1)
                        ->first();

                    if (! $category) {
                        $validator->errors()->add(
                            "entries.{$index}.transaction_category_id",
                            'Please choose an active income or expense category.'
                        );
                        continue;
                    }

                }
            },
        ];
    }
}
