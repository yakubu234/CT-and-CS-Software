<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\LoanPayment;
use App\Models\SmsAutomationRule;
use App\Models\SmsMessage;
use App\Models\SmsTemplate;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ActiveBranchService;
use App\Services\Sms\SmsContextBuilder;
use App\Services\Sms\SmsDispatchService;
use App\Services\Sms\SmsPhoneNormalizer;
use App\Services\Sms\SmsPlaceholderCatalog;
use App\Services\Sms\SmsSettingsService;
use App\Services\Sms\SmsTemplateRenderer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Test any SMS alert with real member data or sample figures: preview the exact message,
 * see every placeholder's value and problems, then send it to one phone number.
 */
class SmsTestController extends Controller
{
    public function __construct(
        protected ActiveBranchService $activeBranchService,
        protected SmsPlaceholderCatalog $catalog,
        protected SmsContextBuilder $contexts,
        protected SmsTemplateRenderer $renderer,
        protected SmsPhoneNormalizer $phoneNormalizer,
        protected SmsDispatchService $dispatchService,
        protected SmsSettingsService $settings,
    ) {
        $this->middleware('module:sms');
    }

    public function create(Request $request): View
    {
        $rule = $request->integer('rule') ? SmsAutomationRule::query()->with('template')->find($request->integer('rule')) : null;
        $template = $request->integer('template') ? SmsTemplate::query()->find($request->integer('template')) : $rule?->template;
        $currentBranch = $this->activeBranchService->ensureActiveBranch($request->user());

        return $this->page($request, [
            'event' => $rule?->event ?: ($template && array_key_exists($template->category, $this->catalog->eventLabels()) ? $template->category : SmsAutomationRule::EVENT_TRANSACTION_CREDIT),
            'template_id' => $template?->id,
            'body' => $template?->body ?? '',
            'branch_id' => $rule?->branch_id ?: $currentBranch?->id,
            'data_source' => 'sample',
            'transaction_date' => now()->format('Y-m-d'),
            'amounts' => ['savings' => 5000, 'shares' => 2000, 'authentication' => 0, 'deposit' => 0],
            'principal_paid' => 20000,
            'interest_paid' => 2000,
            'outstanding_loan_balance' => 80000,
            'outstanding_interest' => 0,
            'loan_id' => 'TEST-LOAN',
            'loan_amount' => 100000,
        ]);
    }

