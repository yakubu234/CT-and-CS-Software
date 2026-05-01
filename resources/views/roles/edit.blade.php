@extends('layouts.admin')

@section('title', 'Edit Role')
@section('page_title', 'Edit Role')

@section('content')
    <form method="POST" action="{{ route('user-roles.update', $role) }}">
        @method('PUT')
        @include('roles._form')
    </form>
@endsection
