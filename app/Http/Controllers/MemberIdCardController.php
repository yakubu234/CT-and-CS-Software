<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActiveBranchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use App\Services\ActiveMemberBranchService;
use Illuminate\Support\Facades\Storage;

class MemberIdCardController extends Controller
{
    public function __construct(
        protected ActiveBranchService $activeBranchService,
    ) {
    }

    public function admin(Request $request, User $member): View
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless(
            $branch
            && ! $member->branch_account
            && $member->user_type === 'customer'
            && $member->branchMemberships()->where('branch_id', $branch->id)->exists(),
            404
        );

        $membership = $member->branchMemberships()->where('branch_id', $branch->id)->firstOrFail();
        $member->setRelation('activeMembership', $membership);
        $member->setRelation('branch', $branch);

        return $this->cardView($member);
    }

    public function customer(Request $request, ActiveMemberBranchService $activeBranch): View
    {
        $member = $request->user();
        $membership = $activeBranch->current($member);

        abort_unless(
            $membership
            &&
            $member
            && ! $member->branch_account
            && $member->user_type === 'customer',
            404
        );

        return $this->cardView($member);
    }

    protected function cardView(User $member): View
    {
        $member->loadMissing(['detail', 'branch']);
        $branch = $member->branch;

        abort_unless($branch, 404, 'This member is not assigned to a branch.');

        return view('members.id-card', [
            'member' => $member,
            'branch' => $branch,
            'memberPhoto' => $this->storedImageDataUrl($member->profile_picture)
                ?: asset('id-card/bg/john_doe.jpeg'),
            'memberSignature' => $this->storedImageDataUrl($member->signature)
                ?: asset('id-card/bg/signature.png'),
            'branchLogo' => $this->storedImageDataUrl($branch->photo)
                ?: asset('id-card/bg/Vector.png'),
            'branchSignature' => $this->storedImageDataUrl($branch->signature)
                ?: asset('id-card/bg/signature.png'),
        ]);
    }

    protected function storedImageDataUrl(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $mimeType = Storage::disk('public')->mimeType($path) ?: 'image/png';

        return 'data:' . $mimeType . ';base64,' . base64_encode(Storage::disk('public')->get($path));
    }
}
