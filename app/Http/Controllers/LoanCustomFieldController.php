<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomFieldRequest;
use App\Http\Requests\UpdateCustomFieldRequest;
use App\Models\CustomField;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LoanCustomFieldController extends Controller
{
    public function index(Request $request): View
    {
        $fields = TableListing::paginate(
            TableListing::applySearch(
                CustomField::query()
                    ->where('table', 'loans')
                    ->latest(),
                $request->string('search')->toString(),
                ['field_name', 'field_type', 'default_value', 'options']
            ),
            $request
        );

        return view('loans.custom-fields.index', [
            'fields' => $fields,
        ]);
    }

    public function create(): View
    {
        return view('loans.custom-fields.create');
    }

    public function store(StoreCustomFieldRequest $request): RedirectResponse
    {
        CustomField::create([
            ...$request->validated(),
            'field_width' => 'col-md-6',
            'table' => 'loans',
            'allow_for_signup' => 0,
            'allow_to_list_view' => 0,
            'order' => (CustomField::query()->where('table', 'loans')->max('order') ?? 0) + 1,
        ]);

        return redirect()
            ->route('loans.custom-fields.index')
            ->with('status', 'Loan custom field created successfully.');
    }

    public function edit(CustomField $customField): View
    {
        abort_unless($customField->table === 'loans', 404);

        return view('loans.custom-fields.edit', [
            'customField' => $customField,
        ]);
    }

    public function update(UpdateCustomFieldRequest $request, CustomField $customField): RedirectResponse
    {
        abort_unless($customField->table === 'loans', 404);

        $customField->update($request->validated());

        return redirect()
            ->route('loans.custom-fields.index')
            ->with('status', 'Loan custom field updated successfully.');
    }

    public function destroy(CustomField $customField): RedirectResponse
    {
        abort_unless($customField->table === 'loans', 404);

        $customField->delete();

        return redirect()
            ->route('loans.custom-fields.index')
            ->with('status', 'Loan custom field deleted successfully.');
    }
}
