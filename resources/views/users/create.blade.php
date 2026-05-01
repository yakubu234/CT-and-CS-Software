@extends('layouts.admin')

@section('title', 'Create User')
@section('page_title', 'Create User')

@section('content')
    <form method="POST" action="{{ route('users.store') }}">
        @include('users._form')
    </form>
@endsection
