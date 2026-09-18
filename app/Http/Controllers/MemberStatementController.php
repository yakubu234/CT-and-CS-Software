<?php

namespace App\Http\Controllers;

use App\Mail\EmailModuleMessage;
use App\Models\EmailMessage;
use App\Models\User;
use App\Services\ActiveBranchService;
use App\Services\Email\EmailSmtpAccountService;
use App\Services\MemberStatementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class MemberStatementController extends Controller
{
    public function __construct(
        private ActiveBranchService $branches,
        private MemberStatementService $statements,
        private EmailSmtpAccountService $smtp,
    ) {
        $this->middleware('module:members');
    }

    public function show(Request $request, int $memberId): View
    {
        $member = $this->member($request, $memberId);
        $filters = $this->filters($request);

        return view('members.statement', [
            'member' => $member,
            'filters' => $filters,
            'statement' => $this->statements->rows($member, $filters),
        ]);
    }

    public function pdf(Request $request, int $memberId): Response
    {
        $member = $this->member($request, $memberId);
        $filters = $this->filters($request);

        $pdf = $this->makePdf($member, $filters);

        return $request->boolean('inline')
            ? $pdf->stream('official-statement-' . $member->id . '.pdf')
            : $pdf->download('official-statement-' . $member->id . '.pdf');
    }

    public function email(Request $request, int $memberId): RedirectResponse
    {
        $member = $this->member($request, $memberId);
        $filters = $this->filters($request);
        abort_unless(filter_var($member->email, FILTER_VALIDATE_EMAIL), 422, 'The member has no valid registered email address.');

        $account = $this->smtp->eligibleAccount();
        if (! $account) {
            return back()->withErrors(['email' => 'No SMTP account is currently available. Try again after checking Email Settings.']);
        }

        $message = EmailMessage::create([
            'user_id' => $member->id,
            'branch_id' => $member->branch_id,
            'email' => $member->email,
            'recipient_name' => $member->name,
            'subject' => 'Official member statement - ' . ($member->display_member_no ?: $member->id),
            'body' => '<p>Dear ' . e($member->name) . ',</p><p>Please find your official member statement attached.</p>',
            'status' => EmailMessage::STATUS_PENDING,
            'meta' => ['created_via' => 'office-statement', 'requested_by' => $request->user()->id],
        ]);

        $mailer = $this->smtp->configureRuntimeMailer($account);
        try {
            Mail::mailer($mailer)->to($member->email, $member->name)->send(
                (new EmailModuleMessage($message))->attachData(
                    $this->makePdf($member, $filters)->output(),
                    'official-member-statement.pdf',
                    ['mime' => 'application/pdf']
                )
            );
            $this->smtp->recordSent($account);
            $message->update(['mailer' => $mailer, 'smtp_account_id' => $account->id, 'status' => EmailMessage::STATUS_SENT, 'processed_at' => now(), 'sent_at' => now()]);
        } catch (\Throwable $exception) {
            $message->update(['mailer' => $mailer, 'smtp_account_id' => $account->id, 'status' => EmailMessage::STATUS_FAILED, 'error_message' => $exception->getMessage(), 'processed_at' => now()]);

            return back()->withErrors(['email' => 'The statement could not be emailed. Check Email Messages for the delivery error.']);
        }

        return back()->with('status', 'The official statement was emailed to the member’s registered address.');
    }

    private function member(Request $request, int $memberId): User
    {
        $member = User::withTrashed()->with(['detail', 'branch'])->findOrFail($memberId);
        $allowed = $this->branches->availableBranches($request->user())->pluck('id')->map(fn ($id) => (string) $id);
        abort_unless($member->user_type === 'customer' && ! $member->branch_account && $allowed->contains((string) $member->branch_id), 404);

        return $member;
    }

    private function filters(Request $request): array
    {
        $filters = $request->validate([
            'period' => ['nullable', Rule::in(['full', 'annual', 'monthly', 'custom'])],
            'year' => ['required_if:period,annual', 'nullable', 'integer', 'between:1900,2100'],
            'month' => ['required_if:period,monthly', 'nullable', 'date_format:Y-m'],
            'start_date' => ['required_if:period,custom', 'nullable', 'date'],
            'end_date' => ['required_if:period,custom', 'nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $filters['period'] ??= 'full';

        return $filters;
    }

    private function makePdf(User $member, array $filters)
    {
        $signature = $member->branch?->signature;
        $signatureData = null;
        if ($signature && Storage::disk('public')->exists($signature)) {
            $mime = Storage::disk('public')->mimeType($signature);
            if (in_array($mime, ['image/png', 'image/jpeg', 'image/gif'], true)) {
                $signatureData = 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('public')->get($signature));
            }
        }

        return Pdf::loadView('members.statement-pdf', [
            'member' => $member,
            'statement' => $this->statements->rows($member, $filters),
            'signatureData' => $signatureData,
        ])->setPaper('a4');
    }
}
