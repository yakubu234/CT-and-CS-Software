@php($customField = $customField ?? null)

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="field_name">Field Name</label>
            <input
                type="text"
                name="field_name"
                id="field_name"
                class="form-control @error('field_name') is-invalid @enderror"
                value="{{ old('field_name', $customField->field_name ?? '') }}"
                required
            >
            @error('field_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="field_type">Field Type</label>
            <select name="field_type" id="field_type" class="form-control @error('field_type') is-invalid @enderror" required>
                @php($selectedFieldType = old('field_type', $customField->field_type ?? 'text'))
                @foreach ([
                    'text' => 'Textbox',
                    'number' => 'Number',
                    'select' => 'Select Box',
                    'textarea' => 'Textarea',
                    'file' => 'File',
                ] as $value => $label)
                    <option value="{{ $value }}" @selected($selectedFieldType === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('field_type')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="default_value">Input Default Name / Value</label>
            <input
                type="text"
                name="default_value"
                id="default_value"
                class="form-control @error('default_value') is-invalid @enderror"
                value="{{ old('default_value', $customField->default_value ?? '') }}"
            >
            @error('default_value')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6" id="max-size-wrapper">
        <div class="form-group">
            <label for="max_size">Maximum File Size (KB)</label>
            <input
                type="number"
                name="max_size"
                id="max_size"
                class="form-control @error('max_size') is-invalid @enderror"
                value="{{ old('max_size', $customField->max_size ?? '') }}"
                min="1"
            >
            @error('max_size')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-12" id="options-wrapper">
        <div class="form-group">
            <label for="options">Select Options</label>
            <textarea
                name="options"
                id="options"
                rows="3"
                class="form-control @error('options') is-invalid @enderror"
                placeholder="Enter options separated by commas or new lines"
            >{{ old('options', $customField->options ?? '') }}</textarea>
            @error('options')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="is_required">Required</label>
            <select name="is_required" id="is_required" class="form-control @error('is_required') is-invalid @enderror">
                @php($selectedRequired = old('is_required', $customField->is_required ?? 'nullable'))
                <option value="nullable" @selected($selectedRequired === 'nullable')>Optional</option>
                <option value="required" @selected($selectedRequired === 'required')>Required</option>
            </select>
            @error('is_required')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-control @error('status') is-invalid @enderror">
                @php($selectedStatus = (string) old('status', isset($customField) ? (int) $customField->status : '1'))
                <option value="1" @selected($selectedStatus === '1')>Active</option>
                <option value="0" @selected($selectedStatus === '0')>Inactive</option>
            </select>
            @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="card-footer px-0 pb-0">
    <button type="submit" class="btn btn-primary">{{ $submitLabel ?? 'Save Custom Field' }}</button>
    <a href="{{ route('members.custom-fields.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>

@push('scripts')
    <script>
        (function () {
            const fieldType = document.getElementById('field_type');
            const optionsWrapper = document.getElementById('options-wrapper');
            const maxSizeWrapper = document.getElementById('max-size-wrapper');

            if (! fieldType || ! optionsWrapper || ! maxSizeWrapper) {
                return;
            }

            const toggleInputs = () => {
                const type = fieldType.value;
                optionsWrapper.style.display = type === 'select' ? '' : 'none';
                maxSizeWrapper.style.display = type === 'file' ? '' : 'none';
            };

            fieldType.addEventListener('change', toggleInputs);
            toggleInputs();
        })();
    </script>
@endpush
