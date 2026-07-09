@extends('layouts.admin')

@section('title', 'Edit Email Template')
@section('page_title', 'Edit Email Template')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-body">
            <form method="POST" action="{{ route('email.templates.update', $emailTemplate) }}">
                @csrf
                @method('PUT')
                @include('email.templates._form', ['submitLabel' => 'Update Template'])
            </form>
        </div>
    </div>
@endsection
