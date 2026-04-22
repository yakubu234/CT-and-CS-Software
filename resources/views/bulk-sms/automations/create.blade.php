@extends('layouts.admin')

@section('title', 'Create SMS Automation')
@section('page_title', 'Create SMS Automation')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-body">
            <form method="POST" action="{{ route('bulk-sms.automations.store') }}">
                @csrf
                @include('bulk-sms.automations._form', ['submitLabel' => 'Create Automation'])
            </form>
        </div>
    </div>
@endsection
