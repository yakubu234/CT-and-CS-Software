<?php

namespace App\Services\Email;

use App\Models\Branch;
use App\Models\EmailCampaign;
use App\Models\EmailMessage;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmailCampaignService
{
    public function __construct(
        protected EmailTemplateRenderer $renderer,
        protected EmailSettingsService $settings,
        protected EmailDispatchService $dispatchService,
    ) {
    }

    public function createCampaign(User $actor, array $payload): EmailCampaign
    {
        return DB::transaction(function () use ($actor, $payload): EmailCampaign {
            $template = ! empty($payload['template_id'])
                ? EmailTemplate::query()->find($payload['template_id'])
                : null;

            $campaign = EmailCampaign::create([
                'branch_id' => $payload['branch_id'] ?: null,
                'template_id' => $template?->id,
                'name' => $payload['name'],
                'audience_type' => $payload['audience_type'],
                'subject' => $payload['subject'] ?: $template?->subject,
                'body' => $payload['body'] ?: $template?->body,
                'status' => ! empty($payload['scheduled_at'])
                    ? EmailCampaign::STATUS_SCHEDULED
                    : EmailCampaign::STATUS_PROCESSING,
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

    public function queueCampaignMessages(EmailCampaign $campaign): void
    {
        $campaign->loadMissing(['branch', 'template']);

        foreach ($this->resolveRecipients($campaign) as $recipient) {
            $context = $this->contextForUser($recipient, $campaign->branch);

            EmailMessage::create([
                'campaign_id' => $campaign->id,
                'user_id' => $recipient->id,
                'branch_id' => $campaign->branch_id,
                'email' => $recipient->email,
                'recipient_name' => $recipient->name,
                'subject' => $this->renderer->render((string) $campaign->subject, $context),
                'body' => $this->renderer->render((string) $campaign->body, $context),
                'mailer' => $this->settings->mailer(),
                'status' => EmailMessage::STATUS_PENDING,
                'scheduled_for' => $campaign->scheduled_at,
                'reference_key' => 'campaign:' . $campaign->id . ':' . $recipient->id,
                'meta' => [
                    'member_no' => $recipient->display_member_no,
                ],
            ]);
        }
    }

    public function processCampaign(EmailCampaign $campaign): void
    {
        $campaign->update(['status' => EmailCampaign::STATUS_PROCESSING]);

        $failed = false;
        $pending = false;

        foreach ($campaign->messages()->where('status', EmailMessage::STATUS_PENDING)->get() as $message) {
            $result = $this->dispatchService->dispatch($message);

            if ($result->status === EmailMessage::STATUS_PENDING) {
                $pending = true;
            }

            if ($result->status === EmailMessage::STATUS_FAILED) {
                $failed = true;
            }
        }

        $campaign->update([
            'status' => $pending
                ? EmailCampaign::STATUS_PROCESSING
                : ($failed ? EmailCampaign::STATUS_FAILED : EmailCampaign::STATUS_SENT),
            'sent_at' => ! $pending ? now() : null,
        ]);
    }

    public function processScheduledMessages(): int
    {
        $processed = 0;
        $campaignIds = [];

        EmailMessage::query()
            ->with('campaign')
            ->where('status', EmailMessage::STATUS_PENDING)
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

        foreach (array_unique($campaignIds) as $campaignId) {
            $campaign = EmailCampaign::query()->with('messages')->find($campaignId);

            if (! $campaign) {
                continue;
            }

            $hasPending = $campaign->messages->contains(fn (EmailMessage $message): bool => $message->status === EmailMessage::STATUS_PENDING);
            $hasFailed = $campaign->messages->contains(fn (EmailMessage $message): bool => $message->status === EmailMessage::STATUS_FAILED);

            $campaign->update([
                'status' => $hasPending
                    ? EmailCampaign::STATUS_PROCESSING
                    : ($hasFailed ? EmailCampaign::STATUS_FAILED : EmailCampaign::STATUS_SENT),
                'sent_at' => ! $hasPending ? now() : $campaign->sent_at,
            ]);
        }

        return $processed;
    }

    protected function resolveRecipients(EmailCampaign $campaign): Collection
    {
        $query = User::query()
            ->with(['detail', 'branch'])
            ->where('branch_account', false)
            ->whereNull('deleted_at')
            ->whereNotNull('email')
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
        return [
            'member_name' => $user->name,
            'member_no' => $user->display_member_no,
            'first_name' => $user->getRawOriginal('name'),
            'last_name' => $user->last_name,
            'branch_name' => $branch?->name ?: $user->branch?->name,
            'email' => $user->email,
            'mobile' => $user->detail?->mobile,
            'society_name' => 'Oreoluwapo CT&CU',
            'month_label' => now()->format('F Y'),
            'reference_code' => Str::upper(Str::random(8)),
        ];
    }
}
