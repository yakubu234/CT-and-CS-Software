<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use App\Support\PermissionRegistry;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('module:roles');
    }

    public function index(Request $request): View
    {
        $roles = TableListing::paginate(
            TableListing::applySearch(Role::query()->adminAccess()->latest(), $request->string('search')->toString(), ['name', 'description', 'slug']),
            $request
        );

        return view('roles.index', [
            'roles' => $roles,
            'permissionLabels' => PermissionRegistry::labels(),
        ]);
    }

    public function create(): View
    {
        return view('roles.create', [
            'role' => new Role(),
            'permissionGroups' => PermissionRegistry::definitions(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create([
            'name' => $request->validated('name'),
            'slug' => $this->uniqueSlug($request->validated('name')),
            'description' => $request->validated('description'),
            'permissions' => array_values($request->validated('permissions')),
            'is_system' => false,
        ]);

        return redirect()
            ->route('user-roles.index')
            ->with('status', "{$role->name} role created successfully.");
    }

    public function edit(Role $role): View
    {
        abort_unless($role->isAdminAccessRole(), 404);

        return view('roles.edit', [
            'role' => $role,
            'permissionGroups' => PermissionRegistry::definitions(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        abort_unless($role->isAdminAccessRole(), 404);

        $role->update([
            'name' => $request->validated('name'),
            'slug' => $role->slug ?: $this->uniqueSlug($request->validated('name'), $role->id),
            'description' => $request->validated('description'),
            'permissions' => array_values($request->validated('permissions')),
        ]);

        return redirect()
            ->route('user-roles.index')
            ->with('status', "{$role->name} role updated successfully.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_unless($role->isAdminAccessRole(), 404);

        if ($role->is_system) {
            return back()->withErrors(['role' => 'System roles cannot be deleted.']);
        }

        if (method_exists($role, 'users') && $role->users()->exists()) {
            return back()->withErrors(['role' => 'This role is assigned to one or more users and cannot be deleted yet.']);
        }

        if (\App\Models\User::query()->where('role_id', $role->id)->exists()) {
            return back()->withErrors(['role' => 'This role is assigned to one or more users and cannot be deleted yet.']);
        }

        $roleName = $role->name;
        $role->delete();

        return redirect()
            ->route('user-roles.index')
            ->with('status', "{$roleName} role deleted successfully.");
    }

    protected function uniqueSlug(string $name, ?int $ignoreRoleId = null): string
    {
        $base = Str::slug($name) ?: 'role';
        $slug = $base;
        $counter = 2;

        while (
            Role::query()
                ->when($ignoreRoleId, fn ($query) => $query->whereKeyNot($ignoreRoleId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
