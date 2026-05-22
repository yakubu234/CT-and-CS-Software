<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDesignationRequest;
use App\Http\Requests\UpdateDesignationRequest;
use App\Models\Designation;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DesignationController extends Controller
{
    public function __construct()
    {
        $this->middleware('module:roles');
    }

    public function index(Request $request): View
    {
        $designations = TableListing::paginate(
            TableListing::applySearch(
                Designation::query()->latest('sort_order')->latest('name'),
                $request->string('search')->toString(),
                ['name', 'slug']
            ),
            $request
        );

        return view('designations.index', [
            'designations' => $designations,
        ]);
    }

    public function create(): View
    {
        return view('designations.create', [
            'designation' => new Designation(),
        ]);
    }

    public function store(StoreDesignationRequest $request): RedirectResponse
    {
        $designation = Designation::create([
            'name' => $request->validated('name'),
            'slug' => $this->uniqueSlug($request->validated('name')),
            'status' => (int) $request->validated('status'),
            'sort_order' => (int) $request->validated('sort_order'),
        ]);

        return redirect()
            ->route('exco-roles.index')
            ->with('status', "{$designation->name} exco role created successfully.");
    }

    public function edit(Designation $designation): View
    {
        return view('designations.edit', [
            'designation' => $designation,
        ]);
    }

    public function update(UpdateDesignationRequest $request, Designation $designation): RedirectResponse
    {
        $designation->update([
            'name' => $request->validated('name'),
            'slug' => $this->uniqueSlug($request->validated('name'), $designation->id),
            'status' => (int) $request->validated('status'),
            'sort_order' => (int) $request->validated('sort_order'),
        ]);

        return redirect()
            ->route('exco-roles.index')
            ->with('status', "{$designation->name} exco role updated successfully.");
    }

    public function destroy(Designation $designation): RedirectResponse
    {
        if (\App\Models\User::query()
            ->where(function ($query) use ($designation): void {
                $query->where('designation', $designation->name)
                    ->orWhere('former_designation', $designation->name)
                    ->orWhere('society_role', $designation->name);
            })
            ->exists()
        ) {
            return back()->withErrors(['designation' => 'This exco role is already in use and cannot be deleted yet.']);
        }

        $name = $designation->name;
        $designation->delete();

        return redirect()
            ->route('exco-roles.index')
            ->with('status', "{$name} exco role deleted successfully.");
    }

    protected function uniqueSlug(string $name, ?int $ignoreDesignationId = null): string
    {
        $base = Str::slug($name) ?: 'designation';
        $slug = $base;
        $counter = 2;

        while (
            Designation::query()
                ->when($ignoreDesignationId, fn ($query) => $query->whereKeyNot($ignoreDesignationId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
