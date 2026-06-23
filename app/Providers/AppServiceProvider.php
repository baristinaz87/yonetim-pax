<?php

namespace App\Providers;

use App\Models\Shopify\Event as ShopifyEvent;
use App\Observers\ShopifyEventObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Yeni shopify_event kaydı → InstallJob / UninstallJob dispatch.
        // Hem PartnerSyncService (cron) hem WebhookController bu observer'dan geçer.
        ShopifyEvent::observe(ShopifyEventObserver::class);
    }
}
