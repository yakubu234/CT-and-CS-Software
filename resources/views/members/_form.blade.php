@php
    $member = $member ?? null;
    $detail = $member->detail ?? null;
    $storedCustomFields = collect(old('custom_fields', $detail?->custom_fields ?? []));
@endphp

@once
    @push('styles')
        <style>
            .field-label-meta {
                margin-left: 0.35rem;
                font-size: 0.75rem;
                font-weight: 600;
                letter-spacing: 0.02em;
                text-transform: uppercase;
            }

            .field-label-meta.required {
                color: #b91c1c;
            }

            .field-label-meta.optional {
                color: #64748b;
            }

            .upload-field-card {
                padding: 1rem;
                border: 2px dashed #cbd5e1;
                border-radius: 0.75rem;
                background: #f8fafc;
                transition: border-color 0.2s ease, background-color 0.2s ease;
            }

            .upload-field-card:focus-within {
                border-color: #2563eb;
                background: #eff6ff;
            }
        </style>
    @endpush
@endonce

<div class="alert alert-light border mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div class="mb-2 mb-md-0">
            <strong>Member Number Counter</strong>
            <div class="text-muted small">
                This branch uses <code>branches.number_count</code> as the numbering source.
            </div>
        </div>
        <div class="text-md-right">
            <div><strong>Current Counter:</strong> {{ str_pad((string) ((int) ($branch->number_count ?? 0)), 4, '0', STR_PAD_LEFT) }}</div>
            <div><strong>Next Member Number:</strong> {{ old('member_number_preview', $detail?->member_no ?? $member->member_no ?? $nextMemberNumber ?? '') }}</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="first_name">
                First Name
                <span class="field-label-meta required">Required</span>
            </label>
            <input type="text" name="first_name" id="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $member->name ?? '') }}" required>
            @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="last_name">
                Last Name
                <span class="field-label-meta required">Required</span>
            </label>
            <input type="text" name="last_name" id="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $member->last_name ?? '') }}" required>
            @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="member_number_preview">Member Number</label>
            <input type="text" id="member_number_preview" class="form-control" value="{{ old('member_number_preview', $detail?->member_no ?? $member->member_no ?? $nextMemberNumber ?? '') }}" readonly>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="branch_display">Branch</label>
            <input type="text" id="branch_display" class="form-control" value="{{ $branch->name }}" readonly>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="occupation">
                Occupation
                <span class="field-label-meta optional">Optional</span>
            </label>
            <input type="text" name="occupation" id="occupation" class="form-control @error('occupation') is-invalid @enderror" value="{{ old('occupation', $detail?->occupation ?? '') }}">
            @error('occupation')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="email">
                Email
                <span class="field-label-meta required">Required</span>
            </label>
            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $member->email ?? '') }}" required>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="mobile">
                Mobile Number
                <span class="field-label-meta required">Required</span>
            </label>
            <input type="text" name="mobile" id="mobile" class="form-control @error('mobile') is-invalid @enderror" value="{{ old('mobile', $detail?->mobile ?? '') }}" required>
            @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="date_of_birth">
                Date of Birth
                <span class="field-label-meta optional">Optional</span>
            </label>
            <input type="date" name="date_of_birth" id="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth', optional($detail?->date_of_birth)->format('Y-m-d')) }}">
            @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="gender">
                Gender
                <span class="field-label-meta optional">Optional</span>
            </label>
            <select name="gender" id="gender" class="form-control @error('gender') is-invalid @enderror">
                @php($selectedGender = old('gender', $detail?->gender ?? ''))
                <option value="">Select gender</option>
                <option value="male" @selected($selectedGender === 'male')>Male</option>
                <option value="female" @selected($selectedGender === 'female')>Female</option>
                <option value="other" @selected($selectedGender === 'other')>Other</option>
            </select>
            @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="designation_display">Designation</label>
            <input type="text" id="designation_display" class="form-control" value="Member / User" readonly>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="city">
                City
                <span class="field-label-meta optional">Optional</span>
            </label>
            <input type="text" name="city" id="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city', $detail?->city ?? '') }}">
            @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="state">
                State
                <span class="field-label-meta optional">Optional</span>
            </label>
            <input type="text" name="state" id="state" class="form-control @error('state') is-invalid @enderror" value="{{ old('state', $detail?->state ?? '') }}">
            @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-12">
        <div class="form-group">
            <label for="address">
                Address
                <span class="field-label-meta optional">Optional</span>
            </label>
            <textarea name="address" id="address" rows="3" class="form-control @error('address') is-invalid @enderror">{{ old('address', $detail?->address ?? '') }}</textarea>
            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <div class="upload-field-card">
                <label for="signature">
                    Signature
                    <span class="field-label-meta optional">Optional</span>
                </label>
                <input type="file" name="signature" id="signature" class="form-control @error('signature') is-invalid @enderror" accept="image/*">
                @error('signature')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                <div class="mt-2">
                    <img
                        id="signature-preview"
                        src="{{ old('signature_preview', isset($member) && $member->signature ? asset('storage/' . $member->signature) : '') }}"
                        alt="Signature preview"
                        style="max-width: 220px; {{ isset($member) && $member->signature ? '' : 'display:none;' }}"
                        class="img-thumbnail"
                    >
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <div class="upload-field-card">
                <label for="picture">
                    Picture
                    <span class="field-label-meta optional">Optional</span>
                </label>
                <input type="file" name="picture" id="picture" class="form-control @error('picture') is-invalid @enderror" accept="image/*">
                @error('picture')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                <div class="mt-2">
                    <img
                        id="picture-preview"
                        src="{{ old('picture_preview', isset($member) && $member->profile_picture ? asset('storage/' . $member->profile_picture) : '') }}"
                        alt="Picture preview"
                        style="max-width: 220px; {{ isset($member) && $member->profile_picture ? '' : 'display:none;' }}"
                        class="img-thumbnail"
                    >
                </div>
            </div>
        </div>
    </div>
