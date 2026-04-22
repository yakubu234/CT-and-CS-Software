@extends('layouts.admin')

@section('title', 'Edit Loan Repayment')
@section('page_title', 'Edit Loan Repayment')

@php
    $loanJs = [
        'balance' => (float) ($context['current_balance'] ?? 0),
        'release_date' => optional($context['detail']?->release_date)->format('Y-m-d'),
        'interest_week_interval' => $context['detail']?->interest_week_interval,
        'carry_forward_total' => (float) ($context['pending_carry_forward_amount'] ?? 0),
        'carry_forwards' => collect($context['pending_carry_forwards'] ?? [])->map(fn ($payment) => [
            'id' => $payment->id,
            'paid_at' => optional($payment->paid_at)->format('Y-m-d'),
            'interest_expected' => (float) ($payment->interest ?? 0),
            'interest_paid' => (float) ($payment->interest_paid ?? 0),
            'remaining' => (float) ($payment->outstanding_interest ?? 0),
        ])->values()->all(),
    ];
@endphp

@push('styles')
    <style>
        .loan-repayment-edit-shell {
            display: grid;
            gap: 1.1rem;
        }

        .loan-repayment-edit-hero,
        .loan-repayment-edit-card {
            border: 1px solid #dbe5f0;
            border-radius: 1rem;
            background: #fff;
            overflow: hidden;
        }

        .loan-repayment-edit-hero {
            padding: 1.15rem 1.2rem;
            background: linear-gradient(135deg, #f8fbff 0%, #eef6ff 55%, #f6fffb 100%);
        }

        .loan-repayment-edit-hero h3 {
            margin: 0 0 0.25rem;
            font-size: 1.3rem;
            font-weight: 700;
            color: #0f172a;
        }

        .loan-repayment-edit-hero p {
            margin: 0;
            color: #475569;
        }

        .loan-repayment-edit-header {
            padding: 0.95rem 1.1rem;
            border-bottom: 1px solid #e5edf5;
            background: #f8fafc;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
        }

        .loan-repayment-edit-body {
            padding: 1.1rem;
        }

        .loan-repayment-edit-grid .mb-3 {
            margin-bottom: 0.9rem !important;
        }

        .repayment-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.9rem;
            margin-bottom: 1rem;
        }

        .repayment-stat {
            border: 1px solid #dbe5f0;
            border-radius: 0.85rem;
            padding: 0.8rem 0.95rem;
            background: rgba(255,255,255,0.82);
        }

        .repayment-stat-label {
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            margin-bottom: 0.35rem;
        }

        .repayment-stat-value {
            font-size: 1.08rem;
            font-weight: 700;
            color: #0f172a;
        }

        .repayment-inline-note {
            display: flex;
            align-items: flex-start;
            gap: 0.65rem;
            padding: 0.85rem 0.95rem;
            border-radius: 0.85rem;
            border: 1px solid #dbe5f0;
            background: #f8fafc;
            color: #334155;
            margin-bottom: 0.9rem;
        }

        .repayment-inline-note i {
            margin-top: 0.1rem;
            color: #2563eb;
        }

        .carry-forward-list {
            display: grid;
            gap: 0.75rem;
        }

        .carry-forward-item {
            border: 1px solid #fde68a;
            background: #fffbeb;
            border-radius: 0.85rem;
            padding: 0.85rem 1rem;
        }

        @media (max-width: 991.98px) {
            .loan-repayment-edit-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .repayment-summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
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

    <div class="loan-repayment-edit-shell">
        <section class="loan-repayment-edit-hero">
            <h3>Edit loan repayment</h3>
            <p>Adjust the recorded principal and interest values, and the linked society credit entries will be updated automatically.</p>
        </section>

        <div class="loan-repayment-edit-card">
            <div class="loan-repayment-edit-header">
                <div>
                    <h3 class="card-title mb-0">Repayment Details</h3>
                </div>
                <a href="{{ route('loan-payments.show', $repayment) }}" class="btn btn-sm btn-outline-secondary">Back</a>
            </div>
            <form action="{{ route('loan-payments.update', $repayment) }}" method="POST" id="loan-repayment-edit-form">
                @csrf
                @method('PUT')
                <div class="loan-repayment-edit-body">
                <div class="alert alert-info">
                    Editing this repayment will also update the related society credit entries. If you reduce the credit too far and the society balance would become invalid, the system will stop the update.
                </div>

                <div class="repayment-inline-note">
                    <i class="fas fa-circle-info"></i>
                    <div>
                        The interest preview updates live from the current outstanding balance. The repayment schedule notice is advisory only, so you can still record interest outside the exact interval when needed.
                    </div>
                </div>

                <div class="row loan-repayment-edit-grid">
                    <div class="col-lg-6 mb-3">
                        <label>Loan</label>
                        <input type="text" class="form-control" value="{{ $loan->loan_id }} ({{ $loan->borrower?->detail?->member_no ?: $loan->borrower?->member_no ?: 'N/A' }} - {{ $loan->borrower?->name ?: 'N/A' }})" disabled>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label for="paid_at">Transaction Date</label>
                        <input type="date" name="paid_at" id="paid_at" class="form-control @error('paid_at') is-invalid @enderror" value="{{ old('paid_at', optional($repayment->paid_at)->format('Y-m-d')) }}">
                        @error('paid_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-lg-4 mb-3">
                        <label for="repayment_amount">Amount to be Paid</label>
                        <input type="number" min="0" step="0.01" name="repayment_amount" id="repayment_amount" class="form-control @error('repayment_amount') is-invalid @enderror" value="{{ old('repayment_amount', number_format((float) ($repayment->repayment_amount ?? 0), 2, '.', '')) }}">
                        @error('repayment_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-lg-4 mb-3">
                        <label for="interest_rate">Interest Rate (%)</label>
                        <input type="number" min="0" step="0.01" name="interest_rate" id="interest_rate" class="form-control @error('interest_rate') is-invalid @enderror" value="{{ old('interest_rate', $repayment->interest_rate !== null ? number_format((float) $repayment->interest_rate, 2, '.', '') : '') }}">
                        @error('interest_rate')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-lg-4 mb-3">
                        <label for="interest_paid">Interest to Pay Now</label>
                        <input type="number" min="0" step="0.01" name="interest_paid" id="interest_paid" class="form-control @error('interest_paid') is-invalid @enderror" value="{{ old('interest_paid', number_format((float) ($repayment->interest_paid ?? 0), 2, '.', '')) }}">
                        @error('interest_paid')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="repayment-summary-grid">
                    <div class="repayment-stat">
                        <div class="repayment-stat-label">Amount Owed</div>
                        <div class="repayment-stat-value" id="amount-owed">&#8358;0.00</div>
                    </div>
                    <div class="repayment-stat">
                        <div class="repayment-stat-label">Suggested Interest</div>
                        <div class="repayment-stat-value text-info" id="suggested-interest">&#8358;0.00</div>
                    </div>
                    <div class="repayment-stat">
                        <div class="repayment-stat-label">Projected Loan Balance</div>
                        <div class="repayment-stat-value" id="projected-balance">&#8358;0.00</div>
                    </div>
                </div>

                <div class="alert alert-light border" id="interest-breakdown"></div>
                <div class="alert alert-info d-none" id="due-cycle-notice"></div>

                <div class="alert alert-warning d-none" id="carry-forward-notice">
                    <strong>Uncompleted interest is pending.</strong>
                    <div class="mt-2 carry-forward-list" id="carry-forward-list"></div>
                </div>

                <div class="form-group mt-3 d-none" id="carry-forward-toggle-wrap">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="carry_forward_remaining" name="carry_forward_remaining" value="1" @checked(old('carry_forward_remaining', (int) ($repayment->carry_forward ?? 0) === 1))>
                        <label class="custom-control-label" for="carry_forward_remaining">
                            Carry any remaining unpaid interest forward to the next repayment
                        </label>
                    </div>
                    <small class="form-text text-muted d-none" id="carry-forward-helper">
                        This has been checked automatically because the interest paid is lower than the suggested interest. You can uncheck it if you do not want the remainder carried forward.
                    </small>
                </div>

                <div class="form-group mt-3">
                    <label for="remarks">Remarks <span class="text-muted small">(Optional)</span></label>
                    <textarea name="remarks" id="remarks" rows="3" class="form-control @error('remarks') is-invalid @enderror">{{ old('remarks', $repayment->remarks) }}</textarea>
                    @error('remarks')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                </div>

                <div class="loan-repayment-edit-header">
                    <a href="{{ route('loan-payments.show', $repayment) }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i>
                        Update Repayment
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const loan = @json($loanJs);
            const paidAtInput = document.getElementById('paid_at');
            const repaymentInput = document.getElementById('repayment_amount');
            const interestRateInput = document.getElementById('interest_rate');
            const interestPaidInput = document.getElementById('interest_paid');
            const amountOwedNode = document.getElementById('amount-owed');
            const suggestedInterestNode = document.getElementById('suggested-interest');
            const projectedBalanceNode = document.getElementById('projected-balance');
            const interestBreakdownNode = document.getElementById('interest-breakdown');
            const dueCycleNoticeNode = document.getElementById('due-cycle-notice');
            const carryForwardNoticeNode = document.getElementById('carry-forward-notice');
            const carryForwardListNode = document.getElementById('carry-forward-list');
            const carryForwardToggleWrap = document.getElementById('carry-forward-toggle-wrap');
            const carryForwardCheckbox = document.getElementById('carry_forward_remaining');
            const carryForwardHelper = document.getElementById('carry-forward-helper');

            const formatMoney = (value) => `₦${new Intl.NumberFormat('en-NG', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(Number(value || 0))}`;

            const parseDate = (value) => value ? new Date(`${value}T00:00:00`) : null;

            const isDueCycle = (paidAt) => {
                const releaseDate = parseDate(loan.release_date);
                const paidDate = parseDate(paidAt);

                if (!releaseDate || !paidDate || paidDate < releaseDate) {
                    return {
                        isDue: false,
                        label: 'This repayment date is earlier than the release date or the release date is missing.',
                    };
                }

                const diffDays = Math.floor((paidDate - releaseDate) / (1000 * 60 * 60 * 24));
                const interval = loan.interest_week_interval;

                if (interval === 'weekly' || interval === 'every-2-weeks' || interval === 'every-3-weeks') {
                    const days = interval === 'weekly' ? 7 : interval === 'every-2-weeks' ? 14 : 21;
                    const due = diffDays > 0 && diffDays % days === 0;
                    return {
                        isDue: due,
                        label: due
                            ? 'This repayment date falls on a scheduled interest interval.'
                            : 'This repayment date is outside the scheduled interest interval. Interest can still be paid later.',
                    };
                }

                if (interval === 'monthly') {
                    const due = releaseDate.getDate() === paidDate.getDate() && paidDate > releaseDate;
                    return {
                        isDue: due,
                        label: due
                            ? 'This repayment date falls on a scheduled monthly interest interval.'
                            : 'This repayment date is outside the scheduled monthly interest interval. Interest can still be paid later.',
                    };
                }

                return {
                    isDue: false,
                    label: 'No interest schedule is available for this loan.',
                };
            };

            const updatePreview = () => {
                const paidAt = paidAtInput.value;
                const repaymentAmount = Number(repaymentInput.value || 0);
                const interestRate = Number(interestRateInput.value || 0);
                const interestPaid = Number(interestPaidInput.value || 0);
                const cycle = isDueCycle(paidAt);
                const currentInterestDue = loan.balance * (interestRate / 100);
                const suggestedInterest = loan.carry_forward_total + currentInterestDue;
                const appliedInterest = Math.min(interestPaid, suggestedInterest);
                const excessInterest = Math.max(interestPaid - suggestedInterest, 0);
                const projectedBalance = Math.max(loan.balance - (repaymentAmount + excessInterest), 0);
                const remainingInterest = Math.max(suggestedInterest - appliedInterest, 0);

                amountOwedNode.textContent = formatMoney(loan.balance);
                suggestedInterestNode.textContent = formatMoney(suggestedInterest);
                projectedBalanceNode.textContent = formatMoney(projectedBalance);

                interestBreakdownNode.innerHTML = `
                    <strong>Interest breakdown:</strong>
                    Current balance ${formatMoney(loan.balance)}
                    × rate ${interestRate.toFixed(2)}%
                    = ${formatMoney(currentInterestDue)}
                    ${loan.carry_forward_total > 0 ? `<br>Carried-forward interest: ${formatMoney(loan.carry_forward_total)}` : ''}
                    <br><strong>Total suggested interest:</strong> ${formatMoney(suggestedInterest)}.
                    ${excessInterest > 0 ? `<br>Excess interest entry ${formatMoney(excessInterest)} will be returned to principal repayment.` : ''}
                `;

                dueCycleNoticeNode.textContent = cycle.label;
                dueCycleNoticeNode.classList.remove('d-none');

                if (loan.carry_forwards.length > 0) {
                    carryForwardListNode.innerHTML = loan.carry_forwards.map((item) => `
                        <div class="carry-forward-item">
                            <div><strong>Date:</strong> ${item.paid_at || 'N/A'}</div>
                            <div><strong>Interest expected:</strong> ${formatMoney(item.interest_expected)}</div>
                            <div><strong>Interest paid:</strong> ${formatMoney(item.interest_paid)}</div>
                            <div><strong>Remaining:</strong> ${formatMoney(item.remaining)}</div>
                        </div>
                    `).join('');
                    carryForwardNoticeNode.classList.remove('d-none');
                } else {
                    carryForwardNoticeNode.classList.add('d-none');
                    carryForwardListNode.innerHTML = '';
                }

                if (remainingInterest > 0) {
                    carryForwardToggleWrap.classList.remove('d-none');
                    if (interestPaid > 0 && interestPaid < suggestedInterest) {
                        carryForwardCheckbox.checked = true;
                        carryForwardHelper.classList.remove('d-none');
                    } else {
                        carryForwardHelper.classList.add('d-none');
                    }
                } else {
                    carryForwardToggleWrap.classList.add('d-none');
                    carryForwardCheckbox.checked = false;
                    carryForwardHelper.classList.add('d-none');
                }
            };

            [paidAtInput, repaymentInput, interestRateInput, interestPaidInput].forEach((field) => {
                field.addEventListener('change', updatePreview);
                field.addEventListener('input', updatePreview);
            });

            updatePreview();
        })();
    </script>
@endpush
