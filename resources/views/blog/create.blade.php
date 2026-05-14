@extends('layouts.admin')

@section('title', 'Create Blog Post')
@section('page_title', 'Create Blog Post')

@section('content')
    <form action="{{ route('blog.store') }}" method="POST" enctype="multipart/form-data">
        @include('blog._form')
    </form>
@endsection
