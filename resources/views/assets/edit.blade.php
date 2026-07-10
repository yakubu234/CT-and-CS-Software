@extends('layouts.admin')

@section('title', 'Edit Asset')
@section('page_title', 'Edit Asset')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title mb-0">Edit Fixed Asset</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('assets.update', $asset) }}">
                @csrf
                @method('PUT')
                @include('assets._form', ['submitLabel' => 'Update Asset'])
            </form>
        </div>
    </div>
@endsection
