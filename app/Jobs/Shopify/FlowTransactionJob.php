<?php

namespace App\Jobs\Shopify;

use App\Constant\ProviderTypeConstant;
use App\Models\Shopify\FlowTransaction;
use App\Services\BrevoService;
use App\Services\Shopify\FlowRecipientResolver;
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

    public function handle(BrevoService $brevo, FlowRecipientResolver $recipients): void
    {
        $transaction = FlowTransaction::findOrFail($this->transactionId);
        $transaction->markProcessing();
        $result = match ($transaction->channel) {
            ProviderTypeConstant::WP_PROVIDER => $this->sendWhatsapp($brevo, $recipients, $transaction),
            ProviderTypeConstant::EMAIL_PROVIDER => $this->sendEmail($brevo, $recipients, $transaction),
            default => ["status" => false, "message" => "Bilinmeyen kanal: {$transaction->channel}", "result" => []],
        };

        if ($result["status"]) {
            $transaction->markDone($result["result"]);
        } else {
            $transaction->markFailed($result["message"], $result["result"]);
        }
    }

    private function sendWhatsapp(BrevoService $brevo, FlowRecipientResolver $recipients, FlowTransaction $flowTransaction): array
    {
        if (!$flowTransaction->template_id) {
            return ["status" => false, "message" => "template_id bulunamadı.", "result" => []];
        }

        $event = $flowTransaction->event;
        if (!$event) {
            return ['status' => false, 'message' => 'Event bulunamadı.', 'result' => []];
        }

        $phones = $recipients->phones($event);
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

    private function sendEmail(BrevoService $brevo, FlowRecipientResolver $recipients, FlowTransaction $flowTransaction): array
    {
        if (!$flowTransaction->template_id) {
            return ["status" => false, "message" => "template_id bulunamadı.", "result" => []];
        }

        $event = $flowTransaction->event;
        if (!$event) {
            return ['status' => false, 'message' => 'Event bulunamadı.', 'result' => []];
        }

        $emails = $recipients->emails($event);
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
