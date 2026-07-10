<?php

namespace App\Services\Email;

use App\Models\EmailMessage;
use App\Models\EmailPreference;

class EmailPreferenceService
{
    public function canSend(EmailMessage $message): array
    {
        $branchPreference = $message->branch_id
            ? EmailPreference::query()
                ->where('branch_id', $message->branch_id)
                ->whereNull('user_id')
                ->first()
            : null;

        if ($this->isPaused($branchPreference)) {
            return [false, 'Email is paused for this branch.'];
        }

        $userPreference = $message->user_id
            ? EmailPreference::query()
                ->where('user_id', $message->user_id)
                ->whereNull('branch_id')
                ->first()
            : null;

        if ($this->isPaused($userPreference)) {
            return [false, 'Email is paused for this recipient.'];
        }

        return [true, null];
    }

    protected function isPaused(?EmailPreference $preference): bool
    {
        if (! $preference) {
            return false;
        }

        if ($preference->email_enabled) {
            return false;
        }

        return ! $preference->paused_until || $preference->paused_until->isFuture();
    }
}
