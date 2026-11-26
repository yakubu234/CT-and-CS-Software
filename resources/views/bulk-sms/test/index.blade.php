@extends('layouts.admin')

@section('title', 'Test SMS Alerts')
@section('page_title', 'Test SMS Alerts')

@section('content')
    @php
        $event = $input['event'] ?? 'transaction_credit';
        $amounts = $input['amounts'] ?? [];
        // Wraps a name in double braces. Built in PHP because writing the braces inside a
        // Blade echo would end the echo early.
        $ph = fn (string $name): string => str_repeat('{', 2) . $name . str_repeat('}', 2);
    @endphp

    <div class="alert alert-light border">
        Build the message exactly as a real alert would: choose the alert type and template, pick a member (or just enter a phone number),
        use the member's latest real transaction or your own sample figures, then <strong>Preview</strong>. When it looks right, <strong>Send test</strong> sends it to one phone number only.
        Nothing is posted to any account.
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card card-outline card-primary">
                <div class="card-body">
                    <form method="POST" action="{{ route('bulk-sms.test.run') }}" id="sms-test-form">
                        @csrf

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="event">Alert type</label>
                                <select name="event" id="event" class="form-control select2" required>
                                    @foreach ($eventLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($event === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="template_id">Template</label>
                                <select name="template_id" id="template_id" class="form-control select2">
                                    <option value="">Type the message below</option>
                                    @foreach ($templates as $template)
                                        <option value="{{ $template->id }}" data-body="{{ $template->body }}" @selected((string) ($input['template_id'] ?? '') === (string) $template->id)>{{ $template->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="body">Message</label>
                            <textarea name="body" id="body" rows="5" class="form-control" required>{{ $input['body'] ?? '' }}</textarea>
                            <small class="text-muted">Edits here are only for this test. To keep them, update the template in SMS Templates. Click a placeholder to insert it:</small>
                            <div class="mt-1">
                                @foreach ($allPlaceholders as $placeholderEvent => $names)
                                    <div class="placeholder-group {{ $placeholderEvent === $event ? '' : 'd-none' }}" data-event="{{ $placeholderEvent }}">
                                        @foreach ($names as $name)
                                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1 mb-1 insert-placeholder" data-value="{{ $ph($name) }}" style="font-size: .75rem;">{{ $name }}</button>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="branch_id">Society</label>
                                <select name="branch_id" id="branch_id" class="form-control select2" required>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected((string) ($input['branch_id'] ?? '') === (string) $branch->id)>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">The society the alert is for. It fills <code>society_name</code>.</small>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="member_id">Test member <span class="text-muted font-weight-normal">(optional)</span></label>
                                <select name="member_id" id="member_id" class="form-control" data-native-select="true">
                                    <option value="">No member - use sample details</option>
                                    @if ($selectedMember)
                                        <option value="{{ $selectedMember->id }}" selected>{{ $selectedMember->display_member_no ?: 'N/A' }} - {{ $selectedMember->name }}</option>
                                    @endif
                                </select>
                                <small class="text-muted">Search by name, number, email or phone.</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="phone">Send the test to</label>
                            <input type="text" name="phone" id="phone" class="form-control" value="{{ $input['phone'] ?? '' }}" placeholder="e.g. 08031234567 (leave empty to use the member's phone)">
                        </div>

                        <div class="form-group event-fields" data-events="transaction_credit,transaction_debit,loan_repayment">
                            <label class="d-block">Transaction details</label>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="source-sample" name="data_source" value="sample" class="custom-control-input" @checked(($input['data_source'] ?? 'sample') === 'sample')>
                                <label class="custom-control-label" for="source-sample">Use the sample figures below</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="source-latest" name="data_source" value="latest" class="custom-control-input" @checked(($input['data_source'] ?? '') === 'latest')>
                                <label class="custom-control-label" for="source-latest">Use the member's latest real transaction</label>
                            </div>
                        </div>
                        <input type="hidden" name="data_source" value="sample" class="event-fields" data-events="loan_approved,birthday,monthly_statement,manual" disabled>

                        <div class="form-row">
                            <div class="form-group col-md-4 event-fields" data-events="transaction_credit,transaction_debit,loan_repayment,loan_approved,birthday,manual">
                                <label for="transaction_date">Date</label>
                                <input type="date" name="transaction_date" id="transaction_date" class="form-control" value="{{ $input['transaction_date'] ?? now()->format('Y-m-d') }}">
                            </div>
                            <div class="form-group col-md-8 event-fields" data-events="monthly_statement">
                                <label for="statement_month">Statement month</label>
                                <input type="month" name="statement_month" id="statement_month" class="form-control" value="{{ $input['statement_month'] ?? now()->subMonthNoOverflow()->format('Y-m') }}">
                                <small class="text-muted">Figures are worked out from the member's real transactions and loans for this month, as at month-end. The automation always uses the last complete month before the send date.</small>
                            </div>
                        </div>

                        <div class="form-row event-fields" data-events="transaction_credit,transaction_debit">
                            @foreach ($accountTypes as $key => $type)
                                <div class="form-group col-md-3 col-6">
                                    <label for="amount-{{ $key }}">{{ ucfirst(strtolower($type)) }}</label>
                                    <input type="number" step="0.01" min="0" name="amounts[{{ $key }}]" id="amount-{{ $key }}" class="form-control" value="{{ $amounts[$key] ?? 0 }}">
                                </div>
                            @endforeach
                            <small class="col-12 text-muted mb-3">Amount for each account in this sample posting (0 = account not included). All accounts go into one alert, as in a real posting.</small>
                        </div>

                        <div class="form-row event-fields" data-events="loan_repayment">
                            <div class="form-group col-md-4"><label>Principal paid</label><input type="number" step="0.01" min="0" name="principal_paid" class="form-control" value="{{ $input['principal_paid'] ?? 0 }}"></div>
                            <div class="form-group col-md-4"><label>Interest paid</label><input type="number" step="0.01" min="0" name="interest_paid" class="form-control" value="{{ $input['interest_paid'] ?? 0 }}"></div>
                            <div class="form-group col-md-4"><label>Loan balance left</label><input type="number" step="0.01" min="0" name="outstanding_loan_balance" class="form-control" value="{{ $input['outstanding_loan_balance'] ?? 0 }}"></div>
                            <div class="form-group col-md-4"><label>Interest still owed</label><input type="number" step="0.01" min="0" name="outstanding_interest" class="form-control" value="{{ $input['outstanding_interest'] ?? 0 }}"></div>
                        </div>

                        <div class="form-row event-fields" data-events="loan_repayment,loan_approved">
                            <div class="form-group col-md-4"><label>Loan reference</label><input type="text" name="loan_id" class="form-control" value="{{ $input['loan_id'] ?? '' }}"></div>
                            <div class="form-group col-md-4"><label>Loan amount</label><input type="number" step="0.01" min="0" name="loan_amount" class="form-control" value="{{ $input['loan_amount'] ?? 0 }}"></div>
                        </div>

                        <div class="d-flex flex-wrap">
                            <button type="submit" name="action" value="preview" class="btn btn-primary mr-2 mb-2"><i class="fas fa-eye mr-1"></i>Preview</button>
                            <button type="submit" name="action" value="send" class="btn btn-success mb-2" onclick="return confirm('Send this test SMS to one phone number?')"><i class="fas fa-paper-plane mr-1"></i>Send test</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            @if ($sendResult)
                <div class="alert alert-{{ $sendResult['successful'] ? 'success' : 'danger' }}">{{ $sendResult['message'] }}</div>
            @endif

            <div class="card">
                <div class="card-header"><strong>Preview</strong></div>
                <div class="card-body">
                    @if (! $preview)
                        <p class="text-muted mb-0">Press <strong>Preview</strong> to see the exact message and every placeholder value.</p>
                    @else
                        @foreach ($preview['notes'] as $note)
                            <div class="small text-muted mb-1"><i class="fas fa-info-circle mr-1"></i>{{ $note }}</div>
                        @endforeach

                        <div class="p-3 my-3" style="background:#e9ecef;border-radius:16px;white-space:pre-wrap;font-size:1rem;">{{ $preview['message'] }}</div>
                        <div class="small text-muted mb-3">
                            {{ $preview['length']['characters'] }} characters &middot; {{ $preview['length']['pages'] }} SMS page{{ $preview['length']['pages'] === 1 ? '' : 's' }} &middot; {{ $preview['length']['encoding'] }}
                        </div>

                        @php($issues = $preview['issues'])
                        @if ($issues['malformed'])
                            <div class="alert alert-danger py-2 small">
                                <strong>Broken placeholder{{ count($issues['malformed']) > 1 ? 's' : '' }}:</strong>
                                @foreach ($issues['malformed'] as $fragment)<code class="mr-1">{{ $fragment }}</code>@endforeach
                                <br>Each placeholder needs two braces on both sides, e.g. <code>@{{interest_paid}}</code>.
                            </div>
                        @endif
                        @if ($issues['unknown'])
                            <div class="alert alert-danger py-2 small">
                                <strong>Unknown placeholder{{ count($issues['unknown']) > 1 ? 's' : '' }} (will be blank):</strong>
                                @foreach ($issues['unknown'] as $name)<code class="mr-1">{{ $ph($name) }}</code>@endforeach
                            </div>
                        @endif
                        @if ($issues['other_event'])
                            <div class="alert alert-warning py-2 small">
                                <strong>Not available for {{ $eventLabels[$event] ?? $event }} (will be blank):</strong>
                                @foreach ($issues['other_event'] as $name)<code class="mr-1">{{ $ph($name) }}</code>@endforeach
                            </div>
                        @endif
                        @if (! $issues['malformed'] && ! $issues['unknown'] && ! $issues['other_event'])
                            <div class="alert alert-success py-2 small mb-3"><i class="fas fa-check mr-1"></i>All placeholders are valid for this alert type.</div>
                        @endif

                        @if ($preview['values'])
                            <table class="table table-sm table-bordered small mb-0">
                                <thead><tr><th>Placeholder</th><th>Value used</th></tr></thead>
                                <tbody>
                                @foreach ($preview['values'] as $name => $value)
                                    <tr class="{{ $value === null ? 'table-danger' : '' }}">
                                        <td><code>{{ $name }}</code></td>
                                        <td>{{ $value ?? '(empty)' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @endif
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><strong>Placeholders for {{ $eventLabels[$event] ?? $event }}</strong></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0 small">
                        @foreach ($placeholders as $name => [$description, $sample])
                            <tr><td><code>{{ $ph($name) }}</code></td><td>{{ $description }}</td></tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const $ = window.jQuery;
            const body = document.getElementById('body');
            const eventField = document.getElementById('event');

            const syncEvent = () => {
                const event = eventField.value;
                document.querySelectorAll('.event-fields').forEach((element) => {
                    const show = element.dataset.events.split(',').includes(event);
                    element.classList.toggle('d-none', ! show);
                    if (element.tagName === 'INPUT') {
                        element.disabled = ! show;
                    }
                });
                document.querySelectorAll('.placeholder-group').forEach((group) => {
                    group.classList.toggle('d-none', group.dataset.event !== event);
                });
            };

            document.querySelectorAll('.insert-placeholder').forEach((button) => {
                button.addEventListener('click', () => {
                    const start = body.selectionStart ?? body.value.length;
                    const end = body.selectionEnd ?? body.value.length;
                    body.value = body.value.slice(0, start) + button.dataset.value + body.value.slice(end);
                    body.focus();
                    body.selectionStart = body.selectionEnd = start + button.dataset.value.length;
                });
            });

            const fillTemplate = () => {
                const option = document.querySelector('#template_id option:checked');
                if (! option || ! option.dataset.body) {
                    return;
                }
                if (body.value.trim() === '' || body.value === option.dataset.body || confirm('Replace the message with this template?')) {
                    body.value = option.dataset.body;
                }
            };

            if ($) {
                // Select2 fires jQuery "change" events, which native addEventListener does not receive.
                $(eventField).on('change', syncEvent);
                $('#template_id').on('change', fillTemplate);

                if (typeof $.fn.select2 === 'function') {
                    $('#member_id').select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        allowClear: true,
                        placeholder: 'No member - use sample details',
                        minimumInputLength: 2,
                        ajax: {
                            url: @json(route('bulk-sms.campaigns.members.search')),
                            dataType: 'json',
                            delay: 300,
                            data: (params) => ({ q: params.term || '', page: params.page || 1, branch_ids: [$('#branch_id').val()] }),
                            processResults: (data) => data,
                        },
                    });
                }
            } else {
                eventField.addEventListener('change', syncEvent);
                document.getElementById('template_id').addEventListener('change', fillTemplate);
            }

            syncEvent();
        })();
    </script>
@endpush
