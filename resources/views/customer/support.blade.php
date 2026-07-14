@extends('layouts.customer')

@section('title', 'Support')
@section('page_title', 'Support / Requests')
@section('page_subtitle', 'Submit inquiries and track responses from the cooperative.')

@section('content')
    <div class="row">
        <div class="col-lg-5 mb-3">
            <div class="card customer-card">
                <div class="card-header">
                    <h3 class="card-title">New Request</h3>
                </div>
                <form method="POST" action="{{ route('customer.support.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" name="subject" id="subject" class="form-control" value="{{ old('subject') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select name="category" id="category" class="form-control" required>
                                @foreach (['general' => 'General', 'account' => 'Account', 'loan' => 'Loan', 'repayment' => 'Repayment', 'profile' => 'Profile'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('category', 'general') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <label for="message">Message</label>
                            <textarea name="message" id="message" class="form-control" rows="5" required>{{ old('message') }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane mr-1"></i> Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-7 mb-3">
            <div class="card customer-card">
                <div class="card-header">
                    <h3 class="card-title">Request History</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($requests as $supportRequest)
                            <tr>
                                <td>{{ optional($supportRequest->created_at)->format('d M Y') }}</td>
                                <td>
                                    <div class="font-weight-bold">{{ $supportRequest->subject }}</div>
                                    <div class="small text-muted">{{ \Illuminate\Support\Str::limit($supportRequest->message, 80) }}</div>
                                    @if ($supportRequest->admin_response)
                                        <div class="small mt-2"><strong>Response:</strong> {{ $supportRequest->admin_response }}</div>
                                    @endif
                                </td>
                                <td>{{ ucfirst($supportRequest->category) }}</td>
                                <td>
                                    <span class="badge badge-light border">{{ str_replace('_', ' ', ucfirst($supportRequest->status)) }}</span>
                                    @if ($supportRequest->resolved_at)
                                        <div class="small text-muted mt-1">{{ $supportRequest->resolved_at->format('d M Y') }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No support requests submitted yet.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">{{ $requests->links() }}</div>
            </div>
        </div>
    </div>
@endsection
