<?php

namespace App\Http\Controllers;

use App\Models\CustomerSupportRequest;
use App\Services\ActiveBranchService;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerSupportRequestController extends Controller
{
    public function __construct(
        protected ActiveBranchService $activeBranchService,
    ) {
        $this->middleware('module:members');
    }

    public function index(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before viewing support requests.']);
        }

        $requests = TableListing::paginate(
            CustomerSupportRequest::query()
                ->with(['user.detail', 'branch'])
                ->where('branch_id', $branch->id)
                ->when($request->filled('status'), function (Builder $query) use ($request): void {
                    $query->where('status', $request->input('status'));
                })
                ->when($request->filled('category'), function (Builder $query) use ($request): void {
                    $query->where('category', $request->input('category'));
                })
                ->when($request->filled('search'), function (Builder $query) use ($request): void {
                    $search = $request->string('search')->toString();

                    $query->where(function (Builder $builder) use ($search): void {
                        $builder->where('subject', 'like', '%' . $search . '%')
                            ->orWhere('message', 'like', '%' . $search . '%')
                            ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                                $userQuery->where('name', 'like', '%' . $search . '%')
                                    ->orWhere('last_name', 'like', '%' . $search . '%')
                                    ->orWhere('email', 'like', '%' . $search . '%')
                                    ->orWhere('member_no', 'like', '%' . $search . '%')
                                    ->orWhereHas('detail', function (Builder $detailQuery) use ($search): void {
                                        $detailQuery->where('member_no', 'like', '%' . $search . '%');
                                    });
                            });
                    });
                })
                ->latest('id'),
            $request
        );

        return view('support-requests.index', [
            'branch' => $branch,
            'requests' => $requests,
            'filters' => $request->only(['status', 'category', 'search']),
            'statusOptions' => $this->statusOptions(),
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function show(Request $request, CustomerSupportRequest $supportRequest): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before viewing support requests.']);
        }

        abort_unless((int) $supportRequest->branch_id === (int) $branch->id, 404);

        $supportRequest->load(['user.detail', 'branch']);

        return view('support-requests.show', [
            'branch' => $branch,
            'supportRequest' => $supportRequest,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, CustomerSupportRequest $supportRequest): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless($branch && (int) $supportRequest->branch_id === (int) $branch->id, 404);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:open,in_progress,resolved,closed'],
            'admin_response' => ['nullable', 'string', 'max:5000'],
        ], attributes: [
            'admin_response' => 'response',
        ]);

        $supportRequest->fill($validated);
        $supportRequest->resolved_at = in_array($validated['status'], ['resolved', 'closed'], true)
            ? ($supportRequest->resolved_at ?: now())
            : null;
        $supportRequest->save();

        return redirect()
            ->route('support-requests.show', $supportRequest)
            ->with('status', 'Support request updated successfully.');
    }

    protected function statusOptions(): array
    {
        return [
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
        ];
    }

    protected function categoryOptions(): array
    {
        return [
            'general' => 'General',
            'account' => 'Account',
            'loan' => 'Loan',
            'repayment' => 'Repayment',
            'profile' => 'Profile',
        ];
    }
}
