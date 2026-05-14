@extends('layouts.admin')

@section('title', 'Edit Blog Post')
@section('page_title', 'Edit Blog Post')

@section('content')
    <form action="{{ route('blog.update', $blogPost) }}" method="POST" enctype="multipart/form-data">
        @method('PUT')
        @include('blog._form')
    </form>
@endsection
