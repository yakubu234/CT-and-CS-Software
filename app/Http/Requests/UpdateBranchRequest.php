<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $branch = $this->route('branch');
        $branchId = $branch?->id;

        return [
            'branch_name' => ['required', 'string', 'max:191', Rule::unique('branches', 'name')->ignore($branchId)],
            'branch_prefix' => ['required', 'string', 'max:255', Rule::unique('branches', 'prefix')->ignore($branchId)],
            'loan_prefix' => ['required', 'string', 'max:255', Rule::unique('branches', 'id_prefix')->ignore($branchId)],
            'contact_email' => ['required', 'email', 'max:191', Rule::unique('branches', 'contact_email')->ignore($branchId)],
            'contact_phone' => ['nullable', 'string', 'max:191'],
            'registration_number' => ['nullable', 'string', 'max:200'],
            'year_of_registration' => ['nullable', 'digits:4', 'integer', 'min:1900', 'max:' . now()->year],
            'branch_meeting_days' => ['required', Rule::in(['weekly', 'every 2 weeks', 'every 3 weeks', 'monthly'])],
            'address' => ['required', 'string'],
            'photo' => ['nullable', 'image', 'max:4096'],
            'signature' => ['nullable', 'image', 'max:4096'],
            'excos' => ['nullable', 'array'],
            'excos.*.member_id' => ['required', 'integer', 'distinct', 'exists:users,id'],
            'excos.*.designation_id' => ['required', 'exists:designations,id'],
        ];
    }
}
