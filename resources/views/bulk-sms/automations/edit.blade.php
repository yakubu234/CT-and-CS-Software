@extends('layouts.admin')

@section('title', 'Edit SMS Automation')
@section('page_title', 'Edit SMS Automation')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-body">
            <form method="POST" action="{{ route('bulk-sms.automations.update', $rule) }}">
                @csrf
                @method('PUT')
                @include('bulk-sms.automations._form', ['submitLabel' => 'Update Automation'])
            </form>
        </div>
    </div>
@endsection
