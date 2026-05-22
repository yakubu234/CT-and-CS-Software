<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\Designation;
use App\Support\PermissionRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Role $role */
        $role = $this->route('role');

        return [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('roles', 'name')->ignore($role->id),
                function (string $attribute, mixed $value, \Closure $fail) use ($role): void {
                    $slug = Str::slug((string) $value);

                    if ($slug !== '' && Designation::query()->where('slug', $slug)->exists() && $slug !== $role->slug) {
                        $fail('That name is already reserved for an exco role/designation.');
                    }
                },
            ],
            'description' => ['nullable', 'string'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', Rule::in(PermissionRegistry::all())],
        ];
    }
}
