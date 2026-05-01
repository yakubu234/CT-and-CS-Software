@csrf

<div class="row">
    <div class="col-lg-4">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title mb-0">Role Details</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="name">Role Name</label>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $role->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group mb-0">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" rows="5" class="form-control @error('description') is-invalid @enderror" placeholder="Briefly describe who should use this role">{{ old('description', $role->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card card-outline card-primary">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Permissions</h3>
                <span class="text-muted small">Choose what this role can view and manage.</span>
            </div>
            <div class="card-body">
                @php
                    $selectedPermissions = old('permissions', $role->permissions ?? []);
                @endphp

                @foreach ($permissionGroups as $groupName => $permissions)
                    <div class="mb-4">
                        <h4 class="h6 font-weight-bold text-uppercase text-muted">{{ $groupName }}</h4>
                        <div class="row">
                            @foreach ($permissions as $permission => $label)
                                <div class="col-md-6">
                                    <div class="custom-control custom-checkbox mb-2">
                                        <input
                                            type="checkbox"
                                            class="custom-control-input"
                                            id="{{ $permission }}"
                                            name="permissions[]"
                                            value="{{ $permission }}"
                                            @checked(in_array($permission, $selectedPermissions, true))
                                        >
                                        <label class="custom-control-label" for="{{ $permission }}">{{ $label }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                @error('permissions')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>
            <div class="card-footer d-flex justify-content-between flex-wrap">
                <a href="{{ route('user-roles.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i>
                    Save Role
                </button>
            </div>
        </div>
    </div>
</div>
