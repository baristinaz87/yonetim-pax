<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Önceki migration'ın yarım kalan halini düzeltir.
 *
 * Geçmiş:
 *   1. İlk migration (2026_06_24_100000_add_webhook_url_to_shopify_apps_table)
 *      hem webhook_url hem de delivery_login_url, delivery_get_token_url,
 *      delivery_email, delivery_password, delivery_timeout sütunlarını ekledi.
 *   2. Migration dosyası silindi ve yeniden adlandırıldı → Laravel eski
 *      migration'ı rollback edemiyor ("Migration not found").
 *   3. Yeni dosya (2026_06_24_100000_add_api_endpoints_to_shopify_apps_table)
 *      api_auth_endpoint eklemeye çalıştı → "Duplicate column" hatası.
 *
 * Bu migration:
 *   - delivery_* sütunlarını yeniden adlandırır (auth_* / *_endpoint)
 *   - webhook_url ve delivery_timeout sütunlarını siler
 *   - api_auth_endpoint / get_access_token_endpoint zaten varsa atlar
 */
return new class extends Migration
{
    public function up(): void
    {
        $columns = collect(Schema::getColumnListing('shopify_apps'));

        // 1) Eski delivery_* sütunlarını yeniden adlandır (varsa)
        // MySQL rename column syntax
        if ($columns->contains('delivery_login_url')) {
            DB::statement('ALTER TABLE `shopify_apps` CHANGE `delivery_login_url` `api_auth_endpoint` VARCHAR(255) NULL');
        }
        if ($columns->contains('delivery_get_token_url')) {
            DB::statement('ALTER TABLE `shopify_apps` CHANGE `delivery_get_token_url` `get_access_token_endpoint` VARCHAR(255) NULL');
        }
        if ($columns->contains('delivery_email')) {
            DB::statement('ALTER TABLE `shopify_apps` CHANGE `delivery_email` `auth_email` VARCHAR(255) NULL');
        }
        if ($columns->contains('delivery_password')) {
            DB::statement('ALTER TABLE `shopify_apps` CHANGE `delivery_password` `auth_password` TEXT NULL');
        }

        // 2) Yeni eklenmesi gereken sütunlar hâlâ yoksa ekle (eski migration
        //    bu isimlerle eklemiş olabilir ama rename sonrası tekrar garanti edelim)
        Schema::table('shopify_apps', function (Blueprint $table) use ($columns) {
            if (! $columns->contains('api_auth_endpoint') && ! Schema::hasColumn('shopify_apps', 'api_auth_endpoint')) {
                $table->string('api_auth_endpoint')->nullable()->after('logo');
            }
            if (! $columns->contains('get_access_token_endpoint') && ! Schema::hasColumn('shopify_apps', 'get_access_token_endpoint')) {
                $table->string('get_access_token_endpoint')->nullable()->after('api_auth_endpoint');
            }
            if (! $columns->contains('auth_email') && ! Schema::hasColumn('shopify_apps', 'auth_email')) {
                $table->string('auth_email')->nullable()->after('get_access_token_endpoint');
            }
            if (! $columns->contains('auth_password') && ! Schema::hasColumn('shopify_apps', 'auth_password')) {
                $table->text('auth_password')->nullable()->after('auth_email');
            }
        });

        // 3) İstenmeyen sütunları sil
        if (Schema::hasColumn('shopify_apps', 'webhook_url')) {
            Schema::table('shopify_apps', function (Blueprint $table) {
                $table->dropColumn('webhook_url');
            });
        }
        if (Schema::hasColumn('shopify_apps', 'delivery_timeout')) {
            Schema::table('shopify_apps', function (Blueprint $table) {
                $table->dropColumn('delivery_timeout');
            });
        }
    }

    public function down(): void
    {
        // Geri alma: yeni isimleri eski isimlere çevir
        $columns = collect(Schema::getColumnListing('shopify_apps'));

        if ($columns->contains('api_auth_endpoint')) {
            DB::statement('ALTER TABLE `shopify_apps` CHANGE `api_auth_endpoint` `delivery_login_url` VARCHAR(255) NULL');
        }
        if ($columns->contains('get_access_token_endpoint')) {
            DB::statement('ALTER TABLE `shopify_apps` CHANGE `get_access_token_endpoint` `delivery_get_token_url` VARCHAR(255) NULL');
        }
        if ($columns->contains('auth_email')) {
            DB::statement('ALTER TABLE `shopify_apps` CHANGE `auth_email` `delivery_email` VARCHAR(255) NULL');
        }
        if ($columns->contains('auth_password')) {
            DB::statement('ALTER TABLE `shopify_apps` CHANGE `auth_password` `delivery_password` TEXT NULL');
        }
    }
};