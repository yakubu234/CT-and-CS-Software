@extends('layouts.admin')

@section('title', 'Create Income or Expense Category')
@section('page_title', 'Create Income or Expense Category')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title mb-0">New Income & Expense Category</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('expense-categories.store') }}" method="POST">
                @csrf
                @include('expense-categories._form', [
                    'submitLabel' => 'Create Category',
                ])
            </form>
        </div>
    </div>
@endsection
