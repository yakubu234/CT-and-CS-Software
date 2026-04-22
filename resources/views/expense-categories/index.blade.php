@extends('layouts.admin')

@section('title', 'Income & Expense Categories')
@section('page_title', 'Income & Expense Categories')

@push('styles')
    <style>
        .expense-category-action-icons {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
        }

        .expense-category-action-icon {
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            border: 1px solid #dbe5f0;
            background: #fff;
            text-decoration: none;
            font-size: 0.78rem;
            transition: all 0.15s ease;
        }

        .expense-category-action-icon:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
        }

        .expense-category-action-icon.edit {
            color: #0891b2;
        }

        .expense-category-action-icon.delete {
            color: #dc2626;
            cursor: pointer;
        }
    </style>
@endpush

@section('content')
    <div class="card card-outline card-primary">
        @include('layouts.partials.table-toolbar', [
            'title' => 'Income & Expense Categories',
            'subtitle' => 'Manage categories used when posting society-level income and expense entries.',
            'action' => route('expense-categories.index'),
            'placeholder' => 'Search income or expense categories',
        ])

        <div class="card-body">
            <div class="mb-3">
                <a href="{{ route('expense-categories.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i>
                    Add New Category
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Related To</th>
                        <th>Status</th>
                        <th style="width: 70px;" class="text-center">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td>
                                <div class="font-weight-semibold">{{ $category->name }}</div>
                                <small class="text-muted">{{ $category->note ?: 'No note added.' }}</small>
                            </td>
                            <td>{{ $relatedToLabels[$category->related_to] ?? strtoupper($category->related_to) }}</td>
                            <td>
                                <span class="badge badge-{{ (int) $category->status === 1 ? 'success' : 'secondary' }}">
                                    {{ (int) $category->status === 1 ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <div class="expense-category-action-icons">
                                    <a href="{{ route('expense-categories.edit', $category) }}" class="expense-category-action-icon edit" title="Edit category" aria-label="Edit category">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <form action="{{ route('expense-categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this category?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="expense-category-action-icon delete" title="Delete category" aria-label="Delete category">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No income or expense categories found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $categories->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
