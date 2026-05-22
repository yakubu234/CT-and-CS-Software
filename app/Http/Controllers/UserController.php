<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStaffUserRequest;
use App\Http\Requests\UpdateStaffUserRequest;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('module:users');
    }

    public function index(Request $request): View
    {
        $users = TableListing::paginate(
            TableListing::applySearch(
                User::query()
                    ->with(['role', 'branch'])
                    ->where('user_type', '!=', 'customer')
                    ->where('branch_account', false)
                    ->where('society_exco', false)
                    ->where('former_exco', false)
                    ->latest(),
                $request->string('search')->toString(),
                ['name', 'last_name', 'email', 'member_no', 'designation']
            ),
            $request
        );

        return view('users.index', [
            'users' => $users,
        ]);
    }

    public function create(): View
    {
        return view('users.create', [
            'user' => new User(),
            'roles' => Role::query()->adminAccess()->orderBy('name')->get(),
            'branches' => Branch::query()->whereNull('deleted_at')->where('status', 1)->orderBy('name')->get(),
            'assignedBranchIds' => [],
        ]);
    }

    public function store(StoreStaffUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $assignedBranchIds = $this->normalizeAssignedBranches($validated['branch_id'], $validated['assigned_branch_ids'] ?? []);

        $user = User::create([
            'name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'user_type' => 'staff',
            'role_id' => (int) $validated['role_id'],
            'branch_id' => (int) $validated['branch_id'],
            'status' => (int) $validated['status'],
            'society_role' => null,
            'society_exco' => false,
            'former_exco' => false,
            'user_level' => null,
            'branch_account' => false,
            'is_verified' => true,
            'designation' => $validated['designation'] ?? null,
            'assigned_branch' => json_encode($assignedBranchIds),
            'last_branch_id' => (int) $validated['branch_id'],
        ]);

        return redirect()
            ->route('users.index')
            ->with('status', "{$user->name} has been created successfully.");
    }

    public function edit(User $user): View
    {
        abort_unless($user->user_type !== 'customer' && ! $user->branch_account && ! $user->society_exco && ! $user->former_exco, 404);

        return view('users.edit', [
            'user' => $user->load(['role', 'branch']),
            'roles' => Role::query()->adminAccess()->orderBy('name')->get(),
            'branches' => Branch::query()->whereNull('deleted_at')->where('status', 1)->orderBy('name')->get(),
            'assignedBranchIds' => $this->assignedBranchIds($user),
        ]);
    }

    public function update(UpdateStaffUserRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->user_type !== 'customer' && ! $user->branch_account && ! $user->society_exco && ! $user->former_exco, 404);

        $validated = $request->validated();
        $assignedBranchIds = $this->normalizeAssignedBranches($validated['branch_id'], $validated['assigned_branch_ids'] ?? []);

        $payload = [
            'name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? null,
            'email' => $validated['email'],
            'role_id' => (int) $validated['role_id'],
            'branch_id' => (int) $validated['branch_id'],
            'status' => (int) $validated['status'],
            'designation' => $validated['designation'] ?? null,
            'assigned_branch' => json_encode($assignedBranchIds),
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        if ($user->last_branch_id && ! in_array((int) $user->last_branch_id, $assignedBranchIds, true)) {
            $payload['last_branch_id'] = (int) $validated['branch_id'];
        }

        $user->update($payload);

        return redirect()
            ->route('users.index')
            ->with('status', "{$user->name} has been updated successfully.");
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->user_type !== 'customer' && ! $user->branch_account && ! $user->society_exco && ! $user->former_exco, 404);

        if ($user->isSuperAdmin()) {
            return back()->withErrors(['user' => 'Super admin accounts cannot be deleted here.']);
        }

        $name = $user->name;
        $user->update(['status' => 0]);
        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('status', "{$name} has been archived successfully.");
    }

    protected function normalizeAssignedBranches(int|string $primaryBranchId, array $assignedBranchIds): array
    {
        return collect($assignedBranchIds)
            ->push((int) $primaryBranchId)
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();
    }

    protected function assignedBranchIds(User $user): array
    {
        $raw = $user->assigned_branch;

        if (! $raw) {
            return $user->branch_id ? [(int) $user->branch_id] : [];
        }

        $decoded = json_decode((string) $raw, true);
        $values = is_array($decoded) ? $decoded : [$raw];

        return collect($values)
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();
    }
}
