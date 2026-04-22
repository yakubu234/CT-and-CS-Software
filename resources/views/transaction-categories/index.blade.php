@extends('layouts.admin')

@section('title', 'Transaction Categories')
@section('page_title', 'Transaction Categories')

@push('styles')
    <style>
        .category-action-icons {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
        }

        .category-action-icon {
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

        .category-action-icon:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
        }

        .category-action-icon.edit {
            color: #0891b2;
        }

        .category-action-icon.delete {
            color: #dc2626;
            cursor: pointer;
        }

        .category-table th:last-child,
        .category-table td:last-child {
            width: 70px;
            text-align: center;
        }
    </style>
@endpush

@section('content')
    <div class="card card-outline card-primary">
        @include('layouts.partials.table-toolbar', [
            'title' => 'Transaction Categories',
            'subtitle' => 'Manage categories used across member transactions and income or expense records.',
            'action' => route('transaction-categories.index'),
            'placeholder' => 'Search transaction categories',
        ])

        <div class="card-body">
            <div class="mb-3">
                <a href="{{ route('transaction-categories.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i>
                    Add New Category
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover category-table">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Related To</th>
                        <th>Status</th>
                        <th>Action</th>
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
                                <div class="category-action-icons">
                                    <a
                                        href="{{ route('transaction-categories.edit', $category) }}"
                                        class="category-action-icon edit"
                                        title="Edit category"
                                        aria-label="Edit category"
                                    >
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <form
                                        action="{{ route('transaction-categories.destroy', $category) }}"
                                        method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Delete this transaction category?')"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="category-action-icon delete"
                                            title="Delete category"
                                            aria-label="Delete category"
                                        >
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No transaction categories found.</td>
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
