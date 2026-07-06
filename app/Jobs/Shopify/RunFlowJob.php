<?php

declare(strict_types=1);

namespace App\Jobs\Shopify;

use App\Constant\AppTypeConstant;
use App\Constant\ProviderTypeConstant;
use App\Models\NotificationLog;
use App\Models\Shopify\Event;
use App\Models\Shopify\Flow;
use App\Services\BrevoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunFlowJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function __construct(
        public int $flowId,
        public int $eventId,
    ) {}

    public function handle(BrevoService $brevo): void
    {
        $flow = Flow::find($this->flowId);
        $event = Event::with(['store', 'app'])->find($this->eventId);

        if (! $flow || ! $event || ! $event->store || ! $event->app || ! $flow->matches($event)) {
            Log::info("[shopify-flow] atlandı: flow_id={$this->flowId}, event_id={$this->eventId}");
            return;
        }

        foreach ($flow->channels ?? [] as $channel) {
            match ($channel) {
                ProviderTypeConstant::WP_PROVIDER => $this->sendWhatsapp($brevo, $flow, $event),
                ProviderTypeConstant::EMAIL_PROVIDER => $this->sendEmail($brevo, $flow, $event),
                default => Log::warning("[shopify-flow] bilinmeyen kanal: {$channel}"),
            };
        }
    }

    private function sendWhatsapp(BrevoService $brevo, Flow $flow, Event $event): void
    {
        if (! $flow->whatsapp_template_id) {
            return;
        }

        $phones = $this->phonesForEvent($event);
        if ($phones === []) {
            Log::warning("[shopify-flow] telefon bulunamadı: flow_id={$flow->id}, event_id={$event->id}");
            return;
        }

        $response = $brevo->sendTemplateMessage($phones, $flow->whatsapp_template_id);

        if (! empty($response['messageId'])) {
            $this->createNotificationLog($flow, $event, ProviderTypeConstant::WP_PROVIDER, $phones, $flow->whatsapp_template_id, $response);
        }
    }

    private function sendEmail(BrevoService $brevo, Flow $flow, Event $event): void
    {
        if (! $flow->email_template_id) {
            return;
        }

        $emails = $this->emailsForEvent($event);
        if ($emails === []) {
            Log::warning("[shopify-flow] e-posta bulunamadı: flow_id={$flow->id}, event_id={$event->id}");
            return;
        }

        $toName = $event->store->name ?: $event->store->domain;
        $response = $brevo->sendTemplateEmail($toName, $emails, $flow->email_template_id);

        if (! empty($response['messageId'])) {
            $this->createNotificationLog($flow, $event, ProviderTypeConstant::EMAIL_PROVIDER, $emails, $flow->email_template_id, $response);
        }
    }

    private function phonesForEvent(Event $event): array
    {
        $phone = trim((string) $event->store->phone);
        if ($phone === '') {
            return [];
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = '90'.substr($digits, 1);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '5')) {
            $digits = '90'.$digits;
        }

        return $digits !== '' ? [$digits] : [];
    }

    private function emailsForEvent(Event $event): array
    {
        return collect([$event->store->email, $event->store->contact_email])
            ->filter()
            ->map(fn (string $email) => trim($email))
            ->filter(fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }

    private function createNotificationLog(Flow $flow, Event $event, string $type, array $addresses, int|string $templateId, array $response): void
    {
        NotificationLog::create([
            'myshopify_domain' => $event->store->domain,
            'app'              => $event->app->handle ?: AppTypeConstant::E_FATURA,
            'type'             => $type,
            'addresses'        => implode(',', $addresses),
            'template_id'      => (int) $templateId,
            'payload'          => [
                'message_id' => $response['messageId'],
                'flow_id'    => $flow->id,
                'event_id'   => $event->id,
                'event_type' => $event->type,
            ],
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error("[shopify-flow] başarısız: flow_id={$this->flowId}, event_id={$this->eventId}, hata=".$exception->getMessage());
    }
}
