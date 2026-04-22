@extends('layouts.admin')

@section('title', 'Create SMS Template')
@section('page_title', 'Create SMS Template')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-body">
            <form method="POST" action="{{ route('bulk-sms.templates.store') }}">
                @csrf
                @include('bulk-sms.templates._form', ['submitLabel' => 'Create Template'])
            </form>
        </div>
    </div>
@endsection
