@php
    $fieldInputKey = 'custom_fields.' . $field->id;
    $customFieldValue = $storedCustomFields->get((string) $field->id);
    $resolvedValue = is_array($customFieldValue) ? ($customFieldValue['value'] ?? '') : $customFieldValue;
    $fieldValue = old($fieldInputKey, $resolvedValue ?: $field->default_value);
    $isTextareaField = $field->field_type === 'textarea';
    $isSelectField = $field->field_type === 'select';
    $isFileField = $field->field_type === 'file';
    $inputType = $field->field_type === 'number' ? 'number' : 'text';
    $fieldOptions = $field->optionsList();
@endphp

<div class="{{ $isTextareaField ? 'col-12' : 'col-md-6' }}">
    <div class="form-group">
        <label for="custom_field_{{ $field->id }}">
            {{ $field->field_name }}
            @if ($field->is_required === 'required')
                <span class="field-label-meta required">Required</span>
            @else
                <span class="field-label-meta optional">Optional</span>
            @endif
        </label>

        @if ($isTextareaField)
            <textarea
                name="custom_fields[{{ $field->id }}]"
                id="custom_field_{{ $field->id }}"
                rows="3"
                class="form-control"
            >{{ $fieldValue }}</textarea>
        @elseif ($isSelectField)
            <select name="custom_fields[{{ $field->id }}]" id="custom_field_{{ $field->id }}" class="form-control">
                <option value="">Select {{ strtolower($field->field_name) }}</option>
                @foreach ($fieldOptions as $option)
                    <option value="{{ $option }}" {{ (string) $fieldValue === (string) $option ? 'selected' : '' }}>
                        {{ $option }}
                    </option>
                @endforeach
            </select>
        @elseif ($isFileField)
            <input type="file" name="custom_fields[{{ $field->id }}]" id="custom_field_{{ $field->id }}" class="form-control-file">
            @if ($resolvedValue)
                <div class="mt-2">
                    <a href="{{ asset('storage/' . $resolvedValue) }}" target="_blank">View current file</a>
                </div>
            @endif
        @else
            <input
                type="{{ $inputType }}"
                name="custom_fields[{{ $field->id }}]"
                id="custom_field_{{ $field->id }}"
                class="form-control"
                value="{{ $fieldValue }}"
            >
        @endif
    </div>
</div>
