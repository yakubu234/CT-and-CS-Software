@extends('layouts.admin')

@section('title', 'Create Member')
@section('page_title', 'Create Member')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Create a new member</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('members.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @include('members._form', ['submitLabel' => 'Create Member'])
            </form>
        </div>
    </div>
@endsection
