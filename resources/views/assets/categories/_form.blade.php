<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="name">Category Name</label>
            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $category->name) }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                <option value="1" @selected((string) old('status', (int) $category->status) === '1')>Active</option>
                <option value="0" @selected((string) old('status', (int) $category->status) === '0')>Inactive</option>
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12">
        <div class="form-group">
            <label for="description">Description <span class="text-muted small">(Optional)</span></label>
            <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $category->description) }}</textarea>
            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="mt-3">
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save mr-1"></i>
        {{ $submitLabel ?? 'Save Category' }}
    </button>
    <a href="{{ route('asset-categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
