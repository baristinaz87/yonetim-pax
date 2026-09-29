<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('shopify:sync')
    ->everyMinute()
    ->withoutOverlapping(10)
    ->runInBackground()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/shopify-sync.log'));

// Her gece 02:00'de: eksik access_token'ları doldur + mağaza bilgilerini zenginleştir.
// 1 saatlik kilit ile aynı gün tekrar çalışması engellenir.
//
// Aktif ama token'ı boş olan mağaza-uygulama kayıtlarını bulur,
// App'in get_access_token_endpoint'ini kullanarak token çeker.
// API'de tanımlı olmayan mağazaları "uninstalled" olarak işaretler.
// Token yazılan mağazalar için shop.json çağrısıyla store bilgileri zenginleştirilir.
Schedule::command('shopify:fix-shop-informations')
    ->dailyAt('02:00')
    ->withoutOverlapping(60)
    ->runInBackground()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/shopify-fix-shop-informations.log'));

// Her saatin 30. dakikasında: aktif + token'ı dolu mağaza-uygulama kayıtları için
// get_app_data_endpoint'ten app data çekip shopify_store_app_data'ya yazar.
// generate-events saat başında çalıştığı için ondan önce veri tazelenmiş olur.
Schedule::command('shopify:update-app-shop-data')
    ->hourlyAt(30)
    ->withoutOverlapping(60)
    ->runInBackground()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/shopify-update-app-shop-data.log'));

// Her generator kendi gün/saat penceresini içeride denetler.
Schedule::command('shopify:generate-events')
    ->hourly()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/shopify-event-generators.log'));
