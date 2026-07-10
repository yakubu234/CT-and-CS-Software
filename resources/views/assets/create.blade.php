@extends('layouts.admin')

@section('title', 'Record Asset')
@section('page_title', 'Record Asset')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title mb-0">New Fixed Asset</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('assets.store') }}">
                @csrf
                @include('assets._form', ['submitLabel' => 'Record Asset'])
            </form>
        </div>
    </div>
@endsection
