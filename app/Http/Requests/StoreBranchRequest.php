<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_name' => ['required', 'string', 'max:191', 'unique:branches,name'],
            'branch_prefix' => ['required', 'string', 'max:255', 'unique:branches,prefix'],
            'loan_prefix' => ['required', 'string', 'max:255', 'unique:branches,id_prefix'],
            'contact_email' => ['required', 'email', 'max:191', 'unique:branches,contact_email'],
            'contact_phone' => ['nullable', 'string', 'max:191'],
            'registration_number' => ['nullable', 'string', 'max:200'],
            'year_of_registration' => ['nullable', 'digits:4', 'integer', 'min:1900', 'max:' . now()->year],
            'branch_meeting_days' => [
                'required',
                Rule::in(['weekly', 'every 2 weeks', 'every 3 weeks', 'monthly']),
            ],
            'address' => ['required', 'string'],
            'photo' => ['nullable', 'image', 'max:4096'],
            'signature' => ['nullable', 'image', 'max:4096'],
            'excos' => ['required', 'array', 'min:1'],
            'excos.*.first_name' => ['required', 'string', 'max:191'],
            'excos.*.last_name' => ['required', 'string', 'max:191'],
            'excos.*.phone' => ['required', 'string', 'max:191'],
            'excos.*.designation_id' => ['required', 'exists:designations,id'],
            'excos.*.image' => ['nullable', 'image', 'max:4096'],
        ];
    }

    public function attributes(): array
    {
        return [
            'branch_name' => 'branch name',
            'branch_prefix' => 'branch prefix',
            'loan_prefix' => 'loan prefix',
            'contact_email' => 'contact email',
            'contact_phone' => 'contact phone',
            'registration_number' => 'registration number',
            'year_of_registration' => 'year of registration',
            'branch_meeting_days' => 'branch meeting days',
            'photo' => 'branch logo',
            'signature' => 'branch signature',
            'excos.*.first_name' => 'exco first name',
            'excos.*.last_name' => 'exco last name',
            'excos.*.phone' => 'exco phone',
            'excos.*.designation_id' => 'exco designation',
            'excos.*.image' => 'exco image',
        ];
    }
}
