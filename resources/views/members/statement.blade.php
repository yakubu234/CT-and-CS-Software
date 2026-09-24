@extends('layouts.admin')

@section('title', 'Member Statement Report')
@section('page_title', 'Member Statement Report')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header"><strong>{{ $member->name }}</strong> — {{ $member->display_member_no ?: 'N/A' }} <span class="text-muted">| {{ $member->branch?->name }}</span></div>
        <div class="card-body">
            <p class="text-muted">Official office statement. All amounts come from the same member transaction ledger used in the member portal. A branch signature appears on the PDF only when one is configured.</p>
            <form method="GET" action="{{ route('members.statement', $member->id) }}" class="form-row align-items-end">
                <div class="form-group col-md-2"><label for="period">Period</label><select name="period" id="period" class="form-control">
                    @foreach (['full' => 'Full history', 'annual' => 'Annual', 'monthly' => 'Monthly', 'custom' => 'Custom range'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['period'] ?? 'full') === $value)>{{ $label }}</option>
                    @endforeach
                </select></div>
                <div class="form-group col-md-2"><label for="year">Year</label><input type="number" name="year" id="year" class="form-control" min="1900" max="2100" value="{{ $filters['year'] ?? now()->year }}"></div>
                <div class="form-group col-md-2"><label for="month">Month</label><input type="month" name="month" id="month" class="form-control" value="{{ $filters['month'] ?? now()->format('Y-m') }}"></div>
                <div class="form-group col-md-2"><label for="start_date">From</label><input type="date" name="start_date" id="start_date" class="form-control" value="{{ $filters['start_date'] ?? '' }}"></div>
                <div class="form-group col-md-2"><label for="end_date">To</label><input type="date" name="end_date" id="end_date" class="form-control" value="{{ $filters['end_date'] ?? '' }}"></div>
                <div class="form-group col-md-2"><button class="btn btn-primary btn-block">Generate</button></div>
            </form>
            @error('email')<div class="alert alert-danger">{{ $message }}</div>@enderror
            @if ($errors->any())<div class="alert alert-danger">Check the statement period and try again.</div>@endif
            <div class="mb-3 d-flex flex-wrap" style="gap:8px">
                <a href="{{ route('members.statement.pdf', ['memberId' => $member->id] + $filters) }}" class="btn btn-outline-primary">Download official PDF</a>
                <a href="{{ route('members.statement.pdf', ['memberId' => $member->id, 'inline' => 1] + $filters) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">Print official PDF</a>
                <form method="POST" action="{{ route('members.statement.email', ['memberId' => $member->id] + $filters) }}" onsubmit="return confirm('Email this official statement to {{ $member->email }}?')">@csrf<button class="btn btn-outline-success" @disabled(! filter_var($member->email, FILTER_VALIDATE_EMAIL))>Email registered address</button></form>
            </div>
            <p><strong>Opening balance:</strong> ₦{{ number_format($statement['opening'], 2) }} &nbsp; <strong>Closing balance:</strong> ₦{{ number_format($statement['closing'], 2) }}</p>
            <p class="text-muted small">Loan interest payments remain in the transaction history as PAID. They do not reduce the closing balance because the corresponding interest charge is tracked separately from this principal and member account balance.</p>
            <div class="table-responsive"><table class="table table-bordered table-sm">
                <thead><tr><th>Date</th><th>Account</th><th>Particulars</th><th>Type</th><th class="text-right">Amount</th><th class="text-right">Running balance</th></tr></thead>
                <tbody>@forelse ($statement['rows'] as $row)
                    @php($transaction = $row['transaction'])
                    <tr><td>{{ $transaction->trans_date?->format('d M Y') }}</td><td>{{ $transaction->account?->product?->type ?: ($transaction->type ?: 'Other') }}<br><small>{{ $transaction->account?->account_number }}</small></td><td>{{ $transaction->description ?: ($transaction->note ?: 'Transaction') }}</td><td>{{ app(\App\Services\MemberStatementService::class)->memberDirection($transaction) }}</td><td class="text-right">₦{{ number_format((float) $transaction->amount, 2) }}</td><td class="text-right">₦{{ number_format($row['balance'], 2) }}</td></tr>
                @empty <tr><td colspan="6" class="text-center">No transactions in this period.</td></tr> @endforelse</tbody>
            </table></div>
        </div>
    </div>
@endsection
