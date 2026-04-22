@extends('layouts.admin')

@section('title', 'Edit Loan Request')
@section('page_title', 'Edit Loan Request')

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
            <h3 class="card-title">Edit Loan Request</h3>
            <div class="card-tools">
                <a href="{{ route('loans.requests.show', $loanDetail) }}" class="btn btn-sm btn-outline-secondary">Back</a>
            </div>
        </div>

        <form action="{{ route('loans.requests.update', $loanDetail) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Editing rules:</strong>
                    You can edit this loan request, but the borrower cannot be changed because loan history is tied to that member.
                    @if ($totalRepaid > 0)
                        This loan already has repayments of <strong>&#8358;{{ number_format($totalRepaid, 2) }}</strong>, so the amount cannot be reduced below that figure.
                        If you want to close the loan now, set the loan amount to match the amount already repaid.
                    @endif
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Borrower</label>
                            <input
                                type="text"
                                class="form-control"
                                value="{{ ($borrower?->detail?->member_no ?: $borrower?->member_no ?: 'N/A') . ' ' . ($borrower?->name ?: 'N/A') }}"
                                disabled
                            >
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
                            <input
                                type="date"
                                name="release_date"
                                id="release_date"
                                class="form-control @error('release_date') is-invalid @enderror"
                                value="{{ old('release_date', optional($loanDetail->release_date)->format('Y-m-d')) }}"
                                required
                            >
                            @error('release_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="due_date">Due Date</label>
                            <input
                                type="date"
                                name="due_date"
                                id="due_date"
                                class="form-control @error('due_date') is-invalid @enderror"
                                value="{{ old('due_date', optional($loanDetail->due_date)->format('Y-m-d')) }}"
                                required
                            >
                            @error('due_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="amount">Amount</label>
                            <input
                                type="number"
                                step="0.01"
                                min="{{ $totalRepaid > 0 ? number_format($totalRepaid, 2, '.', '') : '0.01' }}"
                                name="amount"
                                id="amount"
                                class="form-control @error('amount') is-invalid @enderror"
                                value="{{ old('amount', number_format((float) $loanDetail->applied_amount, 2, '.', '')) }}"
                                required
                            >
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
                                    <option value="{{ $value }}" @selected(old('interest_week_interval', $loanDetail->interest_week_interval) === $value)>{{ $label }}</option>
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
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                name="late_payment_penalties"
                                id="late_payment_penalties"
                                class="form-control @error('late_payment_penalties') is-invalid @enderror"
                                value="{{ old('late_payment_penalties', $loanDetail->late_payment_penalties !== null ? number_format((float) $loanDetail->late_payment_penalties, 2, '.', '') : '') }}"
                            >
                            @error('late_payment_penalties')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="attachment">
                                Replace Attachment
                                <span class="optional-label">(Optional)</span>
                            </label>
                            <input type="file" name="attachment" id="attachment" class="form-control @error('attachment') is-invalid @enderror">
                            @error('attachment')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            @if ($loanDetail->attachment)
                                <small class="form-text text-muted">
                                    Current file:
                                    <a href="{{ asset('storage/' . $loanDetail->attachment) }}" target="_blank">View attachment</a>
                                </small>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="alert alert-light border">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="small text-muted">Current Outstanding</div>
                            <div id="current-outstanding" class="h5 mb-0">&#8358;{{ number_format($currentOutstanding, 2) }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-muted">Already Repaid</div>
                            <div class="h5 mb-0 text-success">&#8358;{{ number_format($totalRepaid, 2) }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-muted">Outstanding After Edit</div>
                            <div id="projected-outstanding" class="h5 mb-0 text-info">&#8358;{{ number_format($projectedOutstanding, 2) }}</div>
                        </div>
                    </div>
                </div>

                @if ($customFields->isNotEmpty())
                    <hr>
                    <h5 class="mb-3">Loan Custom Fields</h5>
                    <div class="row">
                        @foreach ($customFields as $field)
                            @php
                                $existingValue = $loanDetail->custom_fields[$field->id]['value'] ?? null;
                                $fieldValue = old("custom_fields.{$field->id}", $existingValue ?? $field->default_value);
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
                                        @if ($existingValue)
                                            <small class="form-text text-muted">
                                                Current file:
                                                <a href="{{ asset('storage/' . $existingValue) }}" target="_blank">View file</a>
                                            </small>
                                        @endif
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
                <button type="submit" class="btn btn-primary">Update Loan Request</button>
                <a href="{{ route('loans.requests.show', $loanDetail) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const amountInput = document.getElementById('amount');
            const currentOutstanding = document.getElementById('current-outstanding');
            const projectedOutstanding = document.getElementById('projected-outstanding');
            const originalAmount = {{ number_format((float) $loanDetail->applied_amount, 2, '.', '') }};
            const totalRepaid = {{ number_format((float) $totalRepaid, 2, '.', '') }};
            const currentOutstandingValue = {{ number_format((float) $currentOutstanding, 2, '.', '') }};

            if (! amountInput || ! currentOutstanding || ! projectedOutstanding) {
                return;
            }

            const formatMoney = value => `₦${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

            const updatePreview = () => {
                const amount = Number(amountInput.value || 0);
                const projected = Math.max(currentOutstandingValue - (originalAmount - totalRepaid) + (amount - totalRepaid), 0);

                currentOutstanding.textContent = formatMoney(currentOutstandingValue);
                projectedOutstanding.textContent = formatMoney(projected);
            };

            amountInput.addEventListener('input', updatePreview);
            updatePreview();
        })();
    </script>
@endpush
