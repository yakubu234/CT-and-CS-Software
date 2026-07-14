@extends('layouts.customer')

@section('title', 'Profile')
@section('page_title', 'Profile')
@section('page_subtitle', 'Your membership details, branch, contact information, and documents.')

@section('content')
    <div class="row">
        <div class="col-lg-6 mb-3">
            <div class="card customer-card h-100">
                <div class="card-header">
                    <h3 class="card-title">Personal Details</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Name</dt>
                        <dd class="col-sm-8">{{ $customer->name }}</dd>
                        <dt class="col-sm-4">Member No</dt>
                        <dd class="col-sm-8">{{ $customer->display_member_no ?: 'N/A' }}</dd>
                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">{{ $customer->email ?: 'N/A' }}</dd>
                        <dt class="col-sm-4">Phone</dt>
                        <dd class="col-sm-8">{{ $customer->detail?->mobile ?: 'N/A' }}</dd>
                        <dt class="col-sm-4">Branch</dt>
                        <dd class="col-sm-8">{{ $customer->branch?->name ?: 'N/A' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-3">
            <div class="card customer-card h-100">
                <div class="card-header">
                    <h3 class="card-title">Documents</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <tbody>
                        @forelse ($customer->documents as $document)
                            <tr>
                                <td>{{ $document->name ?? 'Document' }}</td>
                                <td class="text-right">
                                    @if ($document->document ?? false)
                                        <a href="{{ asset('storage/' . $document->document) }}" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                                    @else
                                        <span class="text-muted">Unavailable</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center text-muted py-4">No documents found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
