<?php

namespace App\Jobs\Shopify;

use App\Constant\ProviderTypeConstant;
use App\Models\Shopify\Event;
use App\Models\Shopify\FlowTransaction;
use App\Models\Shopify\StoreAppData;
use App\Services\BrevoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;
use Illuminate\Support\Facades\Log;

class FlowTransactionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $transactionId) {}

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(BrevoService $brevo): void
    {
        $transaction = FlowTransaction::findOrFail($this->transactionId);
        $transaction->markProcessing();
        $result = match ($transaction->channel) {
            ProviderTypeConstant::WP_PROVIDER => $this->sendWhatsapp($brevo, $transaction),
            ProviderTypeConstant::EMAIL_PROVIDER => $this->sendEmail($brevo, $transaction),
            default => ["status" => false, "message" => "Bilinmeyen kanal: {$transaction->channel}", "result" => []],
        };

        if ($result["status"]) {
            $transaction->markDone($result["result"]);
        } else {
            $transaction->markFailed($result["message"], $result["result"]);
        }
    }

    private function sendWhatsapp(BrevoService $brevo, FlowTransaction $flowTransaction): array
    {
        if (!$flowTransaction->template_id) {
            return ["status" => false, "message" => "template_id bulunamadı.", "result" => []];
        }

        $event = $flowTransaction->event;
        if (!$event) {
            return ['status' => false, 'message' => 'Event bulunamadı.', 'result' => []];
        }

        $phones = $this->phonesForEvent($event);
        $this->saveTargets($flowTransaction, $phones);
        if ($phones === []) {
            return ["status" => false, "message" => "Geçerli telefon numarası bulunamadı.", "result" => []];
        }

        $response = $brevo->sendTemplateMessage($phones, $flowTransaction->template_id);
        if (empty($response['messageId'])) {
            return ["status" => false, "message" => "İleti gönderilirken problem oluştu.", "result" => $response];
        } else {
            return ["status" => true, "message" => "İleti gönderildi.", "result" => $response];
        }
    }

    private function sendEmail(BrevoService $brevo, FlowTransaction $flowTransaction): array
    {
        if (!$flowTransaction->template_id) {
            return ["status" => false, "message" => "template_id bulunamadı.", "result" => []];
        }

        $event = $flowTransaction->event;
        if (!$event) {
            return ['status' => false, 'message' => 'Event bulunamadı.', 'result' => []];
        }

        $emails = $this->emailsForEvent($event);
        $this->saveTargets($flowTransaction, $emails);
        if ($emails === []) {
            return ["status" => false, "message" => "Geçerli e-posta bulunamadı.", "result" => []];
        }

        $toName = $event->store->name ?: $event->store->domain;
        $response = $brevo->sendTemplateEmail($toName, $emails, $flowTransaction->template_id);

        if (empty($response['messageId'])) {
            return ["status" => false, "message" => "İleti gönderilirken problem oluştu.", "result" => $response];
        } else {
            return ["status" => true, "message" => "İleti gönderildi.", "result" => $response];
        }
    }

    private function phonesForEvent(Event $event): array
    {
        return collect(array_merge(
                [$event->store->phone],
                $this->storeAppDataValues($event, ['phone', 'authorized_phone']),
            ))
            ->filter(fn (mixed $phone) => is_scalar($phone))
            ->map(fn (mixed $phone) => $this->normalizePhone($phone))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function emailsForEvent(Event $event): array
    {
        return collect(array_merge(
                [$event->store->email, $event->store->contact_email],
                $this->storeAppDataValues($event, ['email', 'authorized_email']),
            ))
            ->filter(fn (mixed $email) => is_scalar($email))
            ->map(fn (mixed $email) => strtolower(trim((string) $email)))
            ->filter(fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<int, string> $keys
     * @return array<int, mixed>
     */
    private function storeAppDataValues(Event $event, array $keys): array
    {
        if (!$event->store_id || !$event->app_id) return [];

        $data = StoreAppData::query()
            ->where('store_id', $event->store_id)
            ->where('app_id', $event->app_id)
            ->value('data');

        if (!is_array($data)) return [];

        return $this->valuesForKeys($data, $keys);
    }

    /**
     * @param array<mixed> $data
     * @param array<int, string> $keys
     * @return array<int, mixed>
     */
    private function valuesForKeys(array $data, array $keys): array
    {
        $values = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), $keys, true)) {
                $values = array_merge($values, is_array($value) ? $value : [$value]);
            }

            if (is_array($value)) {
                $values = array_merge($values, $this->valuesForKeys($value, $keys));
            }
        }

        return $values;
    }

    private function normalizePhone(mixed $phone): ?string
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = '90'.substr($digits, 1);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '5')) {
            $digits = '90'.$digits;
        }

        return $digits !== '' ? $digits : null;
    }

    /**
     * @param array<int, string> $targets
     */
    private function saveTargets(FlowTransaction $flowTransaction, array $targets): void
    {
        $flowTransaction->forceFill(['targets' => $targets])->save();
    }

    public function failed(Throwable $exception): void
    {
        Log::error("[shopify-flow] başarısız: transaction_id={$this->transactionId}, hata=".$exception->getMessage());
        FlowTransaction::find($this->transactionId)?->markFailed($exception->getMessage());
    }
}
