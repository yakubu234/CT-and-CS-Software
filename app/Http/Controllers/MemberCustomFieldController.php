<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomFieldRequest;
use App\Http\Requests\UpdateCustomFieldRequest;
use App\Models\CustomField;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MemberCustomFieldController extends Controller
{
    public function index(Request $request): View
    {
        $fields = TableListing::paginate(
            TableListing::applySearch(
                CustomField::query()
                    ->forUsers()
                    ->latest(),
                $request->string('search')->toString(),
                ['field_name', 'field_type', 'default_value', 'options']
            ),
            $request
        );

        return view('members.custom-fields.index', [
            'fields' => $fields,
        ]);
    }

    public function create(): View
    {
        return view('members.custom-fields.create');
    }

    public function store(StoreCustomFieldRequest $request): RedirectResponse
    {
        CustomField::create([
            ...$request->validated(),
            'field_width' => 'col-md-6',
            'table' => 'users',
            'allow_for_signup' => 0,
            'allow_to_list_view' => 0,
            'order' => (CustomField::query()->max('order') ?? 0) + 1,
        ]);

        return redirect()
            ->route('members.custom-fields.index')
            ->with('status', 'Member custom field created successfully.');
    }

    public function edit(CustomField $customField): View
    {
        return view('members.custom-fields.edit', [
            'customField' => $customField,
        ]);
    }

    public function update(UpdateCustomFieldRequest $request, CustomField $customField): RedirectResponse
    {
        $customField->update($request->validated());

        return redirect()
            ->route('members.custom-fields.index')
            ->with('status', 'Member custom field updated successfully.');
    }

    public function destroy(CustomField $customField): RedirectResponse
    {
        $customField->delete();

        return redirect()
            ->route('members.custom-fields.index')
            ->with('status', 'Member custom field deleted successfully.');
    }
}
