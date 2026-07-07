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

// Eksik access_token'ları her gece 02:00'de doldur.
// Aktif ama token'ı boş olan mağaza-uygulama kayıtlarını bulur,
// App'in get_access_token_endpoint'ini kullanarak token çeker.
// API'de tanımlı olmayan mağazaları "uninstalled" olarak işaretler.
// Token yazılan mağazalar için shop.json çağrısıyla store bilgileri zenginleştirilir.
Schedule::command('shopify:fix-shop-informations')
    ->dailyAt('02:00')
    ->withoutOverlapping(30)
    ->runInBackground()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/shopify-fix-shop-informations.log'));

