<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActiveBranchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
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
            && (string) $member->branch_id === (string) $branch->id,
            404
        );

        return $this->cardView($member);
    }

    public function customer(Request $request): View
    {
        $member = $request->user();

        abort_unless(
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
