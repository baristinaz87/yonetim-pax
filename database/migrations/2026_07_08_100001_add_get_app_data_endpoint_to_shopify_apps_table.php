<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shopify_apps', function (Blueprint $table) {
            // Per-store app data çekmek için kullanılacak endpoint (her uygulama kendi URL'ini tanımlar).
            $table->string('get_app_data_endpoint')
                ->nullable()
                ->after('get_access_token_endpoint');
        });
    }

    public function down(): void
    {
        Schema::table('shopify_apps', function (Blueprint $table) {
            $table->dropColumn('get_app_data_endpoint');
        });
    }
};