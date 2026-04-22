<?php

namespace App\Http\Requests;

use App\Models\CustomField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'release_date' => ['required', 'date'],
            'due_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'interest_week_interval' => ['required', 'string', 'max:200'],
            'late_payment_penalties' => ['nullable', 'numeric', 'min:0'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ];

        $fields = CustomField::query()
            ->where('table', 'loans')
            ->where('status', true)
            ->get();

        foreach ($fields as $field) {
            $prefix = "custom_fields.{$field->id}";
            $fieldRules = $field->is_required === 'required' ? ['required'] : ['nullable'];

            $fieldRules[] = match ($field->field_type) {
                CustomField::TYPE_NUMBER => 'numeric',
                CustomField::TYPE_FILE => 'file',
                default => 'string',
            };

            if ($field->field_type === CustomField::TYPE_FILE) {
                $fieldRules[] = 'max:' . ((int) ($field->max_size ?: 5120));
            }

            $rules[$prefix] = $fieldRules;
        }

        return $rules;
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $releaseDate = $this->date('release_date');
                $dueDate = $this->date('due_date');

                if ($releaseDate && $dueDate && $dueDate->lt($releaseDate)) {
                    $validator->errors()->add('due_date', 'The due date cannot be earlier than the release date.');
                }
            },
        ];
    }
}
