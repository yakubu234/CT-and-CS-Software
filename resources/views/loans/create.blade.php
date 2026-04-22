@extends('layouts.admin')

@section('title', 'Create Loan Request')
@section('page_title', 'Create Loan Request')

@push('styles')
    <style>
        .optional-label {
            color: #64748b;
            font-size: 0.85rem;
            font-weight: 500;
        }
    </style>
@endpush

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">New Loan Request</h3>
            <div class="card-tools">
                <span class="text-muted small">Branch: {{ $branch->name }}</span>
            </div>
        </div>

        <form action="{{ route('loans.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="borrower_id">Select Borrower</label>
                            <select name="borrower_id" id="borrower_id" class="form-control @error('borrower_id') is-invalid @enderror" required>
                                <option value="">Choose borrower</option>
                                @foreach ($borrowers as $borrower)
                                    <option
                                        value="{{ $borrower['id'] }}"
                                        data-outstanding="{{ $borrower['outstanding'] }}"
                                        @selected((string) old('borrower_id') === (string) $borrower['id'])
                                    >
                                        {{ $borrower['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('borrower_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Current Branch</label>
                            <input type="text" class="form-control" value="{{ $branch->name }}" disabled>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="release_date">Release Date</label>
                            <input type="date" name="release_date" id="release_date" class="form-control @error('release_date') is-invalid @enderror" value="{{ old('release_date') }}" required>
                            @error('release_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="due_date">Due Date</label>
                            <input type="date" name="due_date" id="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date') }}" required>
                            @error('due_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="amount">Amount</label>
                            <input type="number" step="0.01" min="0.01" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="interest_week_interval">Interest Week Interval</label>
                            <select name="interest_week_interval" id="interest_week_interval" class="form-control @error('interest_week_interval') is-invalid @enderror" required>
                                <option value="">Choose interval</option>
                                @foreach ($interestWeekIntervals as $value => $label)
                                    <option value="{{ $value }}" @selected(old('interest_week_interval') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('interest_week_interval')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="late_payment_penalties">
                                Late Payment Penalties
                                <span class="optional-label">(Optional)</span>
                            </label>
                            <input type="number" step="0.01" min="0" name="late_payment_penalties" id="late_payment_penalties" class="form-control @error('late_payment_penalties') is-invalid @enderror" value="{{ old('late_payment_penalties') }}">
                            @error('late_payment_penalties')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="attachment">
                                Attachment
                                <span class="optional-label">(Optional)</span>
                            </label>
                            <input type="file" name="attachment" id="attachment" class="form-control @error('attachment') is-invalid @enderror">
                            @error('attachment')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="alert alert-light border">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="small text-muted">Current Outstanding</div>
                            <div id="current-outstanding" class="h5 mb-0">&#8358;0.00</div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-muted">Outstanding If Approved</div>
                            <div id="projected-outstanding" class="h5 mb-0 text-info">&#8358;0.00</div>
                        </div>
                    </div>
                </div>

                @if ($customFields->isNotEmpty())
                    <hr>
                    <h5 class="mb-3">Loan Custom Fields</h5>
                    <div class="row">
                        @foreach ($customFields as $field)
                            @php
                                $fieldValue = old("custom_fields.{$field->id}", $field->default_value);
                            @endphp
                            <div class="{{ $field->field_width ?: 'col-md-6' }}">
                                <div class="form-group">
                                    <label for="custom_field_{{ $field->id }}">
                                        {{ $field->field_name }}
                                        @if ($field->is_required === 'required')
                                            <span class="text-danger">*</span>
                                        @else
                                            <span class="optional-label">(Optional)</span>
                                        @endif
                                    </label>

                                    @if ($field->field_type === 'textarea')
                                        <textarea
                                            name="custom_fields[{{ $field->id }}]"
                                            id="custom_field_{{ $field->id }}"
                                            class="form-control @error("custom_fields.{$field->id}") is-invalid @enderror"
                                            rows="3"
                                        >{{ $fieldValue }}</textarea>
                                    @elseif ($field->field_type === 'select')
                                        <select
                                            name="custom_fields[{{ $field->id }}]"
                                            id="custom_field_{{ $field->id }}"
                                            class="form-control @error("custom_fields.{$field->id}") is-invalid @enderror"
                                        >
                                            <option value="">Select {{ strtolower($field->field_name) }}</option>
                                            @foreach ($field->optionsList() as $option)
                                                <option value="{{ $option }}" @selected((string) $fieldValue === (string) $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    @elseif ($field->field_type === 'file')
                                        <input
                                            type="file"
                                            name="custom_fields[{{ $field->id }}]"
                                            id="custom_field_{{ $field->id }}"
                                            class="form-control @error("custom_fields.{$field->id}") is-invalid @enderror"
                                        >
                                    @else
                                        <input
                                            type="{{ $field->field_type === 'number' ? 'number' : 'text' }}"
                                            step="{{ $field->field_type === 'number' ? '0.01' : null }}"
                                            name="custom_fields[{{ $field->id }}]"
                                            id="custom_field_{{ $field->id }}"
                                            class="form-control @error("custom_fields.{$field->id}") is-invalid @enderror"
                                            value="{{ $fieldValue }}"
                                        >
                                    @endif

                                    @error("custom_fields.{$field->id}")
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Save Loan Request</button>
                <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const borrowerSelect = document.getElementById('borrower_id');
            const amountInput = document.getElementById('amount');
            const currentOutstanding = document.getElementById('current-outstanding');
            const projectedOutstanding = document.getElementById('projected-outstanding');

            if (! borrowerSelect || ! amountInput || ! currentOutstanding || ! projectedOutstanding) {
                return;
            }

            const formatMoney = value => `₦${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

            const updatePreview = () => {
                const selectedOption = borrowerSelect.options[borrowerSelect.selectedIndex];
                const outstanding = Number(selectedOption?.dataset?.outstanding || 0);
                const amount = Number(amountInput.value || 0);

                currentOutstanding.textContent = formatMoney(outstanding);
                projectedOutstanding.textContent = formatMoney(outstanding + amount);
            };

            borrowerSelect.addEventListener('change', updatePreview);
            amountInput.addEventListener('input', updatePreview);
            updatePreview();
        })();
    </script>
@endpush
