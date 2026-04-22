@extends('layouts.admin')

@section('title', 'Edit Branch')
@section('page_title', 'Edit Branch')

@php
    $storageUrl = fn (?string $path) => $path ? \Illuminate\Support\Facades\Storage::url($path) : null;
    $existingExcos = $branch->excos->map(function ($exco) use ($designations, $storageUrl) {
        return [
            'user_id' => $exco->id,
            'first_name' => $exco->getRawOriginal('name'),
            'last_name' => $exco->last_name,
            'phone' => '',
            'designation_id' => $designations->firstWhere('name', $exco->designation)?->id,
            'image_url' => $storageUrl($exco->profile_picture),
        ];
    })->values()->all();

    $excos = old('excos', $existingExcos ?: [
        ['user_id' => '', 'first_name' => '', 'last_name' => '', 'phone' => '', 'designation_id' => '', 'image_url' => null],
    ]);
@endphp

@section('content')
    <div class="row">
        <div class="col-lg-9">
            <form action="{{ route('branches.update', $branch) }}" method="POST" enctype="multipart/form-data" id="branch-form">
                @csrf
                @method('PUT')

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
                    'branchFormData' => [
                        'branch_name' => $branch->name,
                        'branch_prefix' => $branch->prefix,
                        'loan_prefix' => $branch->id_prefix,
                        'contact_email' => $branch->contact_email,
                        'contact_phone' => $branch->contact_phone,
                        'registration_number' => $branch->registration_number,
                        'year_of_registration' => $branch->year_of_registration,
                        'branch_meeting_days' => $branch->branch_meeting_days,
                        'address' => $branch->address,
                        'photo_url' => $storageUrl($branch->photo),
                        'signature_url' => $storageUrl($branch->signature),
                    ],
                    'excos' => $excos,
                    'designations' => $designations,
                    'submitLabel' => 'Update branch',
                    'submitIcon' => 'fas fa-save',
                ])
            </form>
        </div>

        <div class="col-lg-3">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">Exco update rules</h3>
                </div>
                <div class="card-body">
                    <ul class="pl-3 mb-0">
                        <li>Removed excos stay in the system as users.</li>
                        <li>Their former designation is preserved.</li>
                        <li>Their exco start and end dates stay on record.</li>
                        <li>Soft deleting the branch is handled separately from editing.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
