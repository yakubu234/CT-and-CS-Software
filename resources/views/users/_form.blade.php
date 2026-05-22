@csrf

@push('styles')
    <style>
        .branch-access-panel {
            border: 1px solid #dbe5f0;
            border-radius: 1rem;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            overflow: hidden;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .branch-access-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.9rem 1rem;
            border-bottom: 1px solid #e5edf5;
            background: #f8fafc;
        }

        .branch-access-summary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 0.86rem;
            font-weight: 700;
        }

        .branch-access-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .branch-access-body {
            padding: 0.9rem 1rem 1rem;
        }

        .branch-access-search {
            margin-bottom: 0.85rem;
        }

        .branch-access-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
            max-height: 18rem;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 0.2rem;
        }

        .branch-access-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.85rem 0.9rem;
            border: 1px solid #dbe5f0;
            border-radius: 0.9rem;
            background: #fff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .branch-access-item:hover {
            border-color: #bfdbfe;
            box-shadow: 0 8px 18px rgba(59, 130, 246, 0.08);
        }

        .branch-access-item.is-selected {
            border-color: #93c5fd;
            background: #eff6ff;
        }

        .branch-access-item.is-primary {
            border-color: #86efac;
            background: #f0fdf4;
        }

        .branch-access-item-label {
            flex: 1 1 auto;
            margin: 0;
            cursor: pointer;
            min-width: 0;
        }

        .branch-access-item-name {
            display: block;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.35;
            white-space: normal;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .branch-access-item-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            margin-top: 0.35rem;
        }

        .branch-access-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.5rem;
            border-radius: 999px;
            background: #e2e8f0;
            color: #475569;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .branch-access-badge--primary {
            background: #dcfce7;
            color: #15803d;
        }

        .branch-access-empty {
            padding: 0.85rem 0.9rem;
            border: 1px dashed #cbd5e1;
            border-radius: 0.9rem;
            color: #64748b;
            background: #f8fafc;
        }

        @media (max-width: 767.98px) {
            .branch-access-list {
                grid-template-columns: 1fr;
            }

            .branch-access-toolbar {
                padding: 0.85rem;
            }

            .branch-access-body {
                padding: 0.85rem;
            }
        }
    </style>
@endpush

