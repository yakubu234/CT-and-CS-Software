<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateMemberRequest extends StoreMemberRequest
{
    protected function prepareForValidation(): void
    {
        $existingDocuments = collect($this->input('existing_documents', []))
            ->map(function (array $document): array {
                $document['keep'] = filter_var($document['keep'] ?? false, FILTER_VALIDATE_BOOL);

                return $document;
            })
            ->all();

        $this->merge([
            'existing_documents' => $existingDocuments,
        ]);
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $member = $this->route('member');

        $rules['email'] = [
            'required',
            'email',
            'max:191',
            Rule::unique('users', 'email')->ignore($member?->id),
        ];

        $rules['existing_documents'] = ['nullable', 'array'];
        $rules['existing_documents.*.id'] = ['nullable', 'integer'];
        $rules['existing_documents.*.name'] = ['nullable', 'string', 'max:191'];
        $rules['existing_documents.*.keep'] = ['nullable', 'boolean'];

        return $rules;
    }
}