    public function run(Request $request): View
    {
        $allowedBranchIds = $this->activeBranchService->availableBranches($request->user())->pluck('id')->all();

        $data = $request->validate([
            'action' => ['required', Rule::in(['preview', 'send'])],
            'event' => ['required', Rule::in(array_keys($this->catalog->eventLabels()))],
            'template_id' => ['nullable', 'exists:sms_templates,id'],
            'body' => ['required', 'string', 'max:1000'],
            'branch_id' => ['required', Rule::in($allowedBranchIds)],
            'member_id' => ['nullable', 'integer', 'exists:users,id'],
            'phone' => ['nullable', 'string', 'max:50'],
            'data_source' => ['required', Rule::in(['sample', 'latest'])],
            'transaction_date' => ['nullable', 'date'],
            'amounts' => ['nullable', 'array'],
            'amounts.*' => ['nullable', 'numeric', 'min:0'],
            'principal_paid' => ['nullable', 'numeric', 'min:0'],
            'interest_paid' => ['nullable', 'numeric', 'min:0'],
            'outstanding_loan_balance' => ['nullable', 'numeric', 'min:0'],
            'outstanding_interest' => ['nullable', 'numeric', 'min:0'],
            'loan_id' => ['nullable', 'string', 'max:50'],
            'loan_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $branch = Branch::query()->findOrFail($data['branch_id']);
        $member = ! empty($data['member_id'])
            ? User::query()->with(['detail', 'branch'])->where('branch_account', false)->find($data['member_id'])
            : null;

        [$context, $notes] = $this->buildContext($data, $branch, $member);
        $message = $this->renderer->render($data['body'], $context);

        $preview = [
            'message' => $message,
            'length' => $this->smsLength($message),
            'notes' => $notes,
            'issues' => $this->catalog->analyze($data['body'], $data['event']),
            'values' => $this->usedValues($data['body'], $context),
            'society_name' => $context['society_name'] ?? null,
            'member' => $member,
        ];

        $sendResult = null;
        if ($data['action'] === 'send') {
            $sendResult = $this->sendTest($data, $branch, $member, $message);
        }

        return $this->page($request, $data, $preview, $sendResult);
    }

    /**
     * @return array{0: array, 1: string[]} placeholder values and notes about where they came from
     */
    protected function buildContext(array $data, Branch $branch, ?User $member): array
    {
        $notes = [];
        $event = $data['event'];
        $date = $data['transaction_date'] ?? now()->format('Y-m-d');

        if ($member) {
            $user = $member;
            $membership = $this->contexts->membershipFor($member, $branch->id);
            if (! $membership) {
                $notes[] = "{$member->name} has no membership in {$branch->name}; balances are taken from all their accounts.";
            } elseif (! $membership->status) {
                $notes[] = "{$member->name}'s membership in {$branch->name} is archived/inactive, so real alerts would not reach them for this society.";
            }
        } else {
            // No member chosen: an unsaved stand-in so the same builder can be used.
            $user = (new User)->forceFill(['name' => 'TEST', 'last_name' => 'MEMBER']);
            $user->setRelation('detail', null);
            $user->setRelation('branch', $branch);
            $membership = null;
            $notes[] = 'No member selected: a sample member (TEST MEMBER) with zero starting balances is used.';
        }

        $override = $member ? [] : ['member_id' => 'TEST_0001', 'member_no' => 'TEST_0001'];

        if (in_array($event, [SmsAutomationRule::EVENT_TRANSACTION_CREDIT, SmsAutomationRule::EVENT_TRANSACTION_DEBIT], true)) {
            $direction = $event === SmsAutomationRule::EVENT_TRANSACTION_DEBIT ? 'dr' : 'cr';

            if ($data['data_source'] === 'latest' && $member) {
                $latest = Transaction::query()->with('account.product')
                    ->where('user_id', $member->id)->where('branch_id', $branch->id)
                    ->where('is_branch', 0)->whereNull('deleted_at')->where('dr_cr', $direction)
                    ->latest('id')->first();

                if ($latest) {
                    $posting = Transaction::query()->with('account.product')
                        ->where('user_id', $member->id)->where('is_branch', 0)->whereNull('deleted_at')
                        ->where('dr_cr', $direction)
                        ->when($latest->batch_id, fn ($query) => $query->where('batch_id', $latest->batch_id), fn ($query) => $query->whereKey($latest->id))
                        ->get();
                    $notes[] = 'Using the member\'s latest real ' . ($direction === 'dr' ? 'debit' : 'credit') . ' posting of ' . $this->contexts->date($latest->trans_date) . ' (' . $posting->count() . ' account entr' . ($posting->count() === 1 ? 'y' : 'ies') . '). Balances are their current balances.';

                    return [[...$this->contexts->transactionBatch($user, $branch, $membership, $direction, $posting->map(fn ($transaction) => [
                        'type' => $transaction->account?->product?->type,
                        'amount' => $transaction->amount,
                        'account_number' => $transaction->account?->account_number,
                    ])->all(), $latest->trans_date), ...$override], $notes];
                }

                $notes[] = 'This member has no ' . ($direction === 'dr' ? 'debit' : 'credit') . ' posting in this society yet, so the sample amounts below were used.';
            }

            $sign = $direction === 'dr' ? -1 : 1;
            $entries = [];
            $adjustments = [];
            // The member's real account numbers for each type, so account_number previews too.
            $accountNumbers = $member
                ? \App\Models\SavingsAccount::query()->with('product')
                    ->where('user_id', $member->id)->where('is_branch_acount', false)->where('status', 1)
                    ->when($membership, fn ($query) => $query->where('member_branch_membership_id', $membership->id))
                    ->get()
                    ->mapWithKeys(fn ($account) => [strtoupper((string) $account->product?->type) => $account->account_number])
                : collect();
            foreach (SmsPlaceholderCatalog::ACCOUNT_TYPES as $key => $type) {
                $amount = (float) ($data['amounts'][$key] ?? 0);
                if ($amount > 0) {
                    $entries[] = ['type' => $type, 'amount' => $amount, 'account_number' => $accountNumbers[$type] ?? null];
                    $adjustments[$key] = $sign * $amount;
                }
            }
            $notes[] = 'Sample posting: balances shown are the ' . ($member ? "member's current balances" : 'starting balances') . ($direction === 'dr' ? ' minus' : ' plus') . ' the sample amounts, as they would be after posting.';

            return [[...$this->contexts->transactionBatch($user, $branch, $membership, $direction, $entries, $date, $adjustments), ...$override], $notes];
        }

        if ($event === SmsAutomationRule::EVENT_LOAN_REPAYMENT) {
            if ($data['data_source'] === 'latest' && $member) {
                $payment = LoanPayment::query()
                    ->whereHas('loan', fn ($query) => $query->where('borrower_id', $member->id)->where('branch_id', $branch->id))
                    ->latest('id')->first();

                if ($payment) {
                    $notes[] = 'Using the member\'s latest real loan repayment of ' . $this->contexts->date($payment->paid_at) . '.';

                    return [$this->contexts->loanRepayment($payment), $notes];
                }

                $notes[] = 'This member has no loan repayment in this society yet, so the sample figures below were used.';
            }

            return [[...$this->contexts->loanRepaymentValues($user, $branch, $membership, [
                'principal_paid' => $data['principal_paid'] ?? 0,
                'interest_paid' => $data['interest_paid'] ?? 0,
                'outstanding_loan_balance' => $data['outstanding_loan_balance'] ?? 0,
                'outstanding_interest' => $data['outstanding_interest'] ?? 0,
                'loan_id' => $data['loan_id'] ?? '',
                'loan_amount' => $data['loan_amount'] ?? 0,
                'transaction_date' => $date,
            ]), ...$override], $notes];
        }

        $context = [...$this->contexts->common($user, $branch, $membership), ...$override];

        return [match ($event) {
            SmsAutomationRule::EVENT_LOAN_APPROVED => [
                ...$context,
                'loan_id' => $data['loan_id'] ?? '',
                'loan_amount' => $this->contexts->money($data['loan_amount'] ?? 0),
                'release_date' => $this->contexts->date($date),
                'due_date' => $this->contexts->date(Carbon::parse($date)->addYear()),
            ],
            SmsAutomationRule::EVENT_BIRTHDAY => [
                ...$context,
                'birth_day' => optional($member?->detail?->date_of_birth)->format('d M') ?: Carbon::parse($date)->format('d M'),
            ],
            default => [
                ...$context,
                ...$this->contexts->balances($user, $membership),
                'month_label' => Carbon::parse($date)->format('F Y'),
            ],
        }, $notes];
    }

    protected function sendTest(array $data, Branch $branch, ?User $member, string $message): array
    {
        $phone = $this->phoneNormalizer->normalize(($data['phone'] ?? null) ?: $member?->detail?->mobile);

        if (! $phone) {
            return ['successful' => false, 'message' => 'Enter a phone number, or choose a member who has one in their profile.'];
        }

        $sms = SmsMessage::create([
            'user_id' => $member?->id,
            'branch_id' => $branch->id,
            'phone' => $phone,
            'recipient_name' => $member?->name ?: 'Test recipient',
            'message' => $message,
            'provider' => $this->settings->activeProvider(),
            'sender_id' => $this->settings->senderId(),
            'status' => SmsMessage::STATUS_PENDING,
            'reference_key' => 'test:' . Str::uuid(),
            'scheduled_for' => now(),
            'meta' => ['test' => true, 'event' => $data['event'], 'template_id' => $data['template_id'] ?? null],
        ]);

        $result = $this->dispatchService->dispatch($sms);

        return [
            'successful' => $result->status === SmsMessage::STATUS_SENT,
            'message' => $result->status === SmsMessage::STATUS_SENT
                ? "Test SMS accepted by the provider for {$phone}. It is listed in SMS Logs marked TEST."
                : "Test SMS to {$phone} was not sent: " . ($result->error_message ?: ucfirst($result->status)),
        ];
    }

    /** Each placeholder used in the body with the value it got (null = not provided). */
    protected function usedValues(string $body, array $context): array
    {
        preg_match_all('/{{\s*([a-zA-Z0-9_\.]+)\s*}}/', $body, $matches);
        $values = [];
        foreach (array_unique($matches[1]) as $name) {
            $value = $context[$name] ?? null;
            $values[$name] = is_scalar($value) && (string) $value !== '' ? (string) $value : null;
        }

        return $values;
    }

    /** Characters and SMS pages (GSM 160/153 per page, or 70/67 when special characters are used). */
    protected function smsLength(string $message): array
    {
        $isGsm = (bool) preg_match('/^[\x0A\x0D\x20-\x7E£¥èéùìòÇØøÅå_ÆæßÉ¤¡ÄÖÑÜ§¿äöñüà€]*$/u', $message);
        $length = mb_strlen($message);
        [$single, $multi] = $isGsm ? [160, 153] : [70, 67];

        return [
            'characters' => $length,
            'pages' => $length <= $single ? 1 : (int) ceil($length / $multi),
            'encoding' => $isGsm ? 'Standard (GSM)' : 'Unicode (special characters: fewer characters per page)',
        ];
    }

    protected function page(Request $request, array $input, ?array $preview = null, ?array $sendResult = null): View
    {
        $member = ! empty($input['member_id']) ? User::query()->with('detail')->find($input['member_id']) : null;

        return view('bulk-sms.test.index', [
            'input' => $input,
            'preview' => $preview,
            'sendResult' => $sendResult,
            'eventLabels' => $this->catalog->eventLabels(),
            'placeholders' => $this->catalog->forEvent($input['event'] ?? null),
            'allPlaceholders' => collect(array_keys($this->catalog->eventLabels()))
                ->mapWithKeys(fn ($event) => [$event => array_keys($this->catalog->forEvent($event))])->all(),
            'templates' => SmsTemplate::query()->where('status', true)->orderBy('name')->get(),
            'branches' => $this->activeBranchService->availableBranches($request->user()),
            'selectedMember' => $member,
            'accountTypes' => SmsPlaceholderCatalog::ACCOUNT_TYPES,
        ]);
    }
}
