@extends('layouts.admin')

@section('title', 'Create Email Template')
@section('page_title', 'Create Email Template')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-body">
            <form method="POST" action="{{ route('email.templates.store') }}">
                @csrf
                @include('email.templates._form', ['submitLabel' => 'Create Template'])
            </form>
        </div>
    </div>
@endsection
