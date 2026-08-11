<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use App\Models\Designation;
use App\Models\User;
use App\Services\BranchFinanceSummaryService;
use App\Services\BranchService;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BranchController extends Controller
{
    public function __construct(
        protected BranchFinanceSummaryService $branchFinanceSummaryService,
        protected BranchService $branchService,
    ) {
        $this->middleware('module:branches');
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

    public function archived(Request $request): View
    {
        $branches = TableListing::paginate(
            TableListing::applySearch(
                Branch::onlyTrashed()
                    ->with(['branchUser'])
                    ->withCount('excos')
                    ->latest('deleted_at'),
                $request->string('search')->toString(),
                ['name', 'prefix', 'id_prefix', 'contact_email', 'contact_phone', 'address', 'registration_number']
            ),
            $request
        );

        return view('branches.archived', compact('branches'));
    }

    public function create(): View
    {
        return view('branches.create', [
            'designations' => Designation::query()
                ->where('status', 1)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'branchMembers' => collect(),
        ]);
    }

    public function show(Branch $branch): View
    {
        return view('branches.show', [
            'branch' => $branch->load(['branchUser.savingsAccounts', 'excos', 'formerExcos']),
            'financeSummary' => $this->branchFinanceSummaryService->buildMonthlySummary($branch),
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
            'branchMembers' => User::query()
                ->with('detail')
                ->where('branch_id', (string) $branch->id)
                ->where('branch_account', false)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->orderBy('last_name')
                ->get(),
        ]);
    }

    public function store(StoreBranchRequest $request): RedirectResponse
    {
        $branch = $this->branchService->create($request->validated(), $request->user());

        return redirect()
            ->route('branches.index')
            ->with('status', "{$branch->name} has been created and added to your accessible branches.");
    }

    public function update(UpdateBranchRequest $request, Branch $branch): RedirectResponse
    {
        $branch = $this->branchService->update($branch, $request->validated());

        return redirect()
            ->route('branches.show', $branch)
            ->with('status', "{$branch->name} has been updated successfully.");
    }

    public function updateBranding(Request $request, Branch $branch, string $type): RedirectResponse
    {
        abort_unless(in_array($type, ['logo', 'signature'], true), 404);

        $request->validate([
            'branding_file' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ], [
            'branding_file.required' => 'Please select an image to upload.',
            'branding_file.image' => 'The selected file must be a valid image.',
            'branding_file.mimes' => 'Please upload a JPEG, PNG, or WebP image.',
            'branding_file.max' => 'The image must not be larger than 5 MB.',
        ]);

        $column = $type === 'logo' ? 'photo' : 'signature';
        $directory = $type === 'logo' ? 'branches/logos' : 'branches/signatures';
        $oldPath = $branch->{$column};
        $newPath = $request->file('branding_file')->store($directory, 'public');

        $branch->update([$column => $newPath]);

        if ($oldPath && $oldPath !== $newPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()
            ->route('branches.show', $branch)
            ->with('status', 'Branch ' . $type . ' uploaded successfully.');
    }

    public function destroyBranding(Branch $branch, string $type): RedirectResponse
    {
        abort_unless(in_array($type, ['logo', 'signature'], true), 404);

        $column = $type === 'logo' ? 'photo' : 'signature';
        $oldPath = $branch->{$column};

        $branch->update([$column => null]);

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()
            ->route('branches.show', $branch)
            ->with('status', 'Branch ' . $type . ' deleted successfully.');
    }

    public function archive(Branch $branch): RedirectResponse
    {
        $branchName = $branch->name;
        $this->branchService->archive($branch);

        return redirect()
            ->route('branches.index')
            ->with('status', "{$branchName} has been archived successfully.");
    }

    public function restore(int $branchId): RedirectResponse
    {
        $branch = Branch::onlyTrashed()->with('branchUser')->findOrFail($branchId);
        $branchName = $branch->name;
        $this->branchService->restore($branch);

        return redirect()
            ->route('branches.index')
            ->with('status', "{$branchName} has been restored successfully.");
    }
}
