<?php

declare(strict_types=1);

namespace App\Observers;

use App\Constant\ShopifyEventTypeConstant;
use App\Jobs\Shopify\InstallJob;
use App\Jobs\Shopify\UninstallJob;
use App\Models\Shopify\Event;
use App\Services\Shopify\FlowTransactionCreator;
use Illuminate\Support\Facades\Log;

/**
 * shopify_events tablosuna yeni kayıt eklendiğinde
 * uygun job'u dispatchQueue'ya atar.
 *
 *   type='installed'           → InstallJob
 *   type='uninstalled'         → UninstallJob
 *   diğer (generator handle'ı) → job yok; yalnızca flow transaction'lar açılır
 *
 * Flow transaction'ları:
 *   - 'installed' için InstallJob, mağaza bilgileri (token + shop.json)
 *     toplandıktan sonra açar; böylece kurulum maili e-posta adresi
 *     belli olmadan gönderilmez.
 *   - Diğer tüm event'ler için burada hemen açılır.
 *
 * Observer üzerinden dispatch etmek şu avantajları sağlar:
 *   - PartnerSyncService (cron) ve WebhookController (canlı) aynı yolu izler
 *   - Yeni bir event yaratan kod parçası job dispatch'ı unutsa bile observer halleder
 */
class ShopifyEventObserver
{
    public function __construct(private readonly FlowTransactionCreator $flowTransactions) {}

    public function created(Event $event): void
    {
        match ($event->type) {
            ShopifyEventTypeConstant::INSTALLED   => $this->dispatchInstall($event),
            ShopifyEventTypeConstant::UNINSTALLED => $this->dispatchUninstall($event),
            default                               => Log::info("[shopify-event-observer] generator event type: {$event->type}"),
        };

        if ($event->type !== ShopifyEventTypeConstant::INSTALLED) {
            $this->flowTransactions->createForEvent($event);
        }
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
}
