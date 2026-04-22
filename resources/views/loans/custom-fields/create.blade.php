@extends('layouts.admin')

@section('title', 'Create Loan Custom Field')
@section('page_title', 'Create Loan Custom Field')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Add a new loan custom field</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('loans.custom-fields.store') }}" method="POST">
                @csrf
                @include('loans.custom-fields._form', [
                    'submitLabel' => 'Create Custom Field',
                ])
            </form>
        </div>
    </div>
@endsection
