<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="name">Template Name</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $smsTemplate->name ?? '') }}" required>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="category">Category</label>
            <select name="category" id="category" class="form-control select2" required>
                @foreach ($categoryOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('category', $smsTemplate->category ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-control select2" required>
                <option value="1" @selected((string) old('status', isset($smsTemplate) ? (int) $smsTemplate->status : '1') === '1')>Active</option>
                <option value="0" @selected((string) old('status', isset($smsTemplate) ? (int) $smsTemplate->status : '1') === '0')>Inactive</option>
            </select>
        </div>
    </div>
    <div class="col-12">
        <div class="form-group">
            <label for="description">Description</label>
            <input type="text" name="description" id="description" class="form-control" value="{{ old('description', $smsTemplate->description ?? '') }}">
        </div>
    </div>
    <div class="col-lg-8">
        <div class="form-group">
            <label for="body">Message Body</label>
            <textarea name="body" id="body" rows="8" class="form-control" required>{{ old('body', $smsTemplate->body ?? '') }}</textarea>
        </div>
    </div>
    <div class="col-lg-4">
        @php($ph = fn (string $name): string => str_repeat('{', 2) . $name . str_repeat('}', 2))
        <div class="card bg-light">
            <div class="card-header">
                <strong>Placeholders for this category</strong>
            </div>
            <div class="card-body p-2" style="max-height: 420px; overflow-y: auto;">
                <p class="small text-muted mb-2">Click one to insert it. Each needs two braces on both sides, e.g. <code>@{{member_name}}</code>.</p>
                @foreach ($placeholderHints as $event => $group)
                    <div class="placeholder-hints" data-event="{{ $event }}">
                        @foreach ($group['placeholders'] as $name => $description)
                            <div class="mb-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 insert-placeholder" data-value="{{ $ph($name) }}">{{ $ph($name) }}</button>
                                <small class="text-muted d-block">{{ $description }}</small>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
        @isset($smsTemplate)
            <a href="{{ route('bulk-sms.test.create', ['template' => $smsTemplate->id]) }}" class="btn btn-outline-info btn-block"><i class="fas fa-vial mr-1"></i>Test this template with sample data</a>
        @endisset
    </div>
</div>

@push('scripts')
    <script>
        (() => {
            const category = document.getElementById('category');
            const body = document.getElementById('body');
            // Template categories that are not an alert type use the general placeholders.
            const eventFor = (value) => document.querySelector('.placeholder-hints[data-event="' + value + '"]') ? value : 'manual';

            const sync = () => {
                const event = eventFor(category.value);
                document.querySelectorAll('.placeholder-hints').forEach((group) => {
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

            // Select2 fires jQuery "change" events, which native addEventListener does not receive.
            if (window.jQuery) {
                window.jQuery(category).on('change', sync);
            } else {
                category.addEventListener('change', sync);
            }
            sync();
        })();
    </script>
@endpush

<div class="mt-3">
    <button type="submit" class="btn btn-primary">{{ $submitLabel ?? 'Save Template' }}</button>
    <a href="{{ route('bulk-sms.templates.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
