@extends('layouts.admin')

@section('title', 'Edit Asset Category')
@section('page_title', 'Edit Asset Category')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title mb-0">Edit Asset Category</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('asset-categories.update', $category) }}">
                @csrf
                @method('PUT')
                @include('assets.categories._form', ['submitLabel' => 'Update Category'])
            </form>
        </div>
    </div>
@endsection
