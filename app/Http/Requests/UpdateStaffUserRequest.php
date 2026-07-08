<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'first_name' => ['required', 'string', 'max:191'],
            'last_name' => ['nullable', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($user->id)->whereNull('deleted_at')],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role_id' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! Role::query()->adminAccess()->whereKey($value)->exists()) {
                        $fail('Please select a valid admin role.');
                    }
                },
            ],
            'branch_id' => ['required', 'exists:branches,id'],
            'assigned_branch_ids' => ['nullable', 'array'],
            'assigned_branch_ids.*' => ['integer', 'exists:branches,id'],
            'status' => ['required', 'boolean'],
            'designation' => ['nullable', 'string', 'max:191'],
        ];
    }

    public function attributes(): array
    {
        return [
            'designation' => 'job title',
            'password_confirmation' => 'confirm password',
        ];
    }
}
