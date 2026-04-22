@extends('layouts.admin')

@section('title', 'Create Member Custom Field')
@section('page_title', 'Create Member Custom Field')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Add a new member custom field</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('members.custom-fields.store') }}" method="POST">
                @csrf
                @include('members.custom-fields._form', ['submitLabel' => 'Create Custom Field'])
            </form>
        </div>
    </div>
@endsection
