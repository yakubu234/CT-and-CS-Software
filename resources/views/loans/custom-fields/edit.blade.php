@extends('layouts.admin')

@section('title', 'Edit Loan Custom Field')
@section('page_title', 'Edit Loan Custom Field')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-body">
            <form action="{{ route('loans.custom-fields.update', $customField) }}" method="POST">
                @csrf
                @method('PUT')

                @include('loans.custom-fields._form', [
                    'customField' => $customField,
                    'submitLabel' => 'Update Custom Field',
                ])
            </form>
        </div>
    </div>
@endsection
