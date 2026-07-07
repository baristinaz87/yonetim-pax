<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shopify_store_app_data', function (Blueprint $table) {
            $table->id();

            // StoreApp pivot'una 1:1 bağlı (store × app kombinasyonu unique)
            $table->foreignId('store_id')
                ->constrained('shopify_stores')
                ->cascadeOnDelete();

            $table->foreignId('app_id')
                ->constrained('shopify_apps')
                ->cascadeOnDelete();

            // App'in mağaza hakkında topladığı ham JSON veri.
            // Her uygulama kendi formatını döner.
            // Boşsa "veri gelmedi", doluysa "veri var" → bu kadar.
            $table->json('data')->nullable();

            $table->timestamps();

            // Bir store + app için sadece tek data kaydı
            $table->unique(['store_id', 'app_id'], 'uniq_store_app_data');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_store_app_data');
    }
};