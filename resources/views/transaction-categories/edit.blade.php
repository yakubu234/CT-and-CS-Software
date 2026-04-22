@extends('layouts.admin')

@section('title', 'Edit Transaction Category')
@section('page_title', 'Edit Transaction Category')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title mb-0">Edit Transaction Category</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('transaction-categories.update', $transactionCategory) }}" method="POST">
                @csrf
                @method('PUT')
                @include('transaction-categories._form', [
                    'transactionCategory' => $transactionCategory,
                    'submitLabel' => 'Update Category',
                ])
            </form>
        </div>
    </div>
@endsection
