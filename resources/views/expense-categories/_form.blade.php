@php($expenseCategory = $expenseCategory ?? null)

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="name">Category Name</label>
            <input
                type="text"
                name="name"
                id="name"
                class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name', $expenseCategory->name ?? '') }}"
                maxlength="30"
                required
            >
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="related_to">Related To</label>
            <select name="related_to" id="related_to" class="form-control @error('related_to') is-invalid @enderror" required>
                @php($selectedRelatedTo = old('related_to', $expenseCategory->related_to ?? 'dr'))
                @foreach ($relatedToOptions as $value => $label)
                    <option value="{{ $value }}" @selected($selectedRelatedTo === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('related_to')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="status">Status</label>
            @php($selectedStatus = (string) old('status', isset($expenseCategory) ? (int) $expenseCategory->status : '1'))
            <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                @foreach ($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected($selectedStatus === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-12">
        <div class="form-group">
            <label for="note">Note</label>
            <textarea
                name="note"
                id="note"
                rows="4"
                class="form-control @error('note') is-invalid @enderror"
                placeholder="Optional note for this category"
            >{{ old('note', $expenseCategory->note ?? '') }}</textarea>
            @error('note')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="card-footer px-0 pb-0">
    <button type="submit" class="btn btn-primary">{{ $submitLabel ?? 'Save Category' }}</button>
    <a href="{{ route('expense-categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
