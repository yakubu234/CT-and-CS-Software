<div class="alert alert-light border mb-4">
    Credit, debit, loan repayment and loan approval alerts go only to the member involved, as soon as the transaction is saved (one alert per member per posting, covering every account in it). Birthday greetings go out each day at the schedule time to that day's celebrants. Monthly statements go out on the chosen day of the month with each member's balances.
    <a href="{{ route('bulk-sms.test.create', isset($rule) ? ['rule' => $rule->id] : []) }}" class="d-inline-block mt-1"><i class="fas fa-vial mr-1"></i>Test an alert with sample data before activating it</a>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="name">Rule Name</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $rule->name ?? '') }}" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            @isset($rule)
                {{-- An existing rule belongs to one branch (or all); edit each branch's copy separately. --}}
                <label for="branch_id">Branch</label>
                <select name="branch_id" id="branch_id" class="form-control select2">
                    <option value="">All branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) old('branch_id', $rule->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            @else
                @php($selectedBranches = array_map('strval', (array) old('branch_ids', $currentBranch ? [$currentBranch->id] : ['all'])))
                <label for="branch_ids">Branches</label>
                <select name="branch_ids[]" id="branch_ids" class="form-control select2 branch-multi" multiple data-placeholder="Choose one or more branches">
                    <option value="all" @selected(in_array('all', $selectedBranches, true))>All branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(in_array((string) $branch->id, $selectedBranches, true))>{{ $branch->name }}</option>
                    @endforeach
                </select>
                <small class="text-muted">Each selected branch gets its own copy of this rule. "All branches" creates one rule that also covers branches added later.</small>
                @error('branch_ids')<div class="text-danger small">{{ $message }}</div>@enderror
            @endisset
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="event">Automation Event</label>
            <select name="event" id="event" class="form-control select2" required>
                @foreach ($eventOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('event', $rule->event ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="template_id">Template</label>
            <select name="template_id" id="template_id" class="form-control select2" required>
                @foreach ($templates as $template)
                    <option value="{{ $template->id }}" @selected((string) old('template_id', $rule->template_id ?? '') === (string) $template->id)>
                        {{ $template->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            @php($selectedDay = (int) old('day_of_month', $rule->day_of_month ?? 1))
            <label for="day_of_month">Day of the Month</label>
            <select name="day_of_month" id="day_of_month" class="form-control select2">
                @for ($day = 1; $day <= 31; $day++)
                    <option value="{{ $day }}" @selected($selectedDay === $day)>{{ $day }}{{ in_array($day % 100, [11, 12, 13], true) ? 'th' : (['th', 'st', 'nd', 'rd'][$day % 10] ?? 'th') }}</option>
                @endfor
            </select>
            <small class="text-muted day-hint" data-event-hint="monthly_statement">If a month is shorter (e.g. 31st in April, or 29th-31st in February), it sends on that month's last day.</small>
            <small class="text-muted day-hint d-none" data-event-hint="birthday">Not used for Birthday. Every day at the schedule time, members in the selected branches whose date of birth (day and month) is today receive the greeting.</small>
            <small class="text-muted day-hint d-none" data-event-hint="instant">Not used for this event. The message sends immediately when the event happens.</small>
            @error('day_of_month')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="schedule_time">Schedule Time</label>
            <input type="time" name="schedule_time" id="schedule_time" class="form-control" value="{{ old('schedule_time', $rule->schedule_time ?? '08:00') }}">
            <small class="text-muted day-hint d-none" data-event-hint="instant">Not used: transaction alerts send immediately.</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-control select2" required>
                <option value="1" @selected((string) old('status', isset($rule) ? (int) $rule->status : '1') === '1')>Active</option>
                <option value="0" @selected((string) old('status', isset($rule) ? (int) $rule->status : '1') === '0')>Inactive</option>
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            @php($sendMode = old('send_mode', isset($rule) ? $rule->sendMode() : \App\Models\SmsAutomationRule::SEND_AUTOMATIC))
            <label for="send_mode">Sending Method</label>
            <select name="send_mode" id="send_mode" class="form-control select2" required>
                <option value="automatic" @selected($sendMode === 'automatic')>Automated - send by itself when the event happens</option>
                <option value="manual" @selected($sendMode === 'manual')>Manual - prepare the message, staff send it</option>
            </select>
            <small class="text-muted">Manual messages wait in <a href="{{ route('bulk-sms.logs.index', ['status' => 'awaiting']) }}">SMS Logs</a> with status "Awaiting" until authorised staff press Send. You can switch method at any time; it applies to new messages.</small>
            @error('send_mode')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="mt-3">
    <button type="submit" class="btn btn-primary">{{ $submitLabel ?? 'Save Rule' }}</button>
    <a href="{{ route('bulk-sms.automations.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>

@include('bulk-sms.partials.branch-multi-script')

@push('scripts')
    <script>
        (() => {
            const eventField = document.getElementById('event');
            const dayField = document.getElementById('day_of_month');

            if (! eventField || ! dayField) {
                return;
            }

            const syncDayField = () => {
                const event = eventField.value;
                const hintKey = event === 'monthly_statement' || event === 'birthday' ? event : 'instant';

                // Only Monthly Statement uses the day; a disabled field is not submitted,
                // so the saved day is kept untouched for other events.
                dayField.disabled = event !== 'monthly_statement';
                document.querySelectorAll('.day-hint').forEach((hint) => {
                    hint.classList.toggle('d-none', hint.dataset.eventHint !== hintKey);
                });

                if (window.jQuery) {
                    window.jQuery(dayField).trigger('change.select2');
                }
            };

            // Select2 fires jQuery "change" events, which native addEventListener does not receive.
            if (window.jQuery) {
                window.jQuery(eventField).on('change', syncDayField);
            } else {
                eventField.addEventListener('change', syncDayField);
            }
            syncDayField();
        })();
    </script>
@endpush
