@php
    $financialRows = $memberFinancialSummary['rows'] ?? [];
    $financialTotal = (float) ($memberFinancialSummary['total'] ?? 0);
@endphp

<div class="alert alert-light border mb-4">
    <div class="font-weight-bold mb-2">Member Financial Summary</div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
            <thead>
            <tr>
                <th>Account</th>
                <th class="text-right">Balance</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($financialRows as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td class="text-right">&#8358;{{ number_format((float) $row['balance'], 2) }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
            <tr>
                <th>Total</th>
                <th class="text-right">&#8358;{{ number_format($financialTotal, 2) }}</th>
            </tr>
            </tfoot>
        </table>
    </div>
</div>
