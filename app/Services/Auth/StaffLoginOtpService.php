<?php

namespace App\Services\Auth;

use App\Models\EmailMessage;
use App\Models\StaffLoginOtpChallenge;
use App\Models\User;
use App\Services\Email\EmailDispatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class StaffLoginOtpService
{
    public const EXPIRES_MINUTES = 10;
    public const RESEND_SECONDS = 60;
    public const MAX_ATTEMPTS = 5;

    public function __construct(protected EmailDispatchService $emailDispatch)
    {
    }

    public function begin(User $user, Request $request): array
    {
        if (! $user->isAdminSideUser()) {
            throw new RuntimeException('OTP authentication is only available for staff accounts.');
        }

        if (! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('This staff account does not have a valid email address.');
        }

        [$challenge, $plainToken, $code, $shouldSend, $claimedAt] = DB::transaction(
            function () use ($user, $request): array {
                $challenge = StaffLoginOtpChallenge::query()
                    ->where('user_id', $user->id)
                    ->whereNull('consumed_at')
                    ->where('expires_at', '>', now())
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                $plainToken = Str::random(64);

                if (! $challenge) {
                    $code = (string) random_int(100000, 999999);
                    $challenge = StaffLoginOtpChallenge::create([
                        'user_id' => $user->id,
                        'public_token_hash' => hash('sha256', $plainToken),
                        'code_hash' => Hash::make($code),
                        'encrypted_code' => Crypt::encryptString($code),
                        'expires_at' => now()->addMinutes(self::EXPIRES_MINUTES),
                        'request_ip' => $request->ip(),
                        'user_agent' => Str::limit((string) $request->userAgent(), 1000),
                    ]);
                } else {
                    $plainToken = (string) $request->session()->get('staff_login_otp_token');
                    $matchesSession = $plainToken !== ''
                        && hash_equals($challenge->public_token_hash, hash('sha256', $plainToken));

                    if (! $matchesSession) {
                        $plainToken = Str::random(64);
                        $challenge->update(['public_token_hash' => hash('sha256', $plainToken)]);
                    }

                    $code = Crypt::decryptString($challenge->encrypted_code);
                }

                $shouldSend = ! $challenge->last_sent_at
                    || $challenge->last_sent_at->lte(now()->subSeconds(self::RESEND_SECONDS));
                $claimedAt = null;

                if ($shouldSend) {
                    $claimedAt = now();
                    $challenge->update(['last_sent_at' => $claimedAt]);
                }

                return [$challenge->refresh(), $plainToken, $code, $shouldSend, $claimedAt];
            }
        );

        if ($shouldSend) {
            try {
                $this->send($challenge, $user, $code);
            } catch (\Throwable $exception) {
                StaffLoginOtpChallenge::query()
                    ->whereKey($challenge->id)
                    ->where('last_sent_at', $claimedAt)
                    ->update(['last_sent_at' => null]);

                throw $exception;
            }
        }

        return [
            'challenge' => $challenge,
            'token' => $plainToken,
            'sent' => $shouldSend,
        ];
    }

    public function resend(string $plainToken, Request $request): array
    {
        $challenge = $this->findActive($plainToken);

        if (! $challenge) {
            throw new RuntimeException('This OTP session has expired. Please sign in again.');
        }

        return $this->begin($challenge->user, $request);
    }

    public function verify(string $plainToken, string $code): ?User
    {
        return DB::transaction(function () use ($plainToken, $code): ?User {
            $challenge = StaffLoginOtpChallenge::query()
                ->where('public_token_hash', hash('sha256', $plainToken))
                ->whereNull('consumed_at')
                ->lockForUpdate()
                ->first();

            if (! $challenge || $challenge->expires_at->isPast()) {
                return null;
            }

            if ($challenge->attempts >= self::MAX_ATTEMPTS) {
                $challenge->update(['consumed_at' => now()]);

                return null;
            }

            if (! Hash::check($code, $challenge->code_hash)) {
                $attempts = $challenge->attempts + 1;
                $challenge->update([
                    'attempts' => $attempts,
                    'consumed_at' => $attempts >= self::MAX_ATTEMPTS ? now() : null,
                ]);

                return null;
            }

            $challenge->update(['consumed_at' => now()]);

            return $challenge->user;
        });
    }

    public function findActive(string $plainToken): ?StaffLoginOtpChallenge
    {
        if ($plainToken === '') {
            return null;
        }

        return StaffLoginOtpChallenge::query()
            ->with('user')
            ->where('public_token_hash', hash('sha256', $plainToken))
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    protected function send(StaffLoginOtpChallenge $challenge, User $user, string $code): void
    {
        $message = EmailMessage::create([
            'user_id' => $user->id,
            'branch_id' => $user->branch_id,
            'email' => $user->email,
            'recipient_name' => $user->name,
            'subject' => 'Your staff login verification code',
            'body' => view('emails.staff-login-otp', [
                'user' => $user,
                'code' => $code,
                'expiresMinutes' => self::EXPIRES_MINUTES,
            ])->render(),
            'status' => EmailMessage::STATUS_PENDING,
            'related_type' => StaffLoginOtpChallenge::class,
            'related_id' => $challenge->id,
            'reference_key' => 'staff-login-otp:' . $challenge->id . ':' . now()->timestamp,
            'meta' => [
                'transactional_security' => true,
                'created_via' => 'staff-login-otp',
            ],
        ]);

        $result = $this->emailDispatch->dispatch($message);

        if ($result->status !== EmailMessage::STATUS_SENT) {
            throw new RuntimeException($result->error_message ?: 'The OTP email could not be sent.');
        }
    }
}
