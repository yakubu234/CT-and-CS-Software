<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use App\Models\Designation;
use App\Services\BranchService;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function __construct(
        protected BranchService $branchService,
    ) {
    }

    public function index(Request $request): View
    {
        $branches = TableListing::paginate(
            TableListing::applySearch(
                Branch::query()
                    ->with(['branchUser', 'excos'])
                    ->whereNull('deleted_at')
                    ->latest(),
                $request->string('search')->toString(),
                [
                    'name',
                    'prefix',
                    'id_prefix',
                    'contact_email',
                    'contact_phone',
                    'address',
                    'registration_number',
                ]
            ),
            $request
        );

        return view('branches.index', [
            'branches' => $branches,
        ]);
    }

    public function create(): View
    {
        return view('branches.create', [
            'designations' => Designation::query()
                ->where('status', 1)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function show(Branch $branch): View
    {
        return view('branches.show', [
            'branch' => $branch->load(['branchUser.savingsAccounts', 'excos', 'formerExcos']),
        ]);
    }

    public function edit(Branch $branch): View
    {
        return view('branches.edit', [
            'branch' => $branch->load(['branchUser', 'excos']),
            'designations' => Designation::query()
                ->where('status', 1)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreBranchRequest $request): RedirectResponse
    {
        $branch = $this->branchService->create($request->validated());

        return redirect()
            ->route('branches.index')
            ->with('status', "{$branch->name} has been created with its branch account and exco records.");
    }

    public function update(UpdateBranchRequest $request, Branch $branch): RedirectResponse
    {
        $branch = $this->branchService->update($branch, $request->validated());

        return redirect()
            ->route('branches.show', $branch)
            ->with('status', "{$branch->name} has been updated successfully.");
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $branchName = $branch->name;
        $this->branchService->delete($branch);

        return redirect()
            ->route('branches.index')
            ->with('status', "{$branchName} has been moved out of the active branch list.");
    }
}
