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
                    'member_no' => $recipient->display_member_no,
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
            // Members with an active membership in this society (as Member Management lists
            // them, including linked members); non-customer excos still go by their branch.
            $branchId = $campaign->branch_id;
            $query->where(function ($builder) use ($branchId): void {
                $builder->whereHas('branchMemberships', fn ($membership) => $membership->where('branch_id', $branchId)->where('status', true))
                    ->orWhere(fn ($exco) => $exco->where('user_type', '<>', 'customer')->where('branch_id', $branchId));
            });
        }

        $memberIds = $campaign->filters['member_ids'] ?? [];

        if ($campaign->audience_type === 'selected_members' && $memberIds !== []) {
            $query->whereIn('id', $memberIds);
        }

        return $query->get();
    }

    protected function contextForUser(User $user, ?Branch $branch): array
    {
        // society_name is the campaign's society (the member's affiliated society), not
        // the parent union; balances are that society's four accounts.
        $contexts = app(SmsContextBuilder::class);
        $membership = $contexts->membershipFor($user, $branch?->id ?: $user->branch_id);

        return [
            ...$contexts->common($user, $branch, $membership),
            ...$contexts->balances($user, $membership),
            'date_of_birth' => optional($user->detail?->date_of_birth)->format('Y-m-d'),
            'month_label' => now()->format('F Y'),
            'unsubscribe_code' => Str::upper(Str::random(6)),
        ];
    }
}
