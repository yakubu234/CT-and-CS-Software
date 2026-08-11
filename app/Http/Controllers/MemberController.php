<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\StoreMemberDocumentRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Models\CustomField;
use App\Models\MemberDocument;
use App\Models\Designation;
use App\Models\User;
use App\Services\ActiveBranchService;
use App\Services\MemberService;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberController extends Controller
{
    public function __construct(
        protected ActiveBranchService $activeBranchService,
        protected MemberService $memberService,
    ) {
        $this->middleware('module:members');
    }

    public function index(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before managing members.']);
        }

        $members = TableListing::paginate(
            TableListing::applySearch(
                User::query()
                    ->with(['detail'])
                    ->where('branch_account', false)
                    ->where('user_type', 'customer')
                    ->whereNull('deleted_at')
                    ->where('branch_id', (string) $branch->id)
                    ->latest(),
                $request->string('search')->toString(),
                ['name', 'last_name', 'email', 'member_no', 'designation']
            ),
            $request
        );

        return view('members.index', [
            'branch' => $branch,
            'members' => $members,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before creating members.']);
        }

        return view('members.create', [
            'branch' => $branch,
            'nextMemberNumber' => $this->memberService->nextMemberNumber($branch),
            'memberNumberPreview' => $this->memberService->memberNumberPreview(null, $branch),
            'designations' => Designation::query()->where('status', 1)->orderBy('sort_order')->orderBy('name')->get(),
            'customFields' => CustomField::query()->forUsers()->active()->orderBy('order')->orderBy('field_name')->get(),
        ]);
    }

    public function archived(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before viewing archived members.']);
        }

        $members = TableListing::paginate(
            TableListing::applySearch(
                User::onlyTrashed()
                    ->with(['detail'])
                    ->withCount('savingsAccounts')
                    ->where('branch_account', false)
                    ->where('user_type', 'customer')
                    ->where('branch_id', (string) $branch->id)
                    ->latest('deleted_at'),
                $request->string('search')->toString(),
                ['name', 'last_name', 'email', 'member_no', 'designation']
            ),
            $request
        );

        return view('members.archived', compact('branch', 'members'));
    }

    public function archivedShow(Request $request, int $memberId): View|RedirectResponse
    {
        $member = User::onlyTrashed()->findOrFail($memberId);

        return $this->showMember($request, $member, true);
    }

    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless($branch, 422, 'Please select a branch before creating a member.');

        $member = $this->memberService->create($request->validated(), $branch);

        return redirect()
            ->route('members.show', $member)
            ->with('status', "{$member->name} has been created successfully.");
    }

    public function show(Request $request, User $member): View|RedirectResponse
    {
        return $this->showMember($request, $member, false);
    }

    protected function showMember(Request $request, User $member, bool $archived): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before viewing members.']);
        }

        abort_unless(
            ! $member->branch_account
            && $member->user_type === 'customer'
            && (string) $member->branch_id === (string) $branch->id,
            404
        );

        $member->load([
            'detail',
            'documents',
            'savingsAccounts.product',
            'loans' => function ($query) use ($branch): void {
                $query->where('branch_id', $branch->id)
                    ->latest('id');
            },
        ]);

        return view('members.show', [
            'member' => $member,
            'archived' => $archived,
        ]);
    }

    public function edit(Request $request, User $member): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before editing members.']);
        }

        abort_unless(
            ! $member->branch_account
            && $member->user_type === 'customer'
            && (string) $member->branch_id === (string) $branch->id,
            404
        );

        $member->load(['detail', 'documents']);

        return view('members.edit', [
            'branch' => $branch,
            'member' => $member,
            'memberNumberPreview' => $this->memberService->memberNumberPreview($member, $branch),
            'customFields' => CustomField::query()->forUsers()->active()->orderBy('order')->orderBy('field_name')->get(),
        ]);
    }

    public function update(UpdateMemberRequest $request, User $member): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless($branch, 422, 'Please select a branch before updating a member.');
        abort_unless(
            ! $member->branch_account
            && $member->user_type === 'customer'
            && (string) $member->branch_id === (string) $branch->id,
            404
        );

        $member = $this->memberService->update($member, $request->validated(), $branch);

        return redirect()
            ->route('members.show', $member)
            ->with('status', "{$member->name} has been updated successfully.");
    }

    public function updatePhoto(Request $request, User $member): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless(
            $branch
            && ! $member->branch_account
            && $member->user_type === 'customer'
            && (string) $member->branch_id === (string) $branch->id,
            404
        );

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ], [
            'photo.required' => 'Please take a photo or choose one from your device.',
            'photo.image' => 'The selected file must be a valid image.',
            'photo.max' => 'The member photo must not be larger than 5 MB.',
        ]);

        $oldPhoto = $member->profile_picture;
        $newPhoto = $request->file('photo')->store('members/pictures', 'public');

        $member->forceFill(['profile_picture' => $newPhoto])->save();

        if ($oldPhoto && $oldPhoto !== $newPhoto) {
            Storage::disk('public')->delete($oldPhoto);
        }

        return redirect()
            ->route('members.show', $member)
            ->with('status', "{$member->name}'s photo has been updated successfully.");
    }

    public function destroyPhoto(Request $request, User $member): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless(
            $branch
            && ! $member->branch_account
            && $member->user_type === 'customer'
            && (string) $member->branch_id === (string) $branch->id,
            404
        );

        $oldPhoto = $member->profile_picture;
        $member->forceFill(['profile_picture' => null])->save();

        if ($oldPhoto) {
            Storage::disk('public')->delete($oldPhoto);
        }

        return redirect()
            ->route('members.show', $member)
            ->with('status', "{$member->name}'s photo has been removed.");
    }

    public function updatePassword(Request $request, User $member): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless(
            $branch
            && ! $member->branch_account
            && $member->user_type === 'customer'
            && (string) $member->branch_id === (string) $branch->id,
            404
        );

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
            'must_change_password' => ['nullable', 'boolean'],
        ], attributes: [
            'password' => 'new password',
            'password_confirmation' => 'confirm password',
            'must_change_password' => 'require password change',
        ]);

        $member->password = $validated['password'];
        $member->must_change_password = $request->boolean('must_change_password');
        $member->save();

        return redirect()
            ->route('members.edit', $member)
            ->with('status', "{$member->name}'s password has been updated successfully.");
    }

    public function archive(Request $request, User $member): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless(
            $branch
            && ! $member->branch_account
            && $member->user_type === 'customer'
            && (string) $member->branch_id === (string) $branch->id,
            404
        );

        $memberName = $member->name;
        $this->memberService->archive($member);

        return redirect()
            ->route('members.index')
            ->with('status', "{$memberName} has been archived successfully.");
    }

    public function restore(Request $request, int $memberId): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());
        $member = User::onlyTrashed()->findOrFail($memberId);

        abort_unless(
            $branch
            && ! $member->branch_account
            && $member->user_type === 'customer'
            && (string) $member->branch_id === (string) $branch->id,
            404
        );

        $memberName = $member->name;
        $this->memberService->restore($member);

        return redirect()
            ->route('members.show', $member)
            ->with('status', "{$memberName} has been restored successfully.");
    }

    public function storeDocument(StoreMemberDocumentRequest $request, User $member): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless(
            $branch
            && ! $member->branch_account
            && $member->user_type === 'customer'
            && (string) $member->branch_id === (string) $branch->id,
            404
        );

        foreach ($request->validated('documents') as $index => $documentData) {
            MemberDocument::create([
                'user_id' => $member->id,
                'name' => $documentData['name'],
                'document_type' => $documentData['document_type'],
                'document' => $request->file("documents.{$index}.file")->store('members/documents', 'public'),
            ]);
        }

        return redirect()
            ->route('members.show', $member)
            ->with('status', count($request->validated('documents')) . ' document(s) uploaded successfully.');
    }

    public function updateDocument(Request $request, User $member, MemberDocument $memberDocument): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless(
            $branch
            && ! $member->branch_account
            && $member->user_type === 'customer'
            && (string) $member->branch_id === (string) $branch->id
            && (int) $memberDocument->user_id === (int) $member->id,
            404
        );

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'document_type' => ['required', 'string', 'max:100'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx', 'max:10240'],
        ]);

        $oldPath = $memberDocument->document;
        if ($request->hasFile('document')) {
            $validated['document'] = $request->file('document')->store('members/documents', 'public');
        } else {
            unset($validated['document']);
        }

        $memberDocument->update($validated);

        if ($request->hasFile('document') && $oldPath && $oldPath !== $memberDocument->document) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()
            ->route('members.show', $member)
            ->with('status', 'Document updated successfully.');
    }

    public function destroyDocument(Request $request, User $member, MemberDocument $memberDocument): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless(
            $branch
            && ! $member->branch_account
            && $member->user_type === 'customer'
            && (string) $member->branch_id === (string) $branch->id
            && (int) $memberDocument->user_id === (int) $member->id,
            404
        );

        $path = $memberDocument->document;
        $memberDocument->delete();

        if ($path) {
            Storage::disk('public')->delete($path);
        }

        return redirect()
            ->route('members.show', $member)
            ->with('status', 'Document deleted successfully.');
    }

    public function viewDocument(Request $request, User $member, MemberDocument $memberDocument): StreamedResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless(
            $branch
            && ! $member->branch_account
            && $member->user_type === 'customer'
            && (string) $member->branch_id === (string) $branch->id
            && (int) $memberDocument->user_id === (int) $member->id,
            404
        );

        $path = $memberDocument->document;
        abort_unless($path && Storage::disk('public')->exists($path), 404, 'The uploaded document could not be found.');

        return Storage::disk('public')->response(
            $path,
            basename($path),
            [],
            $request->boolean('download') ? 'attachment' : 'inline'
        );
    }
}
