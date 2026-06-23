@extends('layouts.admin')

@section('title', 'Create Branch')
@section('page_title', 'Create Branch')

@php
    $excos = old('excos', $branchMembers->isNotEmpty() ? [
        ['member_id' => '', 'designation_id' => ''],
    ] : []);
@endphp

@section('content')
    <div class="row">
        <div class="col-lg-9">
            <form action="{{ route('branches.store') }}" method="POST" enctype="multipart/form-data" id="branch-form">
                @csrf

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <h5 class="mb-2"><i class="icon fas fa-ban"></i> Please fix the highlighted fields.</h5>
                        <ul class="mb-0 pl-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @include('branches.partials.form-fields', [
                    'branchFormData' => [],
                    'excos' => $excos,
                    'designations' => $designations,
                    'branchMembers' => $branchMembers,
                    'submitLabel' => 'Save branch',
                    'submitIcon' => 'fas fa-save',
                ])
            </form>
        </div>

        <div class="col-lg-3">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">What gets created</h3>
                </div>
                <div class="card-body">
                    <ul class="pl-3 mb-0">
                        <li>A branch record</li>
                        <li>A branch-account user with `branch_account = 1`</li>
                        <li>One default branch savings account</li>
                        <li>Excos can be assigned after members are registered under the branch</li>
                        <li>Selected existing members are promoted into exco roles</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
