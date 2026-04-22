@extends('layouts.admin')

@section('title', 'Create Transaction Category')
@section('page_title', 'Create Transaction Category')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title mb-0">New Transaction Category</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('transaction-categories.store') }}" method="POST">
                @csrf
                @include('transaction-categories._form', [
                    'submitLabel' => 'Create Category',
                ])
            </form>
        </div>
    </div>
@endsection