</div>

@if ($customFields->isNotEmpty())
    <hr>
    <h5 class="mb-3">Custom Fields</h5>
    <div class="row">
        @foreach ($customFields as $field)
            @include('members._custom-field', ['field' => $field, 'storedCustomFields' => $storedCustomFields])
        @endforeach
    </div>
@endif

<hr>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Documents</h5>
    <button type="button" class="btn btn-outline-primary btn-sm" id="add-document-row">Add Document</button>
</div>

@if (isset($member) && $member->documents->isNotEmpty())
    <div class="mb-4">
        <h6>Existing Documents</h6>
        @foreach ($member->documents as $index => $document)
            <div class="border rounded p-3 mb-2">
                <input type="hidden" name="existing_documents[{{ $index }}][id]" value="{{ $document->id }}">
                <div class="row">
                    <div class="col-md-5">
                        <label>Document Name</label>
                        <input type="text" name="existing_documents[{{ $index }}][name]" class="form-control" value="{{ old("existing_documents.{$index}.name", $document->name) }}">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <a href="{{ asset('storage/' . $document->document) }}" target="_blank" class="btn btn-outline-secondary">View Document</a>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input type="checkbox" name="existing_documents[{{ $index }}][keep]" value="1" class="form-check-input" id="keep_document_{{ $index }}" {{ old("existing_documents.{$index}.keep", 1) ? 'checked' : '' }}>
                            <label for="keep_document_{{ $index }}" class="form-check-label">Keep document</label>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

<div id="document-rows">
    <div class="document-row border rounded p-3 mb-2">
        <div class="row">
            <div class="col-md-5">
                <label>Document Name</label>
                <input type="text" name="documents[0][name]" class="form-control" placeholder="e.g. ID Card">
            </div>
            <div class="col-md-5">
                <label>Document File</label>
                <input type="file" name="documents[0][file]" class="form-control-file">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-outline-danger btn-block remove-document-row">Remove</button>
            </div>
        </div>
    </div>
</div>

<div class="mt-4">
    <button type="submit" class="btn btn-primary">{{ $submitLabel ?? 'Save Member' }}</button>
    <a href="{{ route('members.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>

@push('scripts')
    <script>
        (function () {
            const bindPreview = (inputId, imageId) => {
                const input = document.getElementById(inputId);
                const image = document.getElementById(imageId);

                if (! input || ! image) {
                    return;
                }

                input.addEventListener('change', function (event) {
                    const [file] = event.target.files || [];

                    if (! file) {
                        return;
                    }

                    image.src = URL.createObjectURL(file);
                    image.style.display = 'block';
                });
            };

            bindPreview('signature', 'signature-preview');
            bindPreview('picture', 'picture-preview');

            const container = document.getElementById('document-rows');
            const addButton = document.getElementById('add-document-row');

            if (! container || ! addButton) {
                return;
            }

            let index = container.querySelectorAll('.document-row').length;

            const bindRemoveButtons = () => {
                container.querySelectorAll('.remove-document-row').forEach(function (button) {
                    button.onclick = function () {
                        const rows = container.querySelectorAll('.document-row');

                        if (rows.length === 1) {
                            rows[0].querySelectorAll('input').forEach(function (input) {
                                input.value = '';
                            });
                            return;
                        }

                        button.closest('.document-row').remove();
                    };
                });
            };

            addButton.addEventListener('click', function () {
                const row = document.createElement('div');
                row.className = 'document-row border rounded p-3 mb-2';
                row.innerHTML = `
                    <div class="row">
                        <div class="col-md-5">
                            <label>Document Name</label>
                            <input type="text" name="documents[${index}][name]" class="form-control" placeholder="e.g. Utility Bill">
                        </div>
                        <div class="col-md-5">
                            <label>Document File</label>
                            <input type="file" name="documents[${index}][file]" class="form-control-file">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger btn-block remove-document-row">Remove</button>
                        </div>
                    </div>
                `;

                container.appendChild(row);
                index += 1;
                bindRemoveButtons();
            });

            bindRemoveButtons();
        })();
    </script>
@endpush
