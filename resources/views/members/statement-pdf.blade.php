<!doctype html><html><head><meta charset="utf-8"><style>
body{font-family:DejaVu Sans,sans-serif;font-size:10px;color:#222}h1{font-size:17px;margin:0 0 4px}.muted{color:#666}.details{margin:18px 0}table{width:100%;border-collapse:collapse}th,td{border:1px solid #bbb;padding:5px 4px;vertical-align:top}th{background:#eee}.number{text-align:right;white-space:nowrap}.signature{margin-top:32px;page-break-inside:avoid;width:240px}.signature-box{height:62px;border-bottom:1px solid #333;text-align:center}.signature-box img{max-width:210px;max-height:54px;width:auto;height:auto}.signature-line{margin-top:8px}
</style></head><body>
<h1>{{ $member->branch?->name ?: 'Oreoluwapo CT&CU' }}</h1>
<div>{{ $member->branch?->address }} @if($member->branch?->contact_phone) | {{ $member->branch->contact_phone }} @endif @if($member->branch?->contact_email) | {{ $member->branch->contact_email }} @endif</div>
<h2>Official Member Statement</h2>
<div class="details"><strong>Member:</strong> {{ $member->name }} &nbsp; <strong>Member number:</strong> {{ $member->display_member_no ?: 'N/A' }}<br><strong>Period:</strong> {{ $statement['start'] ?: 'First transaction' }} to {{ $statement['end'] ?: 'Present' }} &nbsp; <strong>Issued:</strong> {{ now()->format('d M Y') }}<br><strong>Opening balance:</strong> ₦{{ number_format($statement['opening'], 2) }} &nbsp; <strong>Closing balance:</strong> ₦{{ number_format($statement['closing'], 2) }}</div>
<div class="muted" style="margin-bottom:10px">Loan interest payments are recorded as PAID and do not reduce the principal and member account closing balance.</div>
<table><thead><tr><th>Date</th><th>Account</th><th>Particulars</th><th>Type</th><th class="number">Amount (₦)</th><th class="number">Balance (₦)</th></tr></thead><tbody>
@forelse($statement['rows'] as $row)
    @php($transaction = $row['transaction'])
    <tr><td>{{ $transaction->trans_date?->format('d M Y') }}</td><td>{{ $transaction->account?->product?->type ?: ($transaction->type ?: 'Other') }}</td><td>{{ $transaction->description ?: ($transaction->note ?: 'Transaction') }}</td><td>{{ app(\App\Services\MemberStatementService::class)->memberDirection($transaction) }}</td><td class="number">{{ number_format((float) $transaction->amount, 2) }}</td><td class="number">{{ number_format($row['balance'], 2) }}</td></tr>
@empty <tr><td colspan="6">No transactions in this period.</td></tr> @endforelse
</tbody></table>
<div class="signature"><strong>Authorized Signature</strong><div class="signature-box">@if($signatureData)<img src="{{ $signatureData }}" alt="Authorized branch signature">@endif</div><div class="signature-line">Name/Position: ____________________</div><div class="signature-line">Date: ____________________</div>@unless($signatureData)<div class="muted">Authorized signature required</div>@endunless</div>
</body></html>
