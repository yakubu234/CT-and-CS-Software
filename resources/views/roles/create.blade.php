@extends('layouts.admin')

@section('title', 'Create Admin Role')
@section('page_title', 'Create Admin Role')

@section('content')
    <form method="POST" action="{{ route('user-roles.store') }}">
        @include('roles._form')
    </form>
@endsection
