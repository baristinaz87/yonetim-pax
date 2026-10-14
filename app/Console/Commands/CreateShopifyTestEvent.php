<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Constant\ShopifyEventTypeConstant;
use App\Models\Shopify\App;
use App\Models\Shopify\Event;
use App\Models\Shopify\Store;
use Illuminate\Console\Command;

class CreateShopifyTestEvent extends Command
{
    protected $signature = 'shopify:test-event
        {--app-id= : Shopify app ID}
        {--store-id= : Shopify store ID}
        {--type=installed : installed veya uninstalled}';

    protected $description = 'Flow testleri için shopify_events kaydı oluşturur.';

    public function handle(): int
    {
        $appId = (int) ($this->option('app-id') ?: App::query()->orderBy('id')->value('id'));
        $storeId = (int) ($this->option('store-id') ?: Store::query()->orderBy('id')->value('id'));
        $type = (string) $this->option('type');

        if (! $appId || ! $storeId) {
            $this->error('Test event için en az bir Shopify app ve store kaydı olmalı.');
            return self::FAILURE;
        }

        if (! ShopifyEventTypeConstant::isBuiltIn($type)) {
            $this->error('Type sadece '.implode(', ', ShopifyEventTypeConstant::all()).' olabilir.');
            return self::FAILURE;
        }

        $app = App::findOrFail($appId);
        $store = Store::findOrFail($storeId);

        $event = Event::create([
            'store_id'   => $store->id,
            'app_id'     => $app->id,
            'type'       => $type,
            'label'      => 'Test event ('.ShopifyEventTypeConstant::label($type).')',
            'data'       => [
                'source' => 'create_test_event_job',
                'app'    => $app->handle,
                'store'  => $store->domain,
            ],
            'created_at' => now(),
        ]);

        $this->info("Event oluşturuldu: id={$event->id}, type={$type}, store={$store->domain}, app={$app->handle}");

        return self::SUCCESS;
    }
}
