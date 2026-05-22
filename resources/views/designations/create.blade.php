@extends('layouts.admin')

@section('title', 'Create Exco Role')
@section('page_title', 'Create Exco Role')

@section('content')
    <form method="POST" action="{{ route('exco-roles.store') }}">
        @include('designations._form')
    </form>
@endsection
