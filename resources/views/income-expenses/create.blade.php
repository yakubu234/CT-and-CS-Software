@extends('layouts.admin')

@section('title', 'New Income or Expense')
@section('page_title', 'New Income or Expense')

@php
    $categoryJsMap = $categories->mapWithKeys(function ($category) {
        return [
            $category->id => [
                'id' => $category->id,
                'name' => $category->name,
                'related_to' => strtolower($category->related_to),
                'note' => $category->note,
            ],
        ];
    })->all();
@endphp

@push('styles')
    <style>
        .income-expense-shell {
            display: grid;
            gap: 1.5rem;
        }

        .income-expense-hero {
            border: 1px solid #dbe5f0;
            border-radius: 1rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, #f8fbff 0%, #eef6ff 55%, #f6fffb 100%);
        }

        .income-expense-hero-kicker {
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

        .income-expense-hero h3 {
            margin: 1rem 0 0.4rem;
            font-size: 1.55rem;
            font-weight: 700;
            color: #0f172a;
        }

        .income-expense-hero p {
            margin: 0;
            max-width: 44rem;
            color: #475569;
        }

        .income-expense-block {
            border: 1px solid #dbe5f0;
            border-radius: 1rem;
            background: #fff;
            overflow: hidden;
        }

        .income-expense-block-header {
            padding: 1.15rem 1.25rem;
            border-bottom: 1px solid #e5edf5;
            background: #f8fafc;
        }

        .income-expense-block-header h4 {
            margin: 0;
            color: #0f172a;
            font-weight: 700;
        }

        .income-expense-block-header p {
            margin: 0.25rem 0 0;
            color: #64748b;
        }

        .income-expense-block-body {
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

        .income-expense-type-chip {
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

        .income-expense-entry-card {
            border: 1px solid #dbe5f0;
            border-radius: 1rem;
            padding: 1.1rem;
            margin-bottom: 1rem;
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .income-expense-entry-card:last-child {
            margin-bottom: 0;
        }

        .income-expense-entry-card-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px dashed #d7e3ef;
        }

        .income-expense-entry-card-header h5 {
            margin: 0;
            color: #0f172a;
            font-weight: 700;
        }

        .income-expense-entry-subtitle {
            font-size: 0.88rem;
            color: #64748b;
            margin-top: 0.2rem;
        }

        .income-expense-summary-card {
            border: 1px solid #dbe5f0;
            border-radius: 1rem;
            padding: 1.15rem;
            background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 50%, #f5fffb 100%);
        }

        .income-expense-summary-stat {
            border-radius: 0.85rem;
            padding: 0.9rem 1rem;
            background: rgba(255, 255, 255, 0.78);
            border: 1px solid rgba(148, 163, 184, 0.18);
            height: 100%;
        }

        .income-expense-summary-label {
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            margin-bottom: 0.35rem;
        }

        .income-expense-summary-value {
            font-size: 1.35rem;
            font-weight: 700;
            color: #0f172a;
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

    <form action="{{ route('income-expenses.store') }}" method="POST" id="income-expense-create-form">
        @csrf

        <div class="income-expense-shell">
            <section class="income-expense-hero">
                <span class="income-expense-hero-kicker">
                    <i class="fas fa-scale-balanced"></i>
                    Society Ledger Entry
                </span>
                <h3>Create income and expense entries</h3>
                <p>
                    These entries will be recorded against the currently active branch ledger. Set the date once, then add
                    as many income or expense lines as needed for that day before saving.
                </p>
            </section>

            <div class="income-expense-block">
                <div class="income-expense-block-header">
                    <h4>Batch Details</h4>
                    <p>Set the transaction date once, then add multiple income or expense entries below.</p>
                </div>
                <div class="income-expense-block-body">
                    <div class="row">
                        <div class="col-lg-6 mb-3">
                            <label for="trans_date">
                                Entry Date
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

                        <div class="col-lg-4 mb-3">
                            <label>Active Branch</label>
                            <div class="income-expense-type-chip">{{ $branch->name }}</div>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="h5 mb-1">Income & Expense Entries</h4>
                            <small class="text-muted">Add multiple society ledger lines for the same date before saving.</small>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" id="add-entry-btn">
                            <i class="fas fa-plus mr-1"></i>
                            Add New Entry
                        </button>
                    </div>

                    <div id="entries-wrapper"></div>

                    <div class="income-expense-summary-card mt-4">
                        <div class="row">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="income-expense-summary-stat">
                                    <div class="income-expense-summary-label">Total Entries</div>
                                    <div class="income-expense-summary-value" id="summary-entry-count">0</div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="income-expense-summary-stat">
                                    <div class="income-expense-summary-label">Total Income</div>
                                    <div class="income-expense-summary-value text-info" id="summary-total-income">&#8358;0.00</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="income-expense-summary-stat">
                                    <div class="income-expense-summary-label">Total Expense</div>
                                    <div class="income-expense-summary-value text-danger" id="summary-total-expense">&#8358;0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="income-expense-block">
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('income-expenses.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i>
                        Save Entries
                    </button>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (() => {
            const categories = @json($categoryJsMap);
            const oldEntries = @json($initialEntries);
            const maxEntries = 20;
            const wrapper = document.getElementById('entries-wrapper');
            const addEntryBtn = document.getElementById('add-entry-btn');

            const formatMoney = (value) => {
                return new Intl.NumberFormat('en-NG', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }).format(Number(value || 0));
            };

            const relatedLabel = (value) => value === 'cr' ? 'Income' : value === 'dr' ? 'Expense' : 'N/A';

            const updateEntryPreview = (entryCard) => {
                const categorySelect = entryCard.querySelector('.entry-category');
                const amountInput = entryCard.querySelector('.entry-amount');
                const relatedPreview = entryCard.querySelector('.entry-related-preview');
                const category = categories[categorySelect.value] || null;
                const amount = Number(amountInput.value || 0);

                relatedPreview.textContent = category ? relatedLabel(category.related_to) : 'Will show after category selection';
            };

            const updateSummary = () => {
                const entryCards = wrapper.querySelectorAll('.income-expense-entry-card');
                let totalIncome = 0;
                let totalExpense = 0;

                entryCards.forEach((entryCard) => {
                    const categorySelect = entryCard.querySelector('.entry-category');
                    const amountInput = entryCard.querySelector('.entry-amount');
                    const category = categories[categorySelect.value] || null;
                    const amount = Number(amountInput.value || 0);

                    if (category?.related_to === 'cr') {
                        totalIncome += amount;
                    }

                    if (category?.related_to === 'dr') {
                        totalExpense += amount;
                    }
                });

                document.getElementById('summary-entry-count').textContent = entryCards.length;
                document.getElementById('summary-total-income').innerHTML = `&#8358;${formatMoney(totalIncome)}`;
                document.getElementById('summary-total-expense').innerHTML = `&#8358;${formatMoney(totalExpense)}`;
            };

            const bindEntryEvents = (entryCard) => {
                entryCard.querySelector('.entry-category').addEventListener('change', () => {
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
                    updateSummary();
                    toggleAddButton();
                });
            };

            const reindexEntries = () => {
                wrapper.querySelectorAll('.income-expense-entry-card').forEach((entryCard, index) => {
                    entryCard.dataset.index = index;
                    entryCard.querySelector('.entry-title').textContent = `Entry ${index + 1}`;
                    entryCard.querySelectorAll('[data-field]').forEach((field) => {
                        const fieldName = field.dataset.field;
                        field.name = `entries[${index}][${fieldName}]`;
                    });
                });
            };

            const toggleAddButton = () => {
                addEntryBtn.disabled = wrapper.querySelectorAll('.income-expense-entry-card').length >= maxEntries;
            };

            const categoryOptions = (selectedValue = '') => {
                const options = Object.values(categories).map((category) => {
                    const selected = String(selectedValue) === String(category.id) ? 'selected' : '';
                    return `<option value="${category.id}" ${selected}>${category.name}</option>`;
                });

                return ['<option value="">Choose category</option>', ...options].join('');
            };

            const renderEntry = (entry = {}) => {
                const index = wrapper.querySelectorAll('.income-expense-entry-card').length;
                const card = document.createElement('div');
                card.className = 'income-expense-entry-card';
                card.dataset.index = index;

                card.innerHTML = `
                    <div class="income-expense-entry-card-header">
                        <div>
                            <h5 class="h6 mb-0 entry-title">Entry ${index + 1}</h5>
                            <div class="income-expense-entry-subtitle">Choose a category, amount, and optional description for this line.</div>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm remove-entry-btn">
                            <i class="fas fa-trash-alt mr-1"></i>
                            Remove
                        </button>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 mb-3">
                            <label>Category <span class="field-label-meta required">Required</span></label>
                            <select class="form-control entry-category" data-field="transaction_category_id" name="entries[${index}][transaction_category_id]">
                                ${categoryOptions(entry.transaction_category_id || '')}
                            </select>
                        </div>
                        <div class="col-lg-3 mb-3">
                            <label>Flow Type</label>
                            <div class="income-expense-type-chip entry-related-preview">Will show after category selection</div>
                        </div>
                        <div class="col-lg-3 mb-3">
                            <label>Amount <span class="field-label-meta required">Required</span></label>
                            <input type="number" min="0.01" step="0.01" class="form-control entry-amount" data-field="amount" name="entries[${index}][amount]" value="${entry.amount || ''}" placeholder="Enter amount">
                        </div>
                        <div class="col-12">
                            <label>Description <span class="field-label-meta optional">Optional</span></label>
                            <input type="text" class="form-control" data-field="description" name="entries[${index}][description]" value="${entry.description || ''}" placeholder="Short description">
                        </div>
                    </div>
                `;

                wrapper.appendChild(card);
                bindEntryEvents(card);
                updateEntryPreview(card);
                updateSummary();
                toggleAddButton();
            };

            addEntryBtn.addEventListener('click', () => {
                if (wrapper.querySelectorAll('.income-expense-entry-card').length >= maxEntries) return;
                renderEntry({});
            });

            if (oldEntries.length) {
                oldEntries.forEach((entry) => renderEntry(entry));
            } else {
                renderEntry({});
            }

            toggleAddButton();
        })();
    </script>
@endpush
