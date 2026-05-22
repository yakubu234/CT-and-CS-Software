@csrf

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title mb-0">Exco Role Details</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="name">Exco Role Name</label>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $designation->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="sort_order">Sort Order</label>
                    <input type="number" min="1" name="sort_order" id="sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $designation->sort_order ?: 1) }}" required>
                    @error('sort_order')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                        <option value="1" @selected((string) old('status', $designation->status ?? 1) === '1')>Active</option>
                        <option value="0" @selected((string) old('status', $designation->status ?? 1) === '0')>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between flex-wrap">
        <x-browser-back-button :fallback="route('exco-roles.index')" label="Cancel" class="btn btn-light" />
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save mr-1"></i>
            Save Exco Role
        </button>
    </div>
</div>
