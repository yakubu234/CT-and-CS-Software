<div class="alert alert-light border mb-4">
    Birthday and monthly statement automations can be branch-specific or global. Monthly statement uses the selected day of month and sends a balance summary by SMS.
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
            <label for="branch_id">Branch</label>
            <select name="branch_id" id="branch_id" class="form-control select2">
                <option value="">All accessible branches</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((string) old('branch_id', $rule->branch_id ?? $currentBranch?->id) === (string) $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
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
            <label for="schedule_time">Schedule Time</label>
            <input type="time" name="schedule_time" id="schedule_time" class="form-control" value="{{ old('schedule_time', $rule->schedule_time ?? '08:00') }}">
        </div>
    </div>
    <div class="col-md-4 monthly-only">
        <div class="form-group">
            <label for="day_of_month">Day of Month</label>
            <input type="number" min="1" max="28" name="day_of_month" id="day_of_month" class="form-control" value="{{ old('day_of_month', $rule->day_of_month ?? 1) }}">
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
</div>

<div class="mt-3">
    <button type="submit" class="btn btn-primary">{{ $submitLabel ?? 'Save Rule' }}</button>
    <a href="{{ route('bulk-sms.automations.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>

@push('scripts')
    <script>
        (() => {
            const eventField = document.getElementById('event');
            const monthlyWrap = document.querySelector('.monthly-only');

            const syncEventFields = () => {
                if (! eventField || ! monthlyWrap) {
                    return;
                }

                monthlyWrap.classList.toggle('d-none', eventField.value !== 'monthly_statement');
            };

            eventField?.addEventListener('change', syncEventFields);
            syncEventFields();
        })();
    </script>
@endpush
