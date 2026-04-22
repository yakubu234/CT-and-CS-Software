@extends('layouts.admin')

@section('title', 'View Account Type')
@section('page_title', 'View Account Type')

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

        .account-type-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.1rem;
            border: 1px solid #dbe5f0;
            border-radius: 0.85rem;
            background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 100%);
        }
    </style>
@endpush

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="account-type-summary mb-3">
        <div>
            <h2 class="h4 mb-1">{{ $accountType->name }}</h2>
            <div class="text-muted">Prefix: {{ $accountType->account_number_prefix ?: 'N/A' }} | Next account: {{ $accountType->next_account_number }}</div>
        </div>
        <div class="text-md-right">
            <span class="badge {{ (int) $accountType->status === 1 ? 'badge-success' : 'badge-secondary' }} mb-2">
                {{ (int) $accountType->status === 1 ? 'Active' : 'Inactive' }}
            </span>
            <div>
                <a href="{{ route('account-types.edit', $accountType) }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-edit mr-1"></i>
                    Edit Account Type
                </a>
                <a href="{{ route('account-types.index') }}" class="btn btn-light btn-sm">Back</a>
            </div>
        </div>
    </div>

    @include('account-types._form', ['mode' => 'show'])
@endsection
