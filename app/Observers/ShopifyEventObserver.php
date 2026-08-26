<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\Shopify\InstallJob;
use App\Jobs\Shopify\UninstallJob;
use App\Models\Shopify\Event;
use App\Models\Shopify\Flow;
use App\Models\Shopify\FlowTransaction;
use Illuminate\Support\Facades\Log;

/**
 * shopify_events tablosuna yeni kayıt eklendiğinde
 * uygun job'u dispatchQueue'ya atar.
 *
 *   type='installed'   → InstallJob
 *   type='uninstalled' → UninstallJob
 *
 * Observer üzerinden dispatch etmek şu avantajları sağlar:
 *   - PartnerSyncService (cron) ve WebhookController (canlı) aynı yolu izler
 *   - Yeni bir event yaratan kod parçası job dispatch'ı unutsa bile observer halleder
 */
class ShopifyEventObserver
{
    private const FLOW_TEST_STORE_ID = 302;

    public function created(Event $event): void
    {
        match ($event->type) {
            'installed'   => $this->dispatchInstall($event),
            'uninstalled' => $this->dispatchUninstall($event),
            default       => Log::info("[shopify-event-observer] bilinmeyen event type: {$event->type}"),
        };

        $this->createFlowTransactions($event);
    }

    private function dispatchInstall(Event $event): void
    {
        InstallJob::dispatch($event->id);
        Log::info("[shopify-event-observer] InstallJob dispatch: event_id={$event->id}");
    }

    private function dispatchUninstall(Event $event): void
    {
        UninstallJob::dispatch($event->id);
        Log::info("[shopify-event-observer] UninstallJob dispatch: event_id={$event->id}");
    }

    private function createFlowTransactions(Event $event): void
    {
        if (!$event->app_id) return;

        $shouldSkipForFlowTestMode = config('services.shopify.flow_test_mode', false)
            && $event->store->id !== self::FLOW_TEST_STORE_ID;
        if ($shouldSkipForFlowTestMode) return;

        Flow::query()
            ->where('active', true)
            ->where('event_type', $event->type)
            ->whereJsonContains('app_ids', (int) $event->app_id)
            ->each(function (Flow $flow) use ($event) {
                foreach ($flow->channels ?? [] as $channel) {
                    $templateId = $flow->getTemplateIdForChannel($channel);
                    if (!$templateId) continue;
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
