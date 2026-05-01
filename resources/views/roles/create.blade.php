@extends('layouts.admin')

@section('title', 'Create Role')
@section('page_title', 'Create Role')

@section('content')
    <form method="POST" action="{{ route('user-roles.store') }}">
        @include('roles._form')
    </form>
@endsection
