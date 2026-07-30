<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Services\ActiveBranchService;
use App\Services\AssetAccountingService;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class AssetController extends Controller
{
    public function __construct(
        protected ActiveBranchService $activeBranchService,
        protected AssetAccountingService $assetAccountingService,
    ) {
        $this->middleware('module:assets');
    }

    public function index(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before viewing assets.']);
        }

        $query = TableListing::applySearch(
            Asset::query()->with(['branch'])
                ->where('branch_id', $branch->id)
                ->latest('id'),
            $request->string('search')->toString(),
            ['name', 'category', 'supplier', 'status', 'remarks']
        );

        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $summaryQuery = clone $query;

        return view('assets.index', [
            'assets' => TableListing::paginate($query, $request, 10),
            'summary' => $this->summary($summaryQuery),
            'categoryOptions' => $this->categoryOptions(),
            'statusOptions' => $this->statusOptions(),
            'branch' => $branch,
            'filters' => $request->only(['search', 'category', 'status']),
        ]);
    }

    public function create(Request $request): View
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());
        abort_unless($branch, 404);

        return view('assets.create', [
            'asset' => new Asset([
                'branch_id' => $branch->id,
                'status' => Asset::STATUS_ACTIVE,
            ]),
            'branch' => $branch,
            'categoryOptions' => $this->categoryOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before recording an asset.']);
        }

        try {
            $asset = $this->assetAccountingService->create(
                $branch,
                $request->user(),
                $this->validated($request)
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['asset' => $exception->getMessage()]);
        }

        return redirect()
            ->route('assets.show', $asset)
            ->with('status', 'Asset recorded successfully.');
    }

    public function show(Request $request, Asset $asset): View
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());
        $this->abortUnlessActiveBranchAsset($branch?->id, $asset);
        $asset->load(['branch', 'creator', 'updater', 'purchaseTransaction']);

        return view('assets.show', [
            'asset' => $asset,
            'categoryOptions' => $this->categoryOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function edit(Asset $asset, Request $request): View
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());
        $this->abortUnlessActiveBranchAsset($branch?->id, $asset);

        return view('assets.edit', [
            'asset' => $asset,
            'branch' => $branch,
            'categoryOptions' => $this->categoryOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, Asset $asset): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());
        $this->abortUnlessActiveBranchAsset($branch?->id, $asset);

        try {
            $asset = $this->assetAccountingService->update(
                $asset,
                $branch,
                $request->user(),
                $this->validated($request)
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['asset' => $exception->getMessage()]);
        }

        return redirect()
            ->route('assets.show', $asset)
            ->with('status', 'Asset updated successfully.');
    }

    public function destroy(Request $request, Asset $asset): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());
        $this->abortUnlessActiveBranchAsset($branch?->id, $asset);
        $this->assetAccountingService->delete($asset, $branch, $request->user());

        return redirect()
            ->route('assets.index')
            ->with('status', 'Asset deleted successfully.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'category' => ['required', Rule::in(array_keys($this->categoryOptions(true)))],
            'purchase_date' => ['nullable', 'date'],
            'purchase_cost' => ['required', 'numeric', 'min:0'],
            'supplier' => ['nullable', 'string', 'max:191'],
            'status' => ['required', Rule::in(array_keys($this->statusOptions()))],
            'depreciation_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'depreciation_note' => ['nullable', 'string'],
            'disposed_at' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
        ]);
    }

    protected function summary(Builder $query): array
    {
        $records = $query->get();

        return [
            'total_assets' => $records->count(),
            'total_cost' => round((float) $records->sum('purchase_cost'), 2),
            'active_assets' => $records->where('status', Asset::STATUS_ACTIVE)->count(),
            'disposed_assets' => $records->where('status', Asset::STATUS_DISPOSED)->count(),
        ];
    }

    protected function categoryOptions(bool $activeOnly = false): array
    {
        return AssetCategory::query()
            ->when($activeOnly, fn (Builder $query) => $query->where('status', true))
            ->orderBy('name')
            ->pluck('name', 'slug')
            ->all();
    }

    protected function statusOptions(): array
    {
        return [
            Asset::STATUS_ACTIVE => 'Active',
            Asset::STATUS_UNDER_REPAIR => 'Under Repair',
            Asset::STATUS_DISPOSED => 'Disposed',
        ];
    }

    protected function abortUnlessActiveBranchAsset(?int $branchId, Asset $asset): void
    {
        abort_unless(
            $branchId !== null
            && (int) $asset->branch_id === $branchId
            && $asset->deleted_at === null,
            404
        );
    }
}
