<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDataBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('data-backups.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'modules' => ['required', 'array', 'min:1'],
            'modules.*' => ['required', 'string', Rule::in(array_keys(config('data_backup.modules', [])))],
            'format' => ['required', Rule::in(['xlsx', 'csv', 'pdf', 'sql'])],
        ];
    }
}
