@extends('layouts.admin')

@section('title', 'Edit Income or Expense Category')
@section('page_title', 'Edit Income or Expense Category')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title mb-0">Edit Income & Expense Category</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('expense-categories.update', $expenseCategory) }}" method="POST">
                @csrf
                @method('PUT')
                @include('expense-categories._form', [
                    'expenseCategory' => $expenseCategory,
                    'submitLabel' => 'Update Category',
                ])
            </form>
        </div>
    </div>
@endsection
