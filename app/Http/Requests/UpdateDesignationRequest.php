<?php

namespace App\Http\Requests;

use App\Models\Designation;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class UpdateDesignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Designation $designation */
        $designation = $this->route('designation');

        return [
            'name' => [
                'required',
                'string',
                'max:191',
                Rule::unique('designations', 'name')->ignore($designation->id),
                function (string $attribute, mixed $value, \Closure $fail) use ($designation): void {
                    $slug = Str::slug((string) $value);

                    if ($slug !== '' && Role::query()->adminAccess()->where('slug', $slug)->exists() && $slug !== $designation->slug) {
                        $fail('That name is already reserved for an admin access role.');
                    }
                },
            ],
            'status' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:1'],
        ];
    }
}
