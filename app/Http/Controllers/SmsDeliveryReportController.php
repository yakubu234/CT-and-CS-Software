<?php

namespace App\Http\Controllers;

use App\Models\SmsMessage;
use App\Services\Sms\SmsSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Receives delivery reports from SMS providers (BulkSMSNigeria, Termii, generic gateways)
 * and marks messages Delivered or Failed. Provider formats differ, so the common field
 * names for the message id and status are all accepted, as JSON, form or query data,
 * one report or a list of them.
 */
class SmsDeliveryReportController extends Controller
{
    private const ID_KEYS = ['message_id', 'messageId', 'msg_id', 'messageid', 'sms_id', 'id', 'reference', 'ref', 'data.message_id', 'data.id'];
    private const STATUS_KEYS = ['status', 'delivery_status', 'dlr_status', 'dlrStatus', 'dlr', 'state', 'report', 'data.status', 'data.delivery_status'];
    private const FAILED_WORDS = ['undeliv', 'not deliv', 'fail', 'reject', 'expire', 'dnd', 'error', 'invalid', 'blacklist', 'block', 'unknown subscriber'];
    private const DELIVERED_WORDS = ['deliver', 'dlvrd', 'delivrd', 'success'];

    public function __invoke(Request $request, string $token, SmsSettingsService $settings): JsonResponse
    {
        abort_unless(hash_equals($settings->deliveryReportToken(), $token), 404);

        $payload = $request->all();
        $reports = array_is_list($payload) ? $payload : (isset($payload['data']) && is_array($payload['data']) && array_is_list($payload['data']) ? $payload['data'] : [$payload]);

        $updated = 0;
        foreach ($reports as $report) {
            if (is_array($report) && $this->apply($report)) {
                $updated++;
            }
        }

        return response()->json(['received' => count($reports), 'updated' => $updated]);
    }

    protected function apply(array $report): bool
    {
        $externalId = $this->first($report, self::ID_KEYS);
        $rawStatus = $this->first($report, self::STATUS_KEYS);

        if ($externalId === null || $rawStatus === null) {
            return false;
        }

        $message = SmsMessage::query()->where('external_id', $externalId)->latest('id')->first();
        if (! $message) {
            return false;
        }

        $status = $this->mapStatus($rawStatus);
        $meta = $message->meta ?? [];
        $meta['delivery'] = ['status' => $rawStatus, 'received_at' => now()->toDateTimeString()];

        $changes = ['meta' => $meta];
        if ($status === SmsMessage::STATUS_DELIVERED) {
            $changes['status'] = SmsMessage::STATUS_DELIVERED;
            $changes['error_message'] = null;
        } elseif ($status === SmsMessage::STATUS_FAILED) {
            $changes['status'] = SmsMessage::STATUS_FAILED;
            $changes['error_message'] = 'Delivery failed: ' . $rawStatus;
        }

        $message->update($changes);

        return true;
    }

    /** delivered, failed, or null when the status is still in progress (e.g. "sent", "queued"). */
    protected function mapStatus(string $raw): ?string
    {
        $value = strtolower(trim($raw));

        foreach (self::FAILED_WORDS as $word) {
            if (str_contains($value, $word)) {
                return SmsMessage::STATUS_FAILED;
            }
        }
        foreach (self::DELIVERED_WORDS as $word) {
            if (str_contains($value, $word)) {
                return SmsMessage::STATUS_DELIVERED;
            }
        }

        return null;
    }

    protected function first(array $report, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = Arr::get($report, $key);
            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }
}
