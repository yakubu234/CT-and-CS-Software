<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'documents' => ['required', 'array', 'min:1', 'max:10'],
            'documents.*.name' => ['required', 'string', 'max:191'],
            'documents.*.document_type' => ['required', 'string', 'max:100'],
            'documents.*.file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx', 'max:10240'],
        ];
    }

    public function attributes(): array
    {
        return [
            'documents.*.name' => 'document name',
            'documents.*.document_type' => 'document type',
            'documents.*.file' => 'document file',
        ];
    }
}
