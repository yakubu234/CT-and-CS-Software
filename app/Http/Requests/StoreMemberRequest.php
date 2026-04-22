<?php

namespace App\Http\Requests;

use App\Models\CustomField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:191'],
            'last_name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email'],
            'occupation' => ['nullable', 'string', 'max:191'],
            'mobile' => ['required', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female,other'],
            'city' => ['nullable', 'string', 'max:191'],
            'state' => ['nullable', 'string', 'max:191'],
            'address' => ['nullable', 'string'],
            'signature' => ['nullable', 'image', 'max:2048'],
            'picture' => ['nullable', 'image', 'max:4096'],
            'documents' => ['nullable', 'array'],
            'documents.*.name' => ['nullable', 'string', 'max:191'],
            'documents.*.file' => ['nullable', 'file', 'max:5120'],
            'custom_fields' => ['nullable', 'array'],
        ];

        foreach (CustomField::query()->forUsers()->active()->get() as $field) {
            $fieldRules = [];

            if ($field->is_required === 'required') {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            $fieldRules[] = match ($field->field_type) {
                CustomField::TYPE_NUMBER => 'numeric',
                CustomField::TYPE_FILE => 'file',
                default => 'string',
            };

            if ($field->field_type === CustomField::TYPE_FILE) {
                $fieldRules[] = 'max:' . ($field->max_size ?: 2048);
            }

            if ($field->field_type === CustomField::TYPE_SELECT && $field->optionsList() !== []) {
                $fieldRules[] = Rule::in($field->optionsList());
            }

            $rules["custom_fields.{$field->id}"] = $fieldRules;
        }

        return $rules;
    }
}
