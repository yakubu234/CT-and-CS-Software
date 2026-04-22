@extends('layouts.admin')

@section('title', 'Edit Member')
@section('page_title', 'Edit Member')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Update member details</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('members.update', $member) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('members._form', ['submitLabel' => 'Update Member'])
            </form>
        </div>
    </div>
@endsection
