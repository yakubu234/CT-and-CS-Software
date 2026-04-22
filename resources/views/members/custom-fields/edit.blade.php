@extends('layouts.admin')

@section('title', 'Edit Member Custom Field')
@section('page_title', 'Edit Member Custom Field')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Update member custom field</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('members.custom-fields.update', $customField) }}" method="POST">
                @csrf
                @method('PUT')
                @include('members.custom-fields._form', ['submitLabel' => 'Update Custom Field'])
            </form>
        </div>
    </div>
@endsection
