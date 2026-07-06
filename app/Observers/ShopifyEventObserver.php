<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\Shopify\InstallJob;
use App\Jobs\Shopify\RunFlowJob;
use App\Jobs\Shopify\UninstallJob;
use App\Models\Shopify\Event;
use App\Models\Shopify\Flow;
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
    public function created(Event $event): void
    {
        match ($event->type) {
            'installed'   => $this->dispatchInstall($event),
            'uninstalled' => $this->dispatchUninstall($event),
            default       => Log::info("[shopify-event-observer] bilinmeyen event type: {$event->type}"),
        };

        $this->dispatchFlows($event);
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

    private function dispatchFlows(Event $event): void
    {
        if (! $event->app_id) {
            return;
        }

        Flow::query()
            ->where('active', true)
            ->where('event_type', $event->type)
            ->get()
            ->filter(fn (Flow $flow) => $flow->matches($event))
            ->each(function (Flow $flow) use ($event) {
                RunFlowJob::dispatch($flow->id, $event->id)
                    ->delay(now()->addMinutes($flow->delay_minutes));

                Log::info("[shopify-event-observer] RunFlowJob dispatch: flow_id={$flow->id}, event_id={$event->id}, delay={$flow->delay_minutes}");
            });
    }
}
