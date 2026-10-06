<?php

declare(strict_types=1);

namespace App\Services\Shopify;

use App\Models\Shopify\Event;
use App\Models\Shopify\Flow;
use App\Models\Shopify\FlowTransaction;
use Illuminate\Support\Facades\Log;

/**
 * Bir event için eşleşen aktif Flow'ların her kanalına FlowTransaction açar.
 *
 * firstOrCreate kullanıldığı için aynı event için tekrar çağrılması güvenlidir
 * (ör. InstallJob yeniden denendiğinde mükerrer transaction oluşmaz).
 *
 * Kanal için gönderilecek alıcı (telefon / e-posta) yoksa o kanalın transaction'ı açılmaz.
 */
class FlowTransactionCreator
{
    public function __construct(private readonly FlowRecipientResolver $recipients) {}

    public function createForEvent(Event $event): void
    {
        if (!$event->app_id) return;

        $shouldSkipForFlowTestMode = config('services.shopify.flow_test_mode', false)
            && (int) $event->store_id !== (int) config('services.shopify.flow_test_store_id');
        if ($shouldSkipForFlowTestMode) return;

        // Aynı kanal birden çok flow'da olabilir; alıcılar kanal başına bir kez toplanır.
        $hasRecipients = [];

        Flow::query()
            ->where('active', true)
            ->where('event_type', $event->type)
            ->whereJsonContains('app_ids', (int) $event->app_id)
            ->each(function (Flow $flow) use ($event, &$hasRecipients) {
                foreach ($flow->channels ?? [] as $channel) {
                    $templateId = $flow->getTemplateIdForChannel($channel);
                    if (!$templateId) continue;

                    $hasRecipients[$channel] ??= $this->recipients->forChannel($event, $channel) !== [];
                    if (!$hasRecipients[$channel]) {
                        Log::info("[flow] alıcı yok, transaction açılmadı: flow={$flow->id}, event={$event->id}, channel={$channel}");
                        continue;
                    }
                    $scheduledAt = now()->addMinutes($flow->delay_minutes);
                    FlowTransaction::firstOrCreate(
                        [
                            'flow_id' => $flow->id,
                            'event_id' => $event->id,
                            'channel' => $channel,
                        ],
                        [
                            'template_id' => $templateId,
                            'delay_minutes' => $flow->delay_minutes,
                            'scheduled_at' => $scheduledAt,

                            'flow_snapshot' => [
                                'name' => $flow->name,
                                'event_type' => $flow->event_type,
                                'app_ids' => $flow->app_ids,
                                'channels' => $flow->channels,
                                'delay_minutes' => $flow->delay_minutes,
                                'whatsapp_template_id' => $flow->whatsapp_template_id,
                                'email_template_id' => $flow->email_template_id,
                            ],
                        ],
                    );
                }
            });
    }
}
