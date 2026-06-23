<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shopify_apps', function (Blueprint $table) {
            $table->foreignId('partner_account_id')
                ->nullable()
                ->after('handle')
                ->constrained('shopify_partner_accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shopify_apps', function (Blueprint $table) {
            $table->dropConstrainedForeignId('partner_account_id');
        });
    }
};