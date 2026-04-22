@extends('layouts.admin')

@section('title', 'Edit Account Type')
@section('page_title', 'Edit Account Type')

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

    <form action="{{ route('account-types.update', $accountType) }}" method="POST">
        @csrf
        @method('PUT')

        @include('account-types._form', ['mode' => 'edit'])
    </form>
@endsection
