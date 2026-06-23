<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopify_partner_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('org_id')->index();
            $table->text('access_token');
            $table->string('api_version')->default('2026-04');
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_partner_accounts');
    }
};