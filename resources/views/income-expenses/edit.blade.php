@extends('layouts.admin')

@section('title', 'Edit Income or Expense')
@section('page_title', 'Edit Income or Expense')

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

    $selectedCategoryId = old('transaction_category_id', $category?->id ?? data_get($incomeExpense->transaction_details, 'category_id') ?? $incomeExpense->detail_id);
@endphp

@push('styles')
    <style>
        .income-expense-edit-summary {
            border: 1px solid #dbe5f0;
            border-radius: 0.85rem;
            padding: 1rem;
            background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 100%);
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
            This entry belongs to the active branch ledger. You may correct the category, date, amount, or description
            without it showing under member transactions.
        </p>
    </div>

    <div class="income-expense-edit-summary mb-3">
        <div class="row">
            <div class="col-md-4 mb-2 mb-md-0">
                <div class="text-muted small">Active Branch</div>
                <div class="font-weight-bold">{{ $branch->name }}</div>
            </div>
            <div class="col-md-4 mb-2 mb-md-0">
                <div class="text-muted small">Current Category</div>
                <div class="font-weight-bold">{{ $category?->name ?: ($incomeExpense->type ?: 'N/A') }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Current Type</div>
                <div class="font-weight-bold">{{ $relatedToLabels[strtolower($incomeExpense->dr_cr)] ?? strtoupper($incomeExpense->dr_cr) }}</div>
            </div>
        </div>
    </div>

    <form action="{{ route('income-expenses.update', $incomeExpense) }}" method="POST" id="income-expense-edit-form">
        @csrf
        @method('PUT')

        <div class="card card-outline card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="transaction_category_id">
                            Category
                            <span class="field-label-meta required">Required</span>
                        </label>
                        <select name="transaction_category_id" id="transaction_category_id" class="form-control @error('transaction_category_id') is-invalid @enderror">
                            @foreach ($categories as $categoryOption)
                                <option value="{{ $categoryOption->id }}" @selected((string) $selectedCategoryId === (string) $categoryOption->id)>
                                    {{ $categoryOption->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('transaction_category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

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
                            value="{{ old('trans_date', optional($incomeExpense->trans_date)->format('Y-m-d')) }}"
                        >
                        @error('trans_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-lg-4 mb-3">
                        <label>Flow Type</label>
                        <div class="income-expense-type-chip" id="related-preview">Will show after category selection</div>
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
                            value="{{ old('amount', number_format((float) $incomeExpense->amount, 2, '.', '')) }}"
                        >
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-lg-4 mb-3">
                        <label>Current Branch</label>
                        <div class="income-expense-type-chip">{{ $branch->name }}</div>
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
                            value="{{ old('description', $incomeExpense->description) }}"
                        >
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('income-expenses.show', $incomeExpense) }}" class="btn btn-light">Cancel</a>
                <div class="d-flex">
                    <button type="submit" class="btn btn-primary mr-2">
                        <i class="fas fa-save mr-1"></i>
                        Update Entry
                    </button>
                    <button
                        type="submit"
                        class="btn btn-outline-danger"
                        form="income-expense-delete-form"
                        onclick="return confirm('Delete this income or expense entry?')"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </form>

    <form action="{{ route('income-expenses.destroy', $incomeExpense) }}" method="POST" id="income-expense-delete-form" class="d-none">
        @csrf
        @method('DELETE')
    </form>
@endsection

@push('scripts')
    <script>
        (() => {
            const categories = @json($categoryJsMap);
            const categorySelect = document.getElementById('transaction_category_id');
            const relatedPreview = document.getElementById('related-preview');

            const relatedLabel = (value) => value === 'cr' ? 'Income' : value === 'dr' ? 'Expense' : 'N/A';

            const refreshPreview = () => {
                const category = categories[categorySelect.value] || null;
                relatedPreview.textContent = category ? relatedLabel(category.related_to) : 'Will show after category selection';
            };

            categorySelect.addEventListener('change', refreshPreview);
            refreshPreview();
        })();
    </script>
@endpush
