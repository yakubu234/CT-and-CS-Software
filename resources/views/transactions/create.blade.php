@extends('layouts.admin')

@section('title', 'New Transaction')
@section('page_title', 'New Transaction')

@php
    $memberJsMap = $members->mapWithKeys(function ($member) {
        return [
            $member->id => [
                'id' => $member->id,
                'name' => $member->name,
                'member_no' => $member->detail?->member_no ?: $member->member_no,
                'accounts' => $member->savingsAccounts->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'account_number' => $account->account_number,
                        'type' => $account->product?->type,
                        'balance' => (float) $account->balance,
                        'label' => $account->account_number . ' (' . ($account->product?->type ?: 'N/A') . ')',
                    ];
                })->values()->all(),
            ],
        ];
    })->all();
@endphp

@push('styles')
    <style>
        .transaction-shell {
            display: grid;
            gap: 1.5rem;
        }

        .transaction-hero {
            border: 1px solid #dbe5f0;
            border-radius: 1rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, #f8fbff 0%, #eef6ff 55%, #f6fffb 100%);
        }

        .transaction-hero-kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.08);
            color: #1d4ed8;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .transaction-hero h3 {
            margin: 1rem 0 0.4rem;
            font-size: 1.55rem;
            font-weight: 700;
            color: #0f172a;
        }

        .transaction-hero p {
            margin: 0;
            max-width: 46rem;
            color: #475569;
        }

        .transaction-hero-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.85rem;
            margin-top: 1.25rem;
        }

        .transaction-hero-stat {
            border-radius: 0.9rem;
            padding: 0.9rem 1rem;
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(148, 163, 184, 0.2);
        }

        .transaction-hero-stat-label {
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 0.35rem;
        }

        .transaction-hero-stat-value {
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
        }

        .transaction-block {
            border: 1px solid #dbe5f0;
            border-radius: 1rem;
            background: #fff;
            overflow: hidden;
        }

        .transaction-block-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 1.15rem 1.25rem;
            border-bottom: 1px solid #e5edf5;
            background: #f8fafc;
        }

        .transaction-block-header h4,
        .transaction-block-header h5 {
            margin: 0;
            color: #0f172a;
            font-weight: 700;
        }

        .transaction-block-header p,
        .transaction-block-header small {
            margin: 0.25rem 0 0;
            color: #64748b;
        }

        .transaction-block-body {
            padding: 1.25rem;
        }

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

        .transaction-entry-card {
            border: 1px solid #dbe5f0;
            border-radius: 1rem;
            padding: 1.1rem;
            margin-bottom: 1rem;
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .transaction-entry-card:last-child {
            margin-bottom: 0;
        }

        .transaction-entry-card-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px dashed #d7e3ef;
        }

        .transaction-entry-card-header h5 {
            margin: 0;
            color: #0f172a;
            font-weight: 700;
        }

        .transaction-entry-subtitle {
            font-size: 0.88rem;
            color: #64748b;
            margin-top: 0.2rem;
        }

        .entry-type-badge {
            display: inline-flex;
            align-items: center;
            min-height: 42px;
            width: 100%;
            padding: 0.6rem 0.8rem;
            border-radius: 0.75rem;
            border: 1px dashed #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
            font-weight: 700;
        }

        .entry-balance-panel {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .entry-balance-box {
            border: 1px solid #dbe5f0;
            border-radius: 0.85rem;
            padding: 0.85rem 0.95rem;
            background: #fff;
        }

        .entry-balance-box-label {
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            margin-bottom: 0.35rem;
        }

        .entry-balance-box-value {
            font-size: 1.15rem;
            font-weight: 700;
            color: #0f172a;
        }

        .entry-balance-box-value.is-positive {
            color: #0891b2;
        }

        .entry-balance-box-value.is-negative {
            color: #dc2626;
        }

        .transaction-summary-card {
            border: 1px solid #dbe5f0;
            border-radius: 1rem;
            padding: 1.15rem;
            background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 50%, #f5fffb 100%);
        }

        .summary-stat {
            border-radius: 0.85rem;
            padding: 0.9rem 1rem;
            background: rgba(255, 255, 255, 0.78);
            border: 1px solid rgba(148, 163, 184, 0.18);
        }

        .summary-stat-label {
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            margin-bottom: 0.35rem;
        }

        .summary-stat-value {
            font-size: 1.35rem;
            font-weight: 700;
            color: #0f172a;
        }

        .transaction-form-footer {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem 1.25rem;
        }

        .transaction-form-footer-copy {
            color: #64748b;
            margin: 0;
        }

        @media (max-width: 767.98px) {
            .transaction-hero,
            .transaction-block-body,
            .transaction-block-header,
            .transaction-form-footer {
                padding: 1rem;
            }
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

    <form action="{{ route('transactions.store') }}" method="POST" id="transaction-create-form">
        @csrf

        <div class="transaction-shell">
            <section class="transaction-hero">
                <span class="transaction-hero-kicker">
                    <i class="fas fa-receipt"></i>
                    Manual Transaction Entry
                </span>
                <h3>Create a member transaction batch</h3>
                <p>
                    Choose the member, set the transaction date, then add one or more account entries. The running
                    totals below will help staff confirm everything before saving.
                </p>

                <div class="transaction-hero-grid">
                    <div class="transaction-hero-stat">
                        <div class="transaction-hero-stat-label">Active Branch</div>
                        <div class="transaction-hero-stat-value">{{ $branch->name }}</div>
                    </div>
                    <div class="transaction-hero-stat">
                        <div class="transaction-hero-stat-label">Entry Limit</div>
                        <div class="transaction-hero-stat-value">Up to 4 accounts</div>
                    </div>
                    <div class="transaction-hero-stat">
                        <div class="transaction-hero-stat-label">Date Flexibility</div>
                        <div class="transaction-hero-stat-value">Past dates allowed</div>
                    </div>
                </div>
            </section>

            <div class="transaction-block">
                <div class="transaction-block-header">
                    <div>
                        <h4>Step 1: Member and transaction date</h4>
                        <p>Start by choosing the member and the date this transaction happened.</p>
                    </div>
                </div>
                <div class="transaction-block-body">
                    <div class="row">
                        <div class="col-lg-6 mb-3">
                            <label for="member_id">
                                Select Member
                                <span class="field-label-meta required">Required</span>
                            </label>
                            <select name="member_id" id="member_id" class="form-control @error('member_id') is-invalid @enderror">
                                <option value="">Choose member</option>
                                @foreach ($members as $member)
                                    <option value="{{ $member->id }}" @selected((string) old('member_id') === (string) $member->id)>
                                        {{ $member->detail?->member_no ?: $member->member_no ?: 'N/A' }} {{ $member->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('member_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

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
                                value="{{ old('trans_date', now()->format('Y-m-d')) }}"
                            >
                            @error('trans_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="transaction-block">
                <div class="transaction-block-header">
                    <div>
                        <h5>Step 2: Add account entries</h5>
                        <small>You can record up to 4 account movements for this member in one submission.</small>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="add-entry-btn">
                        <i class="fas fa-plus mr-1"></i>
                        Add Account Entry
                    </button>
                </div>
                <div class="transaction-block-body">
                    <div id="entries-wrapper"></div>

                    <div class="transaction-summary-card mt-4">
                        <div class="row">
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="summary-stat">
                                    <div class="summary-stat-label">Total Entries</div>
                                    <div class="summary-stat-value" id="summary-entry-count">0</div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="summary-stat">
                                    <div class="summary-stat-label">Overall Credit</div>
                                    <div class="summary-stat-value text-info" id="summary-total-credit">&#8358;0.00</div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="summary-stat">
                                    <div class="summary-stat-label">Overall Debit</div>
                                    <div class="summary-stat-value text-danger" id="summary-total-debit">&#8358;0.00</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="summary-stat">
                                    <div class="summary-stat-label">Net Movement</div>
                                    <div class="summary-stat-value" id="summary-net-total">&#8358;0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="transaction-block">
                <div class="transaction-form-footer">
                    <p class="transaction-form-footer-copy mb-0">
                        Review the balances and totals above before saving this transaction batch.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('transactions.index') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>
                            Save Transactions
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (() => {
            const members = @json($memberJsMap);
            const oldEntries = @json($initialEntries);
            const maxEntries = 4;
            const wrapper = document.getElementById('entries-wrapper');
            const memberSelect = document.getElementById('member_id');
            const addEntryBtn = document.getElementById('add-entry-btn');

            const formatMoney = (value) => {
                return new Intl.NumberFormat('en-NG', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }).format(Number(value || 0));
            };

            const currentMember = () => members[memberSelect.value] || null;

            const selectedAccountIds = (excludeCard = null) => {
                return Array.from(wrapper.querySelectorAll('.transaction-entry-card'))
                    .filter((entryCard) => entryCard !== excludeCard)
                    .map((entryCard) => entryCard.querySelector('.entry-account')?.value || '')
                    .filter((value) => value !== '');
            };

            const accountOptions = (selectedValue = '', excludeCard = null) => {
                const member = currentMember();
                if (!member) {
                    return '<option value="">Select member first</option>';
                }

                const takenAccountIds = selectedAccountIds(excludeCard);
                const options = member.accounts.map((account) => {
                    if (takenAccountIds.includes(String(account.id)) && String(selectedValue) !== String(account.id)) {
                        return null;
                    }

                    const selected = String(selectedValue) === String(account.id) ? 'selected' : '';
                    return `<option value="${account.id}" ${selected}>${account.label}</option>`;
                }).filter(Boolean);

                return ['<option value="">Choose account number</option>', ...options].join('');
            };

            const accountData = (accountId) => {
                const member = currentMember();
                if (!member) return null;
                return member.accounts.find((account) => String(account.id) === String(accountId)) || null;
            };

            const updateEntryPreview = (entryCard) => {
                const accountSelect = entryCard.querySelector('.entry-account');
                const drCrSelect = entryCard.querySelector('.entry-drcr');
                const amountInput = entryCard.querySelector('.entry-amount');
                const typeBadge = entryCard.querySelector('.entry-type');
                const currentBalanceNode = entryCard.querySelector('.entry-current-balance');
                const projectedBalanceNode = entryCard.querySelector('.entry-projected-balance');

                const account = accountData(accountSelect.value);
                const amount = Number(amountInput.value || 0);
                const drCr = drCrSelect.value || 'cr';

                if (!account) {
                    typeBadge.textContent = 'Account type will show here';
                    currentBalanceNode.innerHTML = '&#8358;0.00';
                    projectedBalanceNode.innerHTML = '&#8358;0.00';
                    projectedBalanceNode.classList.remove('is-positive', 'is-negative');
                    return;
                }

                const currentBalance = Number(account.balance || 0);
                const projected = drCr === 'cr'
                    ? currentBalance + amount
                    : currentBalance - amount;

                typeBadge.textContent = account.type || 'N/A';
                currentBalanceNode.innerHTML = `&#8358;${formatMoney(currentBalance)}`;
                projectedBalanceNode.innerHTML = `&#8358;${formatMoney(projected)}`;
                projectedBalanceNode.classList.toggle('is-positive', projected >= 0);
                projectedBalanceNode.classList.toggle('is-negative', projected < 0);
            };

            const updateSummary = () => {
                const entryCards = wrapper.querySelectorAll('.transaction-entry-card');
                let totalCredit = 0;
                let totalDebit = 0;

                entryCards.forEach((entryCard) => {
                    const amount = Number(entryCard.querySelector('.entry-amount').value || 0);
                    const drCr = entryCard.querySelector('.entry-drcr').value || 'cr';

                    if (drCr === 'cr') {
                        totalCredit += amount;
                    } else {
                        totalDebit += amount;
                    }
                });

                document.getElementById('summary-entry-count').textContent = entryCards.length;
                document.getElementById('summary-total-credit').innerHTML = `&#8358;${formatMoney(totalCredit)}`;
                document.getElementById('summary-total-debit').innerHTML = `&#8358;${formatMoney(totalDebit)}`;

                const net = totalCredit - totalDebit;
                const netNode = document.getElementById('summary-net-total');
                netNode.innerHTML = `&#8358;${formatMoney(net)}`;
                netNode.classList.toggle('text-info', net >= 0);
                netNode.classList.toggle('text-danger', net < 0);
            };

            const bindEntryEvents = (entryCard) => {
                entryCard.querySelector('.entry-account').addEventListener('change', () => {
                    refreshAccountOptions();
                    updateEntryPreview(entryCard);
                    updateSummary();
                });

                entryCard.querySelector('.entry-drcr').addEventListener('change', () => {
                    updateEntryPreview(entryCard);
                    updateSummary();
                });

                entryCard.querySelector('.entry-amount').addEventListener('input', () => {
                    updateEntryPreview(entryCard);
                    updateSummary();
                });

                entryCard.querySelector('.remove-entry-btn').addEventListener('click', () => {
                    entryCard.remove();
                    reindexEntries();
                    refreshAccountOptions();
                    updateSummary();
                    toggleAddButton();
                });
            };

            const reindexEntries = () => {
                wrapper.querySelectorAll('.transaction-entry-card').forEach((entryCard, index) => {
                    entryCard.dataset.index = index;
                    entryCard.querySelector('.entry-title').textContent = `Entry ${index + 1}`;
                    entryCard.querySelectorAll('[data-field]').forEach((field) => {
                        const fieldName = field.dataset.field;
                        field.name = `entries[${index}][${fieldName}]`;
                    });
                });
            };

            const toggleAddButton = () => {
                addEntryBtn.disabled = wrapper.querySelectorAll('.transaction-entry-card').length >= maxEntries;
            };

            const renderEntry = (entry = {}) => {
                const index = wrapper.querySelectorAll('.transaction-entry-card').length;
                const card = document.createElement('div');
                card.className = 'transaction-entry-card';
                card.dataset.index = index;

                card.innerHTML = `
                    <div class="transaction-entry-card-header">
                        <div>
                            <h5 class="h6 mb-0 entry-title">Entry ${index + 1}</h5>
                            <div class="transaction-entry-subtitle">Choose account, direction, and amount for this line.</div>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm remove-entry-btn">
                            <i class="fas fa-trash-alt mr-1"></i>
                            Remove
                        </button>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 mb-3">
                            <label>Account Number <span class="field-label-meta required">Required</span></label>
                            <select class="form-control entry-account" data-field="savings_account_id" name="entries[${index}][savings_account_id]">
                                ${accountOptions(entry.savings_account_id || '', card)}
                            </select>
                        </div>
                        <div class="col-lg-3 mb-3">
                            <label>Credit/Debit <span class="field-label-meta required">Required</span></label>
                            <select class="form-control entry-drcr" data-field="dr_cr" name="entries[${index}][dr_cr]">
                                <option value="cr" ${(entry.dr_cr || 'cr') === 'cr' ? 'selected' : ''}>Credit</option>
                                <option value="dr" ${(entry.dr_cr || 'cr') === 'dr' ? 'selected' : ''}>Debit</option>
                            </select>
                        </div>
                        <div class="col-lg-3 mb-3">
                            <label>Type</label>
                            <div class="entry-type-badge entry-type">Account type will show here</div>
                        </div>
                        <div class="col-12">
                            <div class="entry-balance-panel">
                                <div class="entry-balance-box">
                                    <div class="entry-balance-box-label">Current Balance</div>
                                    <div class="entry-balance-box-value entry-current-balance">&#8358;0.00</div>
                                </div>
                                <div class="entry-balance-box">
                                    <div class="entry-balance-box-label">Projected Balance</div>
                                    <div class="entry-balance-box-value entry-projected-balance">&#8358;0.00</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-3">
                            <label>Amount <span class="field-label-meta required">Required</span></label>
                            <input type="number" min="0.01" step="0.01" class="form-control entry-amount" data-field="amount" name="entries[${index}][amount]" value="${entry.amount || ''}" placeholder="Enter amount">
                        </div>
                        <div class="col-lg-6 mb-3">
                            <label>Description <span class="field-label-meta optional">Optional</span></label>
                            <input type="text" class="form-control" data-field="description" name="entries[${index}][description]" value="${entry.description || ''}" placeholder="Short description">
                        </div>
                    </div>
                `;

                wrapper.appendChild(card);
                bindEntryEvents(card);
                refreshAccountOptions();
                updateEntryPreview(card);
                updateSummary();
                toggleAddButton();
            };

            const refreshAccountOptions = () => {
                wrapper.querySelectorAll('.transaction-entry-card').forEach((entryCard) => {
                    const accountSelect = entryCard.querySelector('.entry-account');
                    const selectedValue = accountSelect.value;

                    accountSelect.innerHTML = accountOptions(selectedValue, entryCard);

                    if (selectedValue && !accountData(selectedValue)) {
                        accountSelect.value = '';
                    } else {
                        accountSelect.value = selectedValue;
                    }

                    updateEntryPreview(entryCard);
                });
                updateSummary();
            };

            memberSelect.addEventListener('change', refreshAccountOptions);
            addEntryBtn.addEventListener('click', () => {
                if (wrapper.querySelectorAll('.transaction-entry-card').length >= maxEntries) return;
                renderEntry({ dr_cr: 'cr' });
            });

            if (oldEntries.length) {
                oldEntries.forEach((entry) => renderEntry(entry));
            } else {
                renderEntry({ dr_cr: 'cr' });
            }

            toggleAddButton();
        })();
    </script>
@endpush
