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
        <div class="card bg-light">
            <div class="card-header">
                <strong>Available Placeholders</strong>
            </div>
            <div class="card-body">
                @foreach ($placeholderHints as $hint)
                    <span class="badge badge-secondary mb-1">{{ $hint }}</span>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="mt-3">
    <button type="submit" class="btn btn-primary">{{ $submitLabel ?? 'Save Template' }}</button>
    <a href="{{ route('bulk-sms.templates.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
