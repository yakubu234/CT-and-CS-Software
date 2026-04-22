@extends('layouts.admin')

@section('title', 'Loan Request Details')
@section('page_title', 'Loan Request Details')

@php
    $loan = $loanDetail->loan;
    $borrower = $loanDetail->borrower;
    $outstanding = (float) ($loan->balanace ?? 0);
    $projectedOutstanding = $loanDetail->decision_status === 'approved'
        ? $outstanding
        : $outstanding + (float) $loanDetail->applied_amount;
@endphp

@section('content')
    <div class="card card-outline card-primary mb-4">
        <div class="card-header">
            <h3 class="card-title">Loan Request Review</h3>
            <div class="card-tools">
                <a href="{{ route('loans.pending') }}" class="btn btn-sm btn-outline-secondary">Back</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="small text-muted">Borrower</div>
                    <div class="font-weight-bold">{{ $borrower?->name ?: 'N/A' }}</div>
                    <div class="text-muted small">{{ $borrower?->detail?->member_no ?: $borrower?->member_no ?: 'N/A' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="small text-muted">Loan ID</div>
                    <div class="font-weight-bold">{{ $loan?->loan_id ?: 'N/A' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="small text-muted">Current Outstanding</div>
                    <div class="font-weight-bold">&#8358;{{ number_format($outstanding, 2) }}</div>
                </div>
                <div class="col-md-3">
                    <div class="small text-muted">Outstanding If Approved</div>
                    <div class="font-weight-bold text-info">&#8358;{{ number_format($projectedOutstanding, 2) }}</div>
                </div>
            </div>

            <div class="alert alert-light border mb-4">
                <strong>Society purse balance:</strong>
                &#8358;{{ number_format((float) $branchLedgerBalance, 2) }}
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="small text-muted">Release Date</div>
                    <div class="font-weight-bold">{{ optional($loanDetail->release_date)->format('d M Y') ?: 'N/A' }}</div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="small text-muted">Due Date</div>
                    <div class="font-weight-bold">{{ optional($loanDetail->due_date)->format('d M Y') ?: 'N/A' }}</div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="small text-muted">Amount</div>
                    <div class="font-weight-bold">&#8358;{{ number_format((float) $loanDetail->applied_amount, 2) }}</div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="small text-muted">Interest Week Interval</div>
                    <div class="font-weight-bold text-capitalize">{{ str_replace('-', ' ', $loanDetail->interest_week_interval ?: 'N/A') }}</div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="small text-muted">Late Payment Penalties</div>
                    <div class="font-weight-bold">&#8358;{{ number_format((float) ($loanDetail->late_payment_penalties ?? 0), 2) }}</div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="small text-muted">Decision Status</div>
                    <div class="font-weight-bold text-capitalize">{{ $loanDetail->decision_status }}</div>
                </div>
                @if ($loanDetail->attachment)
                    <div class="col-md-12 mb-3">
                        <div class="small text-muted">Attachment</div>
                        <a href="{{ asset('storage/' . $loanDetail->attachment) }}" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                            View Attachment
                        </a>
                    </div>
                @endif
            </div>

            @if (! empty($loanDetail->custom_fields))
                <hr>
                <h5 class="mb-3">Loan Custom Field Values</h5>
                <div class="row">
                    @foreach ($loanDetail->custom_fields as $field)
                        <div class="col-md-6 mb-3">
                            <div class="small text-muted">{{ $field['label'] ?? 'Field' }}</div>
                            <div class="font-weight-bold">
                                @if (($field['type'] ?? '') === 'file')
                                    <a href="{{ asset('storage/' . ($field['value'] ?? '')) }}" target="_blank">View File</a>
                                @else
                                    {{ $field['value'] ?? 'N/A' }}
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <hr>
            <div class="d-flex flex-wrap gap-2">
                @if ($loanDetail->canBeEdited())
                    <a href="{{ route('loans.requests.edit', $loanDetail) }}" class="btn btn-outline-primary mr-2 mb-2">
                        Edit Loan
                    </a>
                @endif

                @if ($loanDetail->canBeDeleted())
                    <form action="{{ route('loans.requests.destroy', $loanDetail) }}" method="POST" class="mr-2 mb-2">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Delete this loan request? This action will remove the request and reverse any disbursement tied to it.')">
                            Delete Loan
                        </button>
                    </form>
                @endif

                @if ($loanDetail->decision_status === 'pending')
                    <form action="{{ route('loans.requests.approve', $loanDetail) }}" method="POST" class="mr-2 mb-2">
                        @csrf
                        <button type="submit" class="btn btn-success" onclick="return confirm('Approve this loan request?')">
                            Approve Loan
                        </button>
                    </form>

                    <form action="{{ route('loans.requests.decline', $loanDetail) }}" method="POST" class="d-flex flex-wrap align-items-end mb-2">
                        @csrf
                        <div class="form-group mb-0 mr-2">
                            <label for="decline_reason" class="small text-muted">Decline Reason <span class="optional-label">(Optional)</span></label>
                            <input type="text" name="decline_reason" id="decline_reason" class="form-control" style="min-width: 280px;" placeholder="Why is this loan being declined?">
                        </div>
                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Decline this loan request?')">
                            Decline Loan
                        </button>
                    </form>
                @else
                    <div class="alert alert-light border mb-0">
                        This request has already been <strong>{{ $loanDetail->decision_status }}</strong>.
                        @if ($loanDetail->decision_status === 'approved' && $loanDetail->hasRepayments())
                            It can still be edited, but it cannot be deleted because repayment has already been recorded.
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
