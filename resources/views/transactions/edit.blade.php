@extends('layouts.admin')

@section('title', 'Edit Transaction')
@section('page_title', 'Edit Transaction')

@push('styles')
    <style>
        .field-label-meta {
            display: inline-block;
            margin-left: 0.35rem;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .field-label-meta.required {
            color: #dc2626;
        }

        .field-label-meta.optional {
            color: #6b7280;
        }

        .transaction-edit-summary {
            border: 1px solid #dbe5f0;
            border-radius: 0.85rem;
            padding: 1rem;
            background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 100%);
        }
    </style>
@endpush

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <h5 class="mb-2"><i class="icon fas fa-ban"></i> Please fix the highlighted fields.</h5>
            <ul class="mb-0 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="alert alert-info">
        <h5 class="mb-2"><i class="icon fas fa-info-circle"></i> Edit rule</h5>
        <p class="mb-0">
            This transaction stays attached to <strong>{{ $member?->name ?: 'the original member' }}</strong>.
            You may correct the date, amount, description, credit/debit direction, or switch between this member's own
            accounts. If the transaction was posted to the wrong member, please delete it and recreate it for the rightful owner.
        </p>
    </div>

    <div class="transaction-edit-summary mb-3">
        <div class="row">
            <div class="col-md-4 mb-2 mb-md-0">
                <div class="text-muted small">Member</div>
                <div class="font-weight-bold">{{ $member?->name ?: 'N/A' }}</div>
            </div>
            <div class="col-md-4 mb-2 mb-md-0">
                <div class="text-muted small">Member No</div>
                <div class="font-weight-bold">{{ $member?->detail?->member_no ?: $member?->member_no ?: 'N/A' }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Current Account</div>
                <div class="font-weight-bold">{{ $transaction->account?->account_number ?: 'N/A' }}</div>
            </div>
        </div>
    </div>

    <form action="{{ route('transactions.update', $transaction) }}" method="POST" id="transaction-edit-form">
        @csrf
        @method('PUT')

        <div class="card card-outline card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="trans_date">
                            Transaction Date
                            <span class="field-label-meta required">Required</span>
                        </label>
                        <input
                            type="date"
                            name="trans_date"
                            id="trans_date"
                            class="form-control @error('trans_date') is-invalid @enderror"
                            value="{{ old('trans_date', optional($transaction->trans_date)->format('Y-m-d')) }}"
                        >
                        @error('trans_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label for="savings_account_id">
                            Account Number
                            <span class="field-label-meta required">Required</span>
                        </label>
                        <select name="savings_account_id" id="savings_account_id" class="form-control @error('savings_account_id') is-invalid @enderror">
                            @foreach ($memberAccounts as $account)
                                <option
                                    value="{{ $account->id }}"
                                    data-balance="{{ (float) $account->balance }}"
                                    data-type="{{ $account->product?->type }}"
                                    @selected((string) old('savings_account_id', $transaction->savings_account_id) === (string) $account->id)
                                >
                                    {{ $account->account_number }} ({{ $account->product?->type ?: 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                        @error('savings_account_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-lg-4 mb-3">
                        <label for="dr_cr">
                            Credit/Debit
                            <span class="field-label-meta required">Required</span>
                        </label>
                        <select name="dr_cr" id="dr_cr" class="form-control @error('dr_cr') is-invalid @enderror">
                            <option value="cr" @selected(old('dr_cr', strtolower($transaction->dr_cr)) === 'cr')>Credit</option>
                            <option value="dr" @selected(old('dr_cr', strtolower($transaction->dr_cr)) === 'dr')>Debit</option>
                        </select>
                        @error('dr_cr')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-lg-4 mb-3">
                        <label>Type</label>
                        <div class="form-control bg-light" id="transaction-type-preview">{{ $transaction->account?->product?->type ?: 'N/A' }}</div>
                    </div>

                    <div class="col-lg-4 mb-3">
                        <label for="amount">
                            Amount
                            <span class="field-label-meta required">Required</span>
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            min="0.01"
                            name="amount"
                            id="amount"
                            class="form-control @error('amount') is-invalid @enderror"
                            value="{{ old('amount', number_format((float) $transaction->amount, 2, '.', '')) }}"
                        >
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 mb-3">
                        <div class="text-muted" id="balance-preview-line">Current balance and projected balance will show here.</div>
                    </div>

                    <div class="col-12 mb-3">
                        <label for="description">
                            Description
                            <span class="field-label-meta optional">Optional</span>
                        </label>
                        <input
                            type="text"
                            name="description"
                            id="description"
                            class="form-control @error('description') is-invalid @enderror"
                            value="{{ old('description', $transaction->description) }}"
                        >
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-between">
                <div>
                    <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-light">Cancel</a>
                </div>
                <div class="d-flex">
                    <button type="submit" class="btn btn-primary mr-2">
                        <i class="fas fa-save mr-1"></i>
                        Update Transaction
                    </button>
                    <button
                        type="submit"
                        class="btn btn-outline-danger"
                        form="transaction-delete-form"
                        onclick="return confirm('Delete this transaction? The account balance will be adjusted automatically.')"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </form>

    <form action="{{ route('transactions.destroy', $transaction) }}" method="POST" id="transaction-delete-form" class="d-none">
        @csrf
        @method('DELETE')
    </form>
@endsection

@push('scripts')
    <script>
        (() => {
            const accountSelect = document.getElementById('savings_account_id');
            const drCrSelect = document.getElementById('dr_cr');
            const amountInput = document.getElementById('amount');
            const typePreview = document.getElementById('transaction-type-preview');
            const balancePreview = document.getElementById('balance-preview-line');

            const formatMoney = (value) => new Intl.NumberFormat('en-NG', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(Number(value || 0));

            const refreshPreview = () => {
                const option = accountSelect.options[accountSelect.selectedIndex];
                const balance = Number(option?.dataset.balance || 0);
                const type = option?.dataset.type || 'N/A';
                const amount = Number(amountInput.value || 0);
                const drCr = drCrSelect.value || 'cr';
                const projected = drCr === 'cr' ? balance + amount : balance - amount;

                typePreview.textContent = type;
                balancePreview.innerHTML = `Current balance: <strong>&#8358;${formatMoney(balance)}</strong> | Projected balance: <strong class="${projected >= 0 ? 'text-info' : 'text-danger'}">&#8358;${formatMoney(projected)}</strong>`;
            };

            accountSelect.addEventListener('change', refreshPreview);
            drCrSelect.addEventListener('change', refreshPreview);
            amountInput.addEventListener('input', refreshPreview);
            refreshPreview();
        })();
    </script>
@endpush
