@php
    $isReadonly = $mode === 'show';
    $fieldValue = fn (string $key, $default = null) => old($key, $default);
    $selectDisabled = $isReadonly ? 'disabled' : null;
@endphp

<div class="card card-outline card-primary">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="name">
                    Name
                    <span class="field-label-meta required">Static</span>
                </label>
                <input
                    type="text"
                    id="name"
                    class="form-control"
                    value="{{ $accountType->name }}"
                    disabled
                >
            </div>

            <div class="col-md-6 mb-3">
                <label for="account_number_prefix">
                    Account Number Prefix
                    <span class="field-label-meta required">Static</span>
                </label>
                <input
                    type="text"
                    id="account_number_prefix"
                    class="form-control"
                    value="{{ $accountType->account_number_prefix }}"
                    disabled
                >
            </div>

            <div class="col-md-6 mb-3">
                <label for="next_account_number">
                    Next Account Number
                    <span class="field-label-meta required">Static</span>
                </label>
                <input
                    type="text"
                    id="next_account_number"
                    class="form-control"
                    value="{{ $accountType->next_account_number }}"
                    disabled
                >
            </div>

            <div class="col-md-6 mb-3">
                <label for="currency_id">
                    Currency
                    <span class="field-label-meta required">Required</span>
                </label>
                <select
                    name="currency_id"
                    id="currency_id"
                    class="form-control @error('currency_id') is-invalid @enderror"
                    {{ $selectDisabled }}
                >
                    @foreach ($currencies as $currency)
                        <option value="{{ $currency->id }}" @selected((int) $fieldValue('currency_id', $accountType->currency_id) === (int) $currency->id)>
                            {{ $currency->name }}
                        </option>
                    @endforeach
                </select>
                @error('currency_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label for="interest_rate">
                    Yearly Interest Rate (%)
                    <span class="field-label-meta optional">Optional</span>
                </label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="interest_rate"
                    id="interest_rate"
                    class="form-control @error('interest_rate') is-invalid @enderror"
                    value="{{ $fieldValue('interest_rate', $accountType->interest_rate) }}"
                    @disabled($isReadonly)
                >
                @error('interest_rate')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label for="interest_period">
                    Interest Period
                    <span class="field-label-meta optional">Optional</span>
                </label>
                <select
                    name="interest_period"
                    id="interest_period"
                    class="form-control @error('interest_period') is-invalid @enderror"
                    {{ $selectDisabled }}
                >
                    <option value="">Select period</option>
                    @foreach ($interestPeriodOptions as $value => $label)
                        <option value="{{ $value }}" @selected((string) $fieldValue('interest_period', $accountType->interest_period) === (string) $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('interest_period')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label for="interest_method">
                    Interest Method
                    <span class="field-label-meta optional">Optional</span>
                </label>
                <select
                    name="interest_method"
                    id="interest_method"
                    class="form-control @error('interest_method') is-invalid @enderror"
                    {{ $selectDisabled }}
                >
                    <option value="">Select method</option>
                    @foreach ($interestMethodOptions as $value => $label)
                        <option value="{{ $value }}" @selected($fieldValue('interest_method', $accountType->interest_method) === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('interest_method')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label for="min_bal_interest_rate">
                    Minimum Balance for Interest
                    <span class="field-label-meta optional">Optional</span>
                </label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="min_bal_interest_rate"
                    id="min_bal_interest_rate"
                    class="form-control @error('min_bal_interest_rate') is-invalid @enderror"
                    value="{{ $fieldValue('min_bal_interest_rate', $accountType->min_bal_interest_rate) }}"
                    @disabled($isReadonly)
                >
                @error('min_bal_interest_rate')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label for="allow_withdraw">
                    Allow Withdraw
                    <span class="field-label-meta required">Required</span>
                </label>
                <select
                    name="allow_withdraw"
                    id="allow_withdraw"
                    class="form-control @error('allow_withdraw') is-invalid @enderror"
                    {{ $selectDisabled }}
                >
                    @foreach ($yesNoOptions as $value => $label)
                        <option value="{{ $value }}" @selected((string) $fieldValue('allow_withdraw', $accountType->allow_withdraw) === (string) $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('allow_withdraw')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label for="minimum_deposit_amount">
                    Minimum Deposit Amount
                    <span class="field-label-meta optional">Optional</span>
                </label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="minimum_deposit_amount"
                    id="minimum_deposit_amount"
                    class="form-control @error('minimum_deposit_amount') is-invalid @enderror"
                    value="{{ $fieldValue('minimum_deposit_amount', $accountType->minimum_deposit_amount) }}"
                    @disabled($isReadonly)
                >
                @error('minimum_deposit_amount')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label for="minimum_account_balance">
                    Minimum Account Balance
                    <span class="field-label-meta optional">Optional</span>
                </label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="minimum_account_balance"
                    id="minimum_account_balance"
                    class="form-control @error('minimum_account_balance') is-invalid @enderror"
                    value="{{ $fieldValue('minimum_account_balance', $accountType->minimum_account_balance) }}"
                    @disabled($isReadonly)
                >
                @error('minimum_account_balance')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label for="maintenance_fee">
                    Maintenance Fee
                    <span class="field-label-meta optional">Optional</span>
                </label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="maintenance_fee"
                    id="maintenance_fee"
                    class="form-control @error('maintenance_fee') is-invalid @enderror"
                    value="{{ $fieldValue('maintenance_fee', $accountType->maintenance_fee) }}"
                    @disabled($isReadonly)
                >
                @error('maintenance_fee')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label for="maintenance_fee_posting_period">
                    Maintenance Fee Will Be Deducted
                    <span class="field-label-meta optional">Optional</span>
                </label>
                <select
                    name="maintenance_fee_posting_period"
                    id="maintenance_fee_posting_period"
                    class="form-control @error('maintenance_fee_posting_period') is-invalid @enderror"
                    {{ $selectDisabled }}
                >
                    <option value="">Select period</option>
                    @foreach ($interestPeriodOptions as $value => $label)
                        <option value="{{ $value }}" @selected((string) $fieldValue('maintenance_fee_posting_period', $accountType->maintenance_fee_posting_period) === (string) $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('maintenance_fee_posting_period')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label for="status">
                    Status
                    <span class="field-label-meta required">Required</span>
                </label>
                <select
                    name="status"
                    id="status"
                    class="form-control @error('status') is-invalid @enderror"
                    {{ $selectDisabled }}
                >
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected((string) $fieldValue('status', $accountType->status) === (string) $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    @if ($mode === 'edit')
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('account-types.show', $accountType) }}" class="btn btn-light">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i>
                Update Account Type
            </button>
        </div>
    @endif
</div>
