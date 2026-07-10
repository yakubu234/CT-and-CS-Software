<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AssetCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('module:assets');
    }

    public function index(Request $request): View
    {
        $categories = TableListing::paginate(
            TableListing::applySearch(
                AssetCategory::query()->latest('id'),
                $request->string('search')->toString(),
                ['name', 'slug', 'description']
            ),
            $request,
            10
        );

        return view('assets.categories.index', [
            'categories' => $categories,
        ]);
    }

    public function create(): View
    {
        return view('assets.categories.create', [
            'category' => new AssetCategory(['status' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        AssetCategory::create(array_merge($data, [
            'slug' => Str::slug($data['name'], '_'),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]));

        return redirect()
            ->route('asset-categories.index')
            ->with('status', 'Asset category created successfully.');
    }

    public function edit(AssetCategory $assetCategory): View
    {
        return view('assets.categories.edit', [
            'category' => $assetCategory,
        ]);
    }

    public function update(Request $request, AssetCategory $assetCategory): RedirectResponse
    {
        $data = $this->validated($request, $assetCategory);

        $assetCategory->update(array_merge($data, [
            'slug' => Str::slug($data['name'], '_'),
            'updated_by' => $request->user()->id,
        ]));

        return redirect()
            ->route('asset-categories.index')
            ->with('status', 'Asset category updated successfully.');
    }

    public function destroy(AssetCategory $assetCategory): RedirectResponse
    {
        $assetCategory->delete();

        return redirect()
            ->route('asset-categories.index')
            ->with('status', 'Asset category deleted successfully.');
    }

    protected function validated(Request $request, ?AssetCategory $category = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:191',
                Rule::unique('asset_categories', 'name')->ignore($category?->id)->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'boolean'],
        ]);
    }
}
