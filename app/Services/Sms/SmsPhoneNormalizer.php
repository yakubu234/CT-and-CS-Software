<?php

namespace App\Services\Sms;

class SmsPhoneNormalizer
{
    public function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (! $digits) {
            return null;
        }

        if (str_starts_with($digits, '234') && strlen($digits) >= 13) {
            return $digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) >= 11) {
            return '234' . substr($digits, 1);
        }

        if (strlen($digits) === 10) {
            return '234' . $digits;
        }

        return $digits;
    }
}
