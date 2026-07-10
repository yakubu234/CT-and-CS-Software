<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Services\ActiveBranchService;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetController extends Controller
{
    public function __construct(
        protected ActiveBranchService $activeBranchService,
    ) {
        $this->middleware('module:assets');
    }

    public function index(Request $request): View
    {
        $query = TableListing::applySearch(
            Asset::query()->with(['branch'])->latest('id'),
            $request->string('search')->toString(),
            ['name', 'category', 'supplier', 'status', 'remarks']
        );

        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        }

        $summaryQuery = clone $query;

        return view('assets.index', [
            'assets' => TableListing::paginate($query, $request, 10),
            'summary' => $this->summary($summaryQuery),
            'categoryOptions' => $this->categoryOptions(),
            'statusOptions' => $this->statusOptions(),
            'branches' => $this->activeBranchService->availableBranches($request->user()),
            'filters' => $request->only(['search', 'category', 'status', 'branch_id']),
        ]);
    }

    public function create(Request $request): View
    {
        return view('assets.create', [
            'asset' => new Asset([
                'branch_id' => $this->activeBranchService->ensureActiveBranch($request->user())?->id,
                'status' => Asset::STATUS_ACTIVE,
            ]),
            'branches' => $this->activeBranchService->availableBranches($request->user()),
            'categoryOptions' => $this->categoryOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $asset = Asset::create(array_merge($this->validated($request), [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]));

        return redirect()
            ->route('assets.show', $asset)
            ->with('status', 'Asset recorded successfully.');
    }

    public function show(Asset $asset): View
    {
        $asset->load(['branch', 'creator', 'updater']);

        return view('assets.show', [
            'asset' => $asset,
            'categoryOptions' => $this->categoryOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function edit(Asset $asset, Request $request): View
    {
        return view('assets.edit', [
            'asset' => $asset,
            'branches' => $this->activeBranchService->availableBranches($request->user()),
            'categoryOptions' => $this->categoryOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, Asset $asset): RedirectResponse
    {
        $asset->update(array_merge($this->validated($request), [
            'updated_by' => $request->user()->id,
        ]));

        return redirect()
            ->route('assets.show', $asset)
            ->with('status', 'Asset updated successfully.');
    }

    public function destroy(Request $request, Asset $asset): RedirectResponse
    {
        $asset->update(['updated_by' => $request->user()->id]);
        $asset->delete();

        return redirect()
            ->route('assets.index')
            ->with('status', 'Asset deleted successfully.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'branch_id' => ['nullable', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:191'],
            'category' => ['required', Rule::in(array_keys($this->categoryOptions()))],
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

    protected function categoryOptions(): array
    {
        return [
            'land_buildings' => 'Land and Buildings',
            'office_furniture' => 'Office Furniture',
            'computers_laptops' => 'Computers and Laptops',
            'printers' => 'Printers',
            'vehicles' => 'Vehicles',
            'generators' => 'Generators',
            'office_equipment' => 'Office Equipment',
            'other_fixed_assets' => 'Other Fixed Assets',
        ];
    }

    protected function statusOptions(): array
    {
        return [
            Asset::STATUS_ACTIVE => 'Active',
            Asset::STATUS_UNDER_REPAIR => 'Under Repair',
            Asset::STATUS_DISPOSED => 'Disposed',
        ];
    }
}
