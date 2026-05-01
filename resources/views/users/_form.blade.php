@csrf

<div class="row">
    <div class="col-lg-8">
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
                            <input type="text" name="designation" id="designation" class="form-control @error('designation') is-invalid @enderror" value="{{ old('designation', $user->designation) }}" placeholder="Optional role title">
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
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title mb-0">Access Setup</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="role_id">Role</label>
                    <select name="role_id" id="role_id" class="form-control select2 @error('role_id') is-invalid @enderror" required>
                        <option value="">Select role</option>
                        @foreach ($roles as $roleOption)
                            <option value="{{ $roleOption->id }}" @selected((string) old('role_id', $user->role_id) === (string) $roleOption->id)>{{ $roleOption->name }}</option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="branch_id">Primary Branch</label>
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

                <div class="form-group">
                    <label for="assigned_branch_ids">Allowed Branches</label>
                    <select name="assigned_branch_ids[]" id="assigned_branch_ids" class="form-control select2 @error('assigned_branch_ids') is-invalid @enderror" multiple>
                        @foreach ($branches as $branchOption)
                            <option value="{{ $branchOption->id }}" @selected(in_array((int) $branchOption->id, collect(old('assigned_branch_ids', $assignedBranchIds))->map(fn($value) => (int) $value)->all(), true))>{{ $branchOption->name }}</option>
                        @endforeach
                    </select>
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
                <a href="{{ route('users.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i>
                    Save User
                </button>
            </div>
        </div>
    </div>
</div>
