<?php

namespace App\Http\Requests;

use App\Models\CustomField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'field_name' => ['required', 'string', 'max:191'],
            'field_type' => ['required', Rule::in([
                CustomField::TYPE_TEXT,
                CustomField::TYPE_NUMBER,
                CustomField::TYPE_SELECT,
                CustomField::TYPE_TEXTAREA,
                CustomField::TYPE_FILE,
            ])],
            'default_value' => ['nullable', 'string'],
            'options' => ['nullable', 'string'],
            'max_size' => ['nullable', 'integer', 'min:1', 'max:10240'],
            'is_required' => ['required', Rule::in(['nullable', 'required'])],
            'status' => ['required', 'boolean'],
        ];
    }
}
