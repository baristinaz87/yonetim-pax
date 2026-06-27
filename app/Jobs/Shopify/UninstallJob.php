<?php

declare(strict_types=1);

namespace App\Jobs\Shopify;

use App\Models\Shopify\Event;
use App\Models\Shopify\StoreApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Shopify "app uninstalled" event'i için arka plan işi.
 *
 * Akış:
 *   - StoreApp.status = 'uninstalled', uninstalled_at set edilir
 *   - Access token bellekten silinsin diye null yapılır
 */
class UninstallJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function __construct(public int $eventId) {}

    public function handle(): void
    {
        /** @var Event|null $event */
        $event = Event::with(['store', 'app'])->find($this->eventId);

        if (! $event || $event->type !== 'uninstalled' || ! $event->store || ! $event->app) {
            return;
        }

        $domain = $event->store->domain;
        $app    = $event->app;

        Log::info("[uninstall-job] başladı: app={$app->handle}, store={$domain}, event_id={$event->id}");

        $updated = StoreApp::query()
            ->where('store_id', $event->store->id)
            ->where('app_id', $app->id)
            ->update([
                'status'         => 'uninstalled',
                'uninstalled_at' => $event->created_at ?? now(),
                'access_token'   => null, // uninstall sonrası token geçersiz
            ]);

        Log::info("[uninstall-job] tamamlandı: store={$domain}, app={$app->handle}, affected_rows={$updated}");
    }

    public function failed(Throwable $exception): void
    {
        Log::error("[uninstall-job] tüm denemeler başarısız: event_id={$this->eventId}, hata: ".$exception->getMessage());
    }
}
