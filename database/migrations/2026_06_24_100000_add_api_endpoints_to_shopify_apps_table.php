<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shopify_apps', function (Blueprint $table) {
            // Harici API konfigürasyonu — app başına saklanır
            // (çoğu kurulumda hepsi aynı olsa da, gerektiğinde override edebilmek için).
            //
            // api_auth_endpoint        → /api/login (bearer token almak için)
            // get_access_token_endpoint → /api/get-password-by-shop (shop başına access token)
            // auth_email               → login için kullanıcı adı
            // auth_password            → login için parola
            $table->string('api_auth_endpoint')
                ->nullable()
                ->after('logo');

            $table->string('get_access_token_endpoint')
                ->nullable()
                ->after('api_auth_endpoint');

            $table->string('auth_email')
                ->nullable()
                ->after('get_access_token_endpoint');

            $table->text('auth_password')
                ->nullable()
                ->after('auth_email');

            // NOT: HTTP timeout sütun olarak eklenmedi — sabit 20 saniye.
        });
    }

    public function down(): void
    {
        Schema::table('shopify_apps', function (Blueprint $table) {
            $table->dropColumn([
                'api_auth_endpoint',
                'get_access_token_endpoint',
                'auth_email',
                'auth_password',
            ]);
        });
    }
};