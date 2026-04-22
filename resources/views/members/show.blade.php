@extends('layouts.admin')

@section('title', 'View Member')
@section('page_title', 'View Member')

@section('content')
    <div class="row">
        <div class="col-lg-4">
            <div class="card card-outline card-primary">
                <div class="card-body box-profile">
                    <div class="text-center">
                        @if ($member->profile_picture)
                            <img class="profile-user-img img-fluid img-circle" src="{{ asset('storage/' . $member->profile_picture) }}" alt="{{ $member->name }}">
                        @else
                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width:96px;height:96px;">
                                <i class="fas fa-user fa-2x text-muted"></i>
                            </div>
                        @endif
                    </div>

                    <h3 class="profile-username text-center">{{ $member->name }}</h3>
                    <p class="text-muted text-center">{{ $member->detail?->member_no ?: $member->member_no ?: 'N/A' }}</p>

                    <ul class="list-group list-group-unbordered mb-3">
                        <li class="list-group-item"><b>Email</b> <span class="float-right">{{ $member->email }}</span></li>
                        <li class="list-group-item"><b>Mobile</b> <span class="float-right">{{ $member->detail?->mobile ?: 'N/A' }}</span></li>
                        <li class="list-group-item"><b>Occupation</b> <span class="float-right">{{ $member->detail?->occupation ?: 'N/A' }}</span></li>
                        <li class="list-group-item"><b>Designation</b> <span class="float-right">{{ $member->designation ?: 'Member' }}</span></li>
                    </ul>

                    <a href="{{ route('members.edit', $member) }}" class="btn btn-primary btn-block">Edit Member</a>
                    <a href="#documents" class="btn btn-outline-secondary btn-block">Add Document</a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Profile Details</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3"><strong>City:</strong> {{ $member->detail?->city ?: 'N/A' }}</div>
                        <div class="col-md-6 mb-3"><strong>State:</strong> {{ $member->detail?->state ?: 'N/A' }}</div>
                        <div class="col-md-6 mb-3"><strong>Date of Birth:</strong> {{ optional($member->detail?->date_of_birth)->format('d M Y') ?: 'N/A' }}</div>
                        <div class="col-md-6 mb-3"><strong>Gender:</strong> {{ $member->detail?->gender ?: 'N/A' }}</div>
                        <div class="col-md-6 mb-3"><strong>Address:</strong> {{ $member->detail?->address ?: 'N/A' }}</div>
                        <div class="col-md-4 mb-3"><strong>Account Number:</strong> {{ $member->detail?->account_number ?: 'N/A' }}</div>
                        <div class="col-md-4 mb-3"><strong>Account Name:</strong> {{ $member->detail?->account_name ?: 'N/A' }}</div>
                        <div class="col-md-4 mb-3"><strong>Bank Name:</strong> {{ $member->detail?->bank_name ?: 'N/A' }}</div>
                    </div>

                    @if ($member->signature)
                        <hr>
                        <div>
                            <strong class="d-block mb-2">Signature Preview</strong>
                            <a href="{{ asset('storage/' . $member->signature) }}" target="_blank" rel="noopener noreferrer">
                                <img
                                    src="{{ asset('storage/' . $member->signature) }}"
                                    alt="{{ $member->name }} signature"
                                    class="img-thumbnail"
                                    style="max-width: 260px;"
                                >
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Accounts</h3></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                            <tr>
                                <th>Product</th>
                                <th>Account Number</th>
                                <th>Balance</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($member->savingsAccounts as $account)
                                <tr>
                                    <td>{{ $account->product?->type ?: 'N/A' }}</td>
                                    <td>{{ $account->account_number }}</td>
                                    <td>{{ number_format((float) $account->balance, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted">No accounts found.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card" id="documents">
                <div class="card-header"><h3 class="card-title">Documents</h3></div>
                <div class="card-body">
                    <div class="border rounded p-3 mb-4 bg-light">
                        <h6 class="mb-3">Add New Document</h6>
                        <form action="{{ route('members.documents.store', $member) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-md-5">
                                    <label for="document_name">
                                        Document Name
                                        <span class="field-label-meta required">Required</span>
                                    </label>
                                    <input type="text" name="name" id="document_name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. National ID Card">
                                    @error('name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-5">
                                    <label for="document_file">
                                        Document File
                                        <span class="field-label-meta required">Required</span>
                                    </label>
                                    <input type="file" name="document" id="document_file" class="form-control-file @error('document') is-invalid @enderror">
                                    @error('document')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary btn-block">Upload</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    @if ($member->documents->isEmpty())
                        <p class="text-muted mb-0">No documents uploaded for this member yet.</p>
                    @else
                        <ul class="list-group">
                            @foreach ($member->documents as $document)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>{{ $document->name }}</span>
                                    <a href="{{ asset('storage/' . $document->document) }}" target="_blank" class="btn btn-sm btn-outline-primary">View Document</a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Custom Fields</h3></div>
                <div class="card-body">
                    @php($customFields = collect($member->detail?->custom_fields ?? []))
                    @if ($customFields->isEmpty())
                        <p class="text-muted mb-0">No custom field values saved for this member.</p>
                    @else
                        <div class="row">
                            @foreach ($customFields as $field)
                                <div class="col-md-6 mb-3">
                                    <strong>{{ $field['label'] ?? 'Custom Field' }}</strong>
                                    <div class="mt-1">
                                        @if (($field['type'] ?? null) === 'file' && ! empty($field['value']))
                                            <a href="{{ asset('storage/' . $field['value']) }}" target="_blank">View File</a>
                                        @else
                                            {{ $field['value'] ?? 'N/A' }}
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
