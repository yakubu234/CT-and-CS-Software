<?php

namespace App\Http\Controllers;

use App\Models\SmsAutomationRule;
use App\Models\SmsMessage;
use App\Services\Sms\SmsAutomationService;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SmsMessageController extends Controller
{
    public const STATUSES = [
        SmsMessage::STATUS_AWAITING => 'Awaiting (manual)',
        SmsMessage::STATUS_PENDING => 'Pending',
        SmsMessage::STATUS_SENT => 'Sent',
        SmsMessage::STATUS_FAILED => 'Failed',
        SmsMessage::STATUS_SKIPPED => 'Skipped',
    ];

    public function __construct(
        protected SmsAutomationService $automationService,
    ) {
        $this->middleware('module:sms');
    }

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $ruleId = $request->integer('rule') ?: null;

        $messages = TableListing::paginate(
            TableListing::applySearch(
                $this->filteredQuery($status, $ruleId)
                    ->with(['campaign', 'automationRule', 'user.detail', 'branch'])
                    ->latest('id'),
                $request->string('search')->toString(),
                ['phone', 'recipient_name', 'message', 'provider', 'status', 'reference_key']
            ),
            $request,
            15
        );

        return view('bulk-sms.messages.index', [
            'messages' => $messages,
            'statuses' => self::STATUSES,
            'status' => $status,
            'rules' => SmsAutomationRule::query()->orderBy('name')->get(['id', 'name']),
            'ruleId' => $ruleId,
            'awaitingCount' => $this->filteredQuery(SmsMessage::STATUS_AWAITING, $ruleId)->count(),
        ]);
    }

    /** Send one message that a Manual rule prepared. */
    public function send(SmsMessage $smsMessage): RedirectResponse
    {
        abort_unless($smsMessage->status === SmsMessage::STATUS_AWAITING, 422, 'Only awaiting messages can be sent from here.');

        $result = $this->automationService->releaseAwaiting($smsMessage);

        if ($result->status === SmsMessage::STATUS_SENT) {
            return back()->with('status', "SMS sent to {$result->recipient_name}.");
        }

        return back()->withErrors(['sms' => "SMS to {$result->recipient_name} was not sent: " . ($result->error_message ?: ucfirst($result->status))]);
    }

    /** Send every awaiting message (optionally only for one rule). */
    public function sendAwaiting(Request $request): RedirectResponse
    {
        $ruleId = $request->integer('rule') ?: null;
        $sent = 0;
        $notSent = 0;

        $this->filteredQuery(SmsMessage::STATUS_AWAITING, $ruleId)
            ->orderBy('id')
            ->chunkById(100, function ($messages) use (&$sent, &$notSent): void {
                foreach ($messages as $message) {
                    $result = $this->automationService->releaseAwaiting($message);
                    $result->status === SmsMessage::STATUS_SENT ? $sent++ : $notSent++;
                }
            });

        return back()->with('status', "{$sent} awaiting SMS sent" . ($notSent ? ", {$notSent} not sent (see Failed/Skipped)." : '.'));
    }

    protected function filteredQuery(?string $status, ?int $ruleId)
    {
        return SmsMessage::query()
            ->when($status && array_key_exists($status, self::STATUSES), fn ($query) => $query->where('status', $status))
            ->when($ruleId, fn ($query) => $query->where('automation_rule_id', $ruleId));
    }
}
