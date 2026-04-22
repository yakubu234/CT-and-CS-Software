<?php

namespace App\Services\Sms;

use App\Models\Branch;
use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use App\Models\SmsTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SmsCampaignService
{
    public function __construct(
        protected SmsTemplateRenderer $renderer,
        protected SmsPhoneNormalizer $phoneNormalizer,
        protected SmsSettingsService $settings,
        protected SmsDispatchService $dispatchService,
    ) {
    }

    public function createCampaign(User $actor, array $payload): SmsCampaign
    {
        return DB::transaction(function () use ($actor, $payload): SmsCampaign {
            $template = ! empty($payload['template_id'])
                ? SmsTemplate::query()->find($payload['template_id'])
                : null;

            $campaign = SmsCampaign::create([
                'branch_id' => $payload['branch_id'] ?: null,
                'template_id' => $template?->id,
                'name' => $payload['name'],
                'audience_type' => $payload['audience_type'],
                'message' => $payload['message'] ?: $template?->body,
                'status' => ! empty($payload['scheduled_at'])
                    ? SmsCampaign::STATUS_SCHEDULED
                    : SmsCampaign::STATUS_PROCESSING,
                'scheduled_at' => ! empty($payload['scheduled_at']) ? Carbon::parse($payload['scheduled_at']) : null,
                'filters' => [
                    'member_ids' => array_values($payload['member_ids'] ?? []),
                ],
                'meta' => [
                    'created_via' => 'dashboard',
                ],
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->queueCampaignMessages($campaign);

            if (! $campaign->scheduled_at) {
                $this->processCampaign($campaign);
            }

            return $campaign->fresh(['branch', 'template', 'messages']);
        });
    }

    public function queueCampaignMessages(SmsCampaign $campaign): void
    {
        $campaign->loadMissing(['branch', 'template']);

        foreach ($this->resolveRecipients($campaign) as $recipient) {
            $context = $this->contextForUser($recipient, $campaign->branch);
            $renderedMessage = $this->renderer->render((string) $campaign->message, $context);

            SmsMessage::create([
                'campaign_id' => $campaign->id,
                'user_id' => $recipient->id,
                'branch_id' => $campaign->branch_id,
                'phone' => $this->phoneNormalizer->normalize($recipient->detail?->mobile),
                'recipient_name' => $recipient->name,
                'message' => $renderedMessage,
                'provider' => $this->settings->activeProvider(),
                'sender_id' => $this->settings->senderId(),
                'status' => SmsMessage::STATUS_PENDING,
                'scheduled_for' => $campaign->scheduled_at,
                'reference_key' => 'campaign:' . $campaign->id . ':' . $recipient->id,
                'meta' => [
                    'member_no' => $recipient->detail?->member_no ?: $recipient->member_no,
                ],
            ]);
        }
    }

    public function processCampaign(SmsCampaign $campaign): void
    {
        $campaign->update([
            'status' => SmsCampaign::STATUS_PROCESSING,
        ]);

        $failed = false;

        foreach ($campaign->messages()->where('status', SmsMessage::STATUS_PENDING)->get() as $message) {
            $result = $this->dispatchService->dispatch($message);

            if ($result->status !== SmsMessage::STATUS_SENT) {
                $failed = true;
            }
        }

        $campaign->update([
            'status' => $failed ? SmsCampaign::STATUS_FAILED : SmsCampaign::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    public function processScheduledMessages(): int
    {
        $processed = 0;
        $campaignIds = [];

        SmsMessage::query()
            ->with('campaign')
            ->where('status', SmsMessage::STATUS_PENDING)
            ->where(function ($query): void {
                $query->whereNull('scheduled_for')
                    ->orWhere('scheduled_for', '<=', now());
            })
            ->orderBy('id')
            ->chunkById(100, function (Collection $messages) use (&$processed, &$campaignIds): void {
                foreach ($messages as $message) {
                    if ($message->campaign_id) {
                        $campaignIds[] = $message->campaign_id;
                    }

                    $this->dispatchService->dispatch($message);
                    $processed++;
                }
            });

        SmsCampaign::query()
            ->whereIn('status', [SmsCampaign::STATUS_SCHEDULED, SmsCampaign::STATUS_PROCESSING])
            ->where(function ($query): void {
                $query->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->get()
            ->each(function (SmsCampaign $campaign) use (&$campaignIds): void {
                $campaignIds[] = $campaign->id;
            });

        foreach (array_unique($campaignIds) as $campaignId) {
            $campaign = SmsCampaign::query()->with('messages')->find($campaignId);

            if (! $campaign) {
                continue;
            }

            $hasPending = $campaign->messages->contains(fn (SmsMessage $message): bool => $message->status === SmsMessage::STATUS_PENDING);
            $hasFailed = $campaign->messages->contains(fn (SmsMessage $message): bool => $message->status === SmsMessage::STATUS_FAILED);

            $campaign->update([
                'status' => $hasPending
                    ? SmsCampaign::STATUS_PROCESSING
                    : ($hasFailed ? SmsCampaign::STATUS_FAILED : SmsCampaign::STATUS_SENT),
                'sent_at' => ! $hasPending ? now() : $campaign->sent_at,
            ]);
        }

        return $processed;
    }

    protected function resolveRecipients(SmsCampaign $campaign): Collection
    {
        $query = User::query()
            ->with('detail')
            ->where('branch_account', false)
            ->whereNull('deleted_at')
            ->where(function ($builder): void {
                $builder->where('user_type', 'customer')
                    ->orWhere('society_exco', true)
                    ->orWhere('former_exco', true);
            })
            ->orderBy('name');

        if ($campaign->branch_id) {
            $query->where('branch_id', $campaign->branch_id);
        }

        $memberIds = $campaign->filters['member_ids'] ?? [];

        if ($campaign->audience_type === 'selected_members' && $memberIds !== []) {
            $query->whereIn('id', $memberIds);
        }

        return $query->get();
    }

    protected function contextForUser(User $user, ?Branch $branch): array
    {
        $accounts = $user->savingsAccounts()
            ->with('product')
            ->where('is_branch_acount', false)
            ->where('status', 1)
            ->get();

        $statement = $this->statementSummary($accounts);

        return [
            'member_name' => $user->name,
            'member_no' => $user->detail?->member_no ?: $user->member_no,
            'first_name' => $user->getRawOriginal('name'),
            'last_name' => $user->last_name,
            'branch_name' => $branch?->name ?: $user->branch?->name,
            'mobile' => $user->detail?->mobile,
            'date_of_birth' => optional($user->detail?->date_of_birth)->format('Y-m-d'),
            'statement_summary' => $statement['full'],
            'statement_compact' => $statement['compact'],
            'statement_total_balance' => $statement['total'],
            'month_label' => now()->format('F Y'),
            'society_name' => 'Oreoluwapo CT&CS',
            'unsubscribe_code' => Str::upper(Str::random(6)),
        ];
    }

    protected function statementSummary(Collection $accounts): array
    {
        $items = $accounts->map(function ($account): array {
            $type = strtoupper((string) ($account->product?->type ?: 'ACCOUNT'));
            $label = match ($type) {
                'SAVINGS' => 'Sav',
                'SHARES' => 'Shr',
                'AUTHENTICATION' => 'Auth',
                'DEPOSIT' => 'Dep',
                default => Str::title(Str::lower($type)),
            };

            return [
                'full' => ($account->product?->type ?: 'Account') . ': ' . number_format((float) $account->balance, 2),
                'compact' => $label . ' ' . number_format((float) $account->balance, 2),
                'balance' => (float) $account->balance,
            ];
        });

        return [
            'full' => $items->pluck('full')->implode(', '),
            'compact' => $items->pluck('compact')->implode(' | '),
            'total' => number_format((float) $items->sum('balance'), 2),
        ];
    }
}
