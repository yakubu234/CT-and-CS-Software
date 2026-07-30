<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDataBackupSettingsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $emails = preg_split('/[\s,;]+/', (string) $this->input('recipient_emails'), -1, PREG_SPLIT_NO_EMPTY);

        $this->merge([
            'enabled' => $this->boolean('enabled'),
            'recipient_emails' => array_values(array_unique(array_map('strtolower', $emails ?: []))),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('data-backups.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'modules' => ['required', 'array', 'min:1'],
            'modules.*' => ['string', Rule::in(array_keys(config('data_backup.modules', [])))],
            'formats' => ['required', 'array', 'min:1'],
            'formats.*' => ['string', Rule::in(['xlsx', 'csv', 'pdf', 'sql'])],
            'drive_folder_id' => ['required_if:enabled,true', 'nullable', 'string', 'max:255'],
            'recipient_emails' => ['array'],
            'recipient_emails.*' => ['email:rfc', 'max:255'],
            'credentials' => ['nullable', 'file', 'mimetypes:application/json,text/plain', 'max:1024'],
        ];
    }
}
