<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="name">Template Name</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $emailTemplate->name ?? '') }}" required>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="category">Category</label>
            <select name="category" id="category" class="form-control select2" required>
                @foreach ($categoryOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('category', $emailTemplate->category ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-control select2" required>
                <option value="1" @selected((string) old('status', isset($emailTemplate) ? (int) $emailTemplate->status : '1') === '1')>Active</option>
                <option value="0" @selected((string) old('status', isset($emailTemplate) ? (int) $emailTemplate->status : '1') === '0')>Inactive</option>
            </select>
        </div>
    </div>
    <div class="col-12">
        <div class="form-group">
            <label for="description">Description</label>
            <input type="text" name="description" id="description" class="form-control" value="{{ old('description', $emailTemplate->description ?? '') }}">
        </div>
    </div>
    <div class="col-12">
        <div class="form-group">
            <label for="subject">Subject</label>
            <input type="text" name="subject" id="subject" class="form-control" value="{{ old('subject', $emailTemplate->subject ?? '') }}" required>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="form-group">
            <label for="body">Email Body</label>
            <textarea name="body" id="body" rows="12" class="form-control" required>{{ old('body', $emailTemplate->body ?? '') }}</textarea>
            <small class="text-muted">HTML is supported. Use placeholders for personalized details.</small>
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
    <a href="{{ route('email.templates.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
