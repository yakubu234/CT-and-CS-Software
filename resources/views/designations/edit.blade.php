@extends('layouts.admin')

@section('title', 'Edit Exco Role')
@section('page_title', 'Edit Exco Role')

@section('content')
    <form method="POST" action="{{ route('exco-roles.update', $designation) }}">
        @method('PUT')
        @include('designations._form')
    </form>
@endsection
