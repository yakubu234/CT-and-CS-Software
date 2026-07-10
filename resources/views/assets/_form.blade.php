<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="name">Asset Name</label>
            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $asset->name) }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="category">Asset Category</label>
            <select name="category" id="category" class="form-control select2 @error('category') is-invalid @enderror" required>
                @foreach ($categoryOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('category', $asset->category) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="status">Asset Status</label>
            <select name="status" id="status" class="form-control select2 @error('status') is-invalid @enderror" required>
                @foreach ($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $asset->status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="branch_id">Branch</label>
            <select name="branch_id" id="branch_id" class="form-control select2 @error('branch_id') is-invalid @enderror">
                <option value="">Society-wide asset</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((string) old('branch_id', $asset->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
            @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="purchase_date">Purchase Date</label>
            <input type="date" name="purchase_date" id="purchase_date" class="form-control @error('purchase_date') is-invalid @enderror" value="{{ old('purchase_date', optional($asset->purchase_date)->format('Y-m-d')) }}">
            @error('purchase_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="purchase_cost">Purchase Cost</label>
            <input type="number" min="0" step="0.01" name="purchase_cost" id="purchase_cost" class="form-control @error('purchase_cost') is-invalid @enderror" value="{{ old('purchase_cost', $asset->exists ? number_format((float) $asset->purchase_cost, 2, '.', '') : '') }}" required>
            @error('purchase_cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="supplier">Supplier <span class="text-muted small">(Optional)</span></label>
            <input type="text" name="supplier" id="supplier" class="form-control @error('supplier') is-invalid @enderror" value="{{ old('supplier', $asset->supplier) }}">
            @error('supplier')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="depreciation_rate">Depreciation Rate % <span class="text-muted small">(Optional)</span></label>
            <input type="number" min="0" max="100" step="0.01" name="depreciation_rate" id="depreciation_rate" class="form-control @error('depreciation_rate') is-invalid @enderror" value="{{ old('depreciation_rate', $asset->depreciation_rate !== null ? number_format((float) $asset->depreciation_rate, 2, '.', '') : '') }}">
            @error('depreciation_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="disposed_at">Disposed Date</label>
            <input type="date" name="disposed_at" id="disposed_at" class="form-control @error('disposed_at') is-invalid @enderror" value="{{ old('disposed_at', optional($asset->disposed_at)->format('Y-m-d')) }}">
            @error('disposed_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="depreciation_note">Depreciation Note <span class="text-muted small">(Optional)</span></label>
            <textarea name="depreciation_note" id="depreciation_note" rows="3" class="form-control @error('depreciation_note') is-invalid @enderror">{{ old('depreciation_note', $asset->depreciation_note) }}</textarea>
            @error('depreciation_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="remarks">Remarks <span class="text-muted small">(Optional)</span></label>
            <textarea name="remarks" id="remarks" rows="3" class="form-control @error('remarks') is-invalid @enderror">{{ old('remarks', $asset->remarks) }}</textarea>
            @error('remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="mt-3">
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save mr-1"></i>
        {{ $submitLabel ?? 'Save Asset' }}
    </button>
    <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