<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title mb-0">Staff Details</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" name="first_name" id="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $user->getRawOriginal('name')) }}" required>
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" name="last_name" id="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $user->last_name) }}">
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="designation">Designation</label>
                            <input type="text" name="designation" id="designation" class="form-control @error('designation') is-invalid @enderror" value="{{ old('designation', $user->designation) }}" placeholder="Optional admin title">
                            @error('designation')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="password">Password {{ $user->exists ? '(Leave blank to keep current password)' : '' }}</label>
                            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" {{ $user->exists ? '' : 'required' }}>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="password_confirmation">Confirm Password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-header border-top">
                <h3 class="card-title mb-0">Access Setup</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="role_id">Role</label>
                    <select name="role_id" id="role_id" class="form-control select2 @error('role_id') is-invalid @enderror" required>
                        <option value="">Select admin role</option>
                        @foreach ($roles as $roleOption)
                            <option value="{{ $roleOption->id }}" @selected((string) old('role_id', $user->role_id) === (string) $roleOption->id)>{{ $roleOption->name }}</option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="branch_id">Primary Branch <span class="text-danger">*</span></label>
                    <select name="branch_id" id="branch_id" class="form-control select2 @error('branch_id') is-invalid @enderror" required>
                        <option value="">Select branch</option>
                        @foreach ($branches as $branchOption)
                            <option value="{{ $branchOption->id }}" @selected((string) old('branch_id', $user->branch_id) === (string) $branchOption->id)>{{ $branchOption->name }}</option>
                        @endforeach
                    </select>
                    @error('branch_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group allowed-branches-wrap">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                        <label for="assigned_branch_ids" class="mb-0">Allowed Branches <span class="text-muted">(Optional)</span></label>
                    </div>
                    @php
                        $selectedAssignedBranchIds = collect(old('assigned_branch_ids', $assignedBranchIds))
                            ->filter(fn ($value) => is_numeric($value))
                            ->map(fn ($value) => (int) $value)
                            ->unique()
                            ->values()
                            ->all();
                    @endphp

                    <div class="branch-access-panel">
                        <div class="branch-access-toolbar">
                            <div class="branch-access-summary" id="allowed-branches-summary">
                                Choose extra branches
                            </div>
                            <div class="branch-access-actions" role="group" aria-label="Allowed branches quick actions">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="select-all-branches">Select all</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-extra-branches">Clear extras</button>
                            </div>
                        </div>

                        <div class="branch-access-body">
                            <div class="branch-access-search">
                                <input type="search" id="allowed-branches-search" class="form-control" placeholder="Search branches...">
                            </div>

                            <div class="branch-access-list" id="allowed-branches-list">
                                @foreach ($branches as $branchOption)
                                    @php
                                        $branchId = (int) $branchOption->id;
                                        $isChecked = in_array($branchId, $selectedAssignedBranchIds, true);
                                    @endphp
                                    <div class="branch-access-item{{ $isChecked ? ' is-selected' : '' }}" data-branch-item data-branch-name="{{ strtolower($branchOption->name) }}" data-branch-id="{{ $branchId }}">
                                        <div class="custom-control custom-checkbox mt-1">
                                            <input
                                                type="checkbox"
                                                class="custom-control-input"
                                                id="assigned_branch_{{ $branchId }}"
                                                name="assigned_branch_ids[]"
                                                value="{{ $branchId }}"
                                                data-branch-checkbox
                                                @checked($isChecked)
                                            >
                                            <label class="custom-control-label" for="assigned_branch_{{ $branchId }}"></label>
                                        </div>
                                        <label class="branch-access-item-label" for="assigned_branch_{{ $branchId }}">
                                            <span class="branch-access-item-name">{{ $branchOption->name }}</span>
                                            <span class="branch-access-item-meta">
                                            </span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>

                            <div class="branch-access-empty d-none" id="allowed-branches-empty">
                                No branches match your search.
                            </div>
                        </div>
                    </div>

                    <small class="form-text text-muted">The primary branch will always be included automatically.</small>
                    @error('assigned_branch_ids')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    @error('assigned_branch_ids.*')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group mb-0">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                        <option value="1" @selected((string) old('status', $user->status ?? 1) === '1')>Active</option>
                        <option value="0" @selected((string) old('status', $user->status ?? 1) === '0')>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between flex-wrap">
                <x-browser-back-button :fallback="route('users.index')" label="Cancel" class="btn btn-light" />
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i>
                    Save User
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function () {
            const primaryBranchSelect = document.getElementById('branch_id');
            const allowedBranchesSearch = document.getElementById('allowed-branches-search');
            const allowedBranchesList = document.getElementById('allowed-branches-list');
            const emptyState = document.getElementById('allowed-branches-empty');
            const summaryNode = document.getElementById('allowed-branches-summary');
            const selectAllButton = document.getElementById('select-all-branches');
            const clearExtrasButton = document.getElementById('clear-extra-branches');
            const branchItems = Array.from(document.querySelectorAll('[data-branch-item]'));
            const branchCheckboxes = Array.from(document.querySelectorAll('[data-branch-checkbox]'));

            if (!primaryBranchSelect || !allowedBranchesSearch || !allowedBranchesList || !emptyState || !summaryNode || !selectAllButton || !clearExtrasButton || branchItems.length === 0) {
                return;
            }

            const activePrimaryBranchId = () => {
                const value = Number(primaryBranchSelect.value || 0);
                return Number.isFinite(value) ? value : 0;
            };

            const selectedCheckboxes = () => branchCheckboxes.filter((checkbox) => checkbox.checked);

            const syncPrimaryBranchState = () => {
                const primaryId = activePrimaryBranchId();

                branchItems.forEach((item) => {
                    const branchId = Number(item.dataset.branchId || 0);
                    const checkbox = item.querySelector('[data-branch-checkbox]');
                    const meta = item.querySelector('.branch-access-item-meta');

                    if (!checkbox || !meta) {
                        return;
                    }

                    meta.querySelector('.branch-access-badge--primary')?.remove();
                    item.classList.remove('is-primary');

                    if (primaryId > 0 && branchId === primaryId) {
                        checkbox.checked = true;
                        checkbox.disabled = true;
                        item.classList.add('is-primary');

                        const badge = document.createElement('span');
                        badge.className = 'branch-access-badge branch-access-badge--primary';
                        badge.textContent = 'Primary branch';
                        meta.prepend(badge);
                    } else {
                        checkbox.disabled = false;
                    }
                });
            };

            const syncSelectionState = () => {
                const primaryId = activePrimaryBranchId();
                const selected = selectedCheckboxes();
                const total = branchCheckboxes.length;
                const selectedCount = selected.length;
                const extraCount = selected.filter((checkbox) => Number(checkbox.value) !== primaryId).length;

                branchItems.forEach((item) => {
                    const checkbox = item.querySelector('[data-branch-checkbox]');

                    if (!checkbox) {
                        return;
                    }

                    item.classList.toggle('is-selected', checkbox.checked);
                });

                if (selectedCount === 0) {
                    summaryNode.textContent = 'Choose extra branches';
                    return;
                }

                if (selectedCount === total) {
                    summaryNode.textContent = 'All branches selected';
                    return;
                }

                if (primaryId > 0 && extraCount === 0) {
                    summaryNode.textContent = 'Primary branch only';
                    return;
                }

                summaryNode.textContent = primaryId > 0
                    ? `Primary branch + ${extraCount} more`
                    : `${selectedCount} branches selected`;
            };

            const filterBranches = () => {
                const query = allowedBranchesSearch.value.trim().toLowerCase();
                let visibleCount = 0;

                branchItems.forEach((item) => {
                    const branchName = item.dataset.branchName || '';
                    const matches = query === '' || branchName.includes(query);

                    item.classList.toggle('d-none', !matches);
                    if (matches) {
                        visibleCount++;
                    }
                });

                emptyState.classList.toggle('d-none', visibleCount > 0);
            };

            selectAllButton.addEventListener('click', function () {
                branchCheckboxes.forEach((checkbox) => {
                    checkbox.checked = true;
                });

                syncPrimaryBranchState();
                syncSelectionState();
            });

            clearExtrasButton.addEventListener('click', function () {
                const primaryId = activePrimaryBranchId();

                branchCheckboxes.forEach((checkbox) => {
                    checkbox.checked = Number(checkbox.value) === primaryId;
                });

                syncPrimaryBranchState();
                syncSelectionState();
            });

            branchCheckboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', syncSelectionState);
            });

            primaryBranchSelect.addEventListener('change', function () {
                syncPrimaryBranchState();
                syncSelectionState();
            });

            allowedBranchesSearch.addEventListener('input', filterBranches);

            syncPrimaryBranchState();
            syncSelectionState();
            filterBranches();
        })();
    </script>
@endpush
