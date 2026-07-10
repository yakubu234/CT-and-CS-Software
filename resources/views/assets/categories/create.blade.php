@extends('layouts.admin')

@section('title', 'Create Asset Category')
@section('page_title', 'Create Asset Category')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title mb-0">New Asset Category</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('asset-categories.store') }}">
                @csrf
                @include('assets.categories._form', ['submitLabel' => 'Create Category'])
            </form>
        </div>
    </div>
@endsection
