@extends('layouts.admin')

@section('title', 'Admin Roles')
@section('page_title', 'Admin Roles')

@push('styles')
    <style>
        .admin-role-list {
            display: grid;
            gap: 1rem;
        }

        .admin-role-card {
            border: 1px solid #dbe5f0;
            border-radius: 1rem;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }

        .admin-role-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.1rem;
        }

        .admin-role-name {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 800;
            color: #0f172a;
        }

        .admin-role-description {
            margin: 0.35rem 0 0;
            color: #475569;
            max-width: 44rem;
        }

        .admin-role-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .admin-role-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .admin-role-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .admin-role-toggle {
            border: 0;
            background: transparent;
            color: #1d4ed8;
            font-weight: 700;
            padding: 0.35rem 0.25rem;
        }

        .admin-role-toggle:focus {
            outline: none;
        }

        .admin-role-body {
            border-top: 1px solid #e5edf5;
            padding: 1rem 1.1rem 1.1rem;
            background: #fff;
        }

        .admin-role-permission-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 0.9rem;
        }

        .admin-role-permission-group {
            border: 1px solid #e5edf5;
            border-radius: 0.9rem;
            padding: 0.85rem 0.9rem;
            background: #fbfdff;
        }

        .admin-role-permission-group-title {
            margin: 0 0 0.6rem;
            font-size: 0.82rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #475569;
        }

        .admin-role-permission-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }

        .admin-role-permission-tag {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.6rem;
            border: 1px solid #dbe5f0;
            border-radius: 0.75rem;
            background: #fff;
            color: #1e293b;
            font-size: 0.84rem;
            font-weight: 600;
            line-height: 1.3;
        }

        @media (max-width: 767.98px) {
            .admin-role-card-header {
                flex-direction: column;
                align-items: stretch;
            }

            .admin-role-actions {
                justify-content: flex-start;
            }
        }
    </style>
@endpush

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h3 class="card-title mb-0">Admin Roles</h3>
                <small class="text-muted d-block mt-1">Create permission bundles for administrator accounts only.</small>
            </div>
            <div class="d-flex flex-wrap mt-2 mt-md-0">
                <x-browser-back-button :fallback="route('dashboard')" class="btn btn-light mr-2 mb-2 mb-md-0" />
                <a href="{{ route('user-roles.create') }}" class="btn btn-primary mb-2 mb-md-0">
                    <i class="fas fa-plus mr-1"></i>
                    New Role
                </a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('user-roles.index') }}" class="mb-3">
                <div class="input-group">
                    <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search roles...">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-outline-secondary">Search</button>
                    </div>
                </div>
            </form>

            @php
                $permissionGroupLookup = collect(\App\Support\PermissionRegistry::definitions())
                    ->flatMap(function (array $permissions, string $groupName): array {
                        return collect($permissions)->mapWithKeys(fn ($label, $permission) => [
                            $permission => ['group' => $groupName, 'label' => $label],
                        ])->all();
                    });
            @endphp

            <div class="admin-role-list" id="admin-role-accordion">
                @forelse ($roles as $role)
                    @php
                        $permissions = collect($role->permissions ?? []);
                        $groupedPermissions = $permissions
                            ->map(function ($permission) use ($permissionGroupLookup): array {
                                $meta = $permissionGroupLookup->get($permission, [
                                    'group' => 'Other',
                                    'label' => $permission,
                                ]);

                                return [
                                    'group' => $meta['group'],
                                    'label' => $meta['label'],
                                ];
                            })
                            ->groupBy('group');
                    @endphp

                    <div class="admin-role-card">
                        <div class="admin-role-card-header">
                            <div>
                                <h4 class="admin-role-name">{{ $role->name }}</h4>
                                <p class="admin-role-description">{{ $role->description ?: 'No description added for this role yet.' }}</p>
                                <div class="admin-role-meta">
                                    <span class="admin-role-pill">
                                        <i class="fas fa-shield-alt"></i>
                                        {{ $permissions->count() }} permission{{ $permissions->count() === 1 ? '' : 's' }}
                                    </span>
                                    <span class="badge badge-{{ $role->is_system ? 'info' : 'secondary' }} align-self-center">
                                        {{ $role->is_system ? 'System' : 'Custom' }}
                                    </span>
                                </div>
                            </div>

                            <div class="admin-role-actions">
                                <button
                                    class="admin-role-toggle"
                                    type="button"
                                    data-toggle="collapse"
                                    data-target="#role-details-{{ $role->id }}"
                                    aria-expanded="false"
                                    aria-controls="role-details-{{ $role->id }}"
                                >
                                    <i class="fas fa-chevron-down mr-1"></i>
                                    View Permissions
                                </button>
                                <a href="{{ route('user-roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                @if (! $role->is_system)
                                    <form action="{{ route('user-roles.destroy', $role) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this role?')">Delete</button>
                                    </form>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-danger" disabled title="System roles cannot be deleted">Delete</button>
                                @endif
                            </div>
                        </div>

                        <div id="role-details-{{ $role->id }}" class="collapse" data-parent="#admin-role-accordion">
                            <div class="admin-role-body">
                                @if ($groupedPermissions->isNotEmpty())
                                    <div class="admin-role-permission-grid">
                                        @foreach ($groupedPermissions as $groupName => $groupPermissions)
                                            <section class="admin-role-permission-group">
                                                <h5 class="admin-role-permission-group-title">{{ $groupName }}</h5>
                                                <div class="admin-role-permission-tags">
                                                    @foreach ($groupPermissions as $permission)
                                                        <span class="admin-role-permission-tag">{{ $permission['label'] }}</span>
                                                    @endforeach
                                                </div>
                                            </section>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-muted">No permissions are attached to this role.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">No roles found.</div>
                @endforelse
            </div>

            <div class="mt-3">
                {{ $roles->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
