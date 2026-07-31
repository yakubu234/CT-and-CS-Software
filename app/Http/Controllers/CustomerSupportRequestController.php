<?php

namespace App\Http\Controllers;

use App\Models\CustomerSupportRequest;
use App\Models\User;
use App\Services\ActiveBranchService;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

        $admin = $request->user();
        $requests = TableListing::paginate(
            CustomerSupportRequest::query()
                ->with(['user.detail', 'branch', 'creator', 'recipient'])
                ->withCount(['messages as unread_messages_count' => function (Builder $query) use ($admin): void {
                    $query->whereNull('read_at')->where('sender_id', '!=', $admin->id);
                }])
                ->where(function (Builder $query) use ($branch, $admin): void {
                    $query->where(function (Builder $customerQuery) use ($branch): void {
                        $customerQuery->where('conversation_type', 'customer_admin')
                            ->where('branch_id', $branch->id);
                    })->orWhere(function (Builder $staffQuery) use ($admin): void {
                        $staffQuery->where('conversation_type', 'admin_admin')
                            ->where(function (Builder $participantQuery) use ($admin): void {
                                $participantQuery->where('created_by', $admin->id)
                                    ->orWhere('recipient_user_id', $admin->id);
                            });
                    });
                })
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
                ->latest('last_message_at')
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

    public function create(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before creating a complaint.']);
        }

        return view('support-requests.create', [
            'branch' => $branch,
            'recipients' => User::query()
                ->with(['branch', 'role'])
                ->where('user_type', '!=', 'customer')
                ->where('branch_account', false)
                ->where('status', 1)
                ->where('id', '!=', $request->user()->id)
                ->orderBy('name')
                ->get(),
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());
        abort_unless($branch, 422, 'Please select an active branch before creating a complaint.');

        $validated = $request->validate([
            'recipient_user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('user_type', '!=', 'customer')
                    ->where('branch_account', false)
                    ->where('status', 1)
                    ->whereNull('deleted_at')),
            ],
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        abort_if((int) $validated['recipient_user_id'] === (int) $request->user()->id, 422, 'You cannot send a complaint to yourself.');

        $supportRequest = DB::transaction(function () use ($validated, $branch, $request): CustomerSupportRequest {
            $supportRequest = CustomerSupportRequest::create([
                'user_id' => $request->user()->id,
                'branch_id' => $branch->id,
                'created_by' => $request->user()->id,
                'recipient_user_id' => $validated['recipient_user_id'],
                'conversation_type' => 'admin_admin',
                'subject' => $validated['subject'],
                'category' => $validated['category'],
                'priority' => $validated['priority'],
                'message' => $validated['message'],
                'status' => 'open',
                'last_message_at' => now(),
            ]);

            $supportRequest->messages()->create([
                'sender_id' => $request->user()->id,
                'message' => $validated['message'],
            ]);

            return $supportRequest;
        });

        return redirect()->route('support-requests.show', $supportRequest)
            ->with('status', 'Complaint conversation created successfully.');
    }

    public function show(Request $request, CustomerSupportRequest $supportRequest): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before viewing support requests.']);
        }

        $this->authorizeConversation($request, $supportRequest, (int) $branch->id);

        $supportRequest->load(['user.detail', 'branch', 'creator', 'recipient', 'messages.sender']);
        $supportRequest->messages()
            ->whereNull('read_at')
            ->where(function (Builder $query) use ($request): void {
                $query->whereNull('sender_id')->orWhere('sender_id', '!=', $request->user()->id);
            })
            ->update(['read_at' => now()]);

        return view('support-requests.show', [
            'branch' => $branch,
            'supportRequest' => $supportRequest,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, CustomerSupportRequest $supportRequest): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless($branch, 404);
        $this->authorizeConversation($request, $supportRequest, (int) $branch->id);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:open,in_progress,resolved,closed'],
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

    public function reply(Request $request, CustomerSupportRequest $supportRequest): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());
        abort_unless($branch, 404);
        $this->authorizeConversation($request, $supportRequest, (int) $branch->id);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($supportRequest, $request, $validated): void {
            $supportRequest->messages()->create([
                'sender_id' => $request->user()->id,
                'message' => $validated['message'],
            ]);
            $supportRequest->update([
                'last_message_at' => now(),
                'status' => in_array($supportRequest->status, ['resolved', 'closed'], true)
                    ? 'in_progress'
                    : $supportRequest->status,
            ]);
        });

        return redirect()->route('support-requests.show', $supportRequest)
            ->with('status', 'Reply sent successfully.');
    }

    protected function authorizeConversation(Request $request, CustomerSupportRequest $supportRequest, int $branchId): void
    {
        $allowed = $supportRequest->conversation_type === 'customer_admin'
            ? (int) $supportRequest->branch_id === $branchId
            : in_array((int) $request->user()->id, [
                (int) $supportRequest->created_by,
                (int) $supportRequest->recipient_user_id,
            ], true);

        abort_unless($allowed, 404);
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
