@extends('layouts.admin')

@section('title', 'Edit SMS Template')
@section('page_title', 'Edit SMS Template')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-body">
            <form method="POST" action="{{ route('bulk-sms.templates.update', $smsTemplate) }}">
                @csrf
                @method('PUT')
                @include('bulk-sms.templates._form', ['submitLabel' => 'Update Template'])
            </form>
        </div>
    </div>
@endsection
