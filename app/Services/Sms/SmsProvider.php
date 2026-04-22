<?php

namespace App\Services\Sms;

use App\Models\SmsMessage;

interface SmsProvider
{
    public function send(SmsMessage $message): SmsSendResult;

    public function sendTest(string $phone, string $message, ?string $senderId = null): SmsSendResult;

    public function balance(): SmsBalanceResult;
}
