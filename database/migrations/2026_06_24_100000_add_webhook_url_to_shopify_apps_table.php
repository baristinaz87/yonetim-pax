<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shopify_apps', function (Blueprint $table) {
            // Bu uygulamaya install/uninstall olayları için POST atılacak adres.
            // Örn: https://delivery.paxdigital.net/webhooks/shopify/yurtici-kargo
            $table->string('webhook_url')
                ->nullable()
                ->after('logo');

            // delivery.paxdigital.net API konfigürasyonu — app başına saklanır
            // (çoğu kurulumda hepsi aynı olsa da, gerektiğinde override edebilmek için).
            //
            // Auth (login) endpoint: bearer token almak için kullanılır.
            //   Örn: https://delivery.paxdigital.net/api/login
            // Get-access-token endpoint: shop başına Shopify access token çekmek için.
            //   Örn: https://delivery.paxdigital.net/api/get-password-by-shop
            $table->string('api_auth_endpoint')
                ->nullable()
                ->after('webhook_url');

            $table->string('get_access_token_endpoint')
                ->nullable()
                ->after('api_auth_endpoint');

            $table->string('auth_email')
                ->nullable()
                ->after('get_access_token_endpoint');

            $table->text('auth_password')
                ->nullable()
                ->after('auth_email');

            // NOT: HTTP timeout sütun olarak eklenmedi — sabit 20 saniye (DeliveryApiClient::DEFAULT_TIMEOUT).
        });
    }

    public function down(): void
    {
        Schema::table('shopify_apps', function (Blueprint $table) {
            $table->dropColumn([
                'webhook_url',
                'api_auth_endpoint',
                'get_access_token_endpoint',
                'auth_email',
                'auth_password',
            ]);
        });
    }
};