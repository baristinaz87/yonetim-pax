<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopify_store_apps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('shopify_stores')->cascadeOnDelete();
            $table->foreignId('app_id')->constrained('shopify_apps')->cascadeOnDelete();
            $table->text('access_token')->nullable();
            $table->timestamp('installed_at')->useCurrent();
            $table->timestamp('uninstalled_at')->nullable();
            $table->string('status')->default('active');
            $table->unique(['store_id', 'app_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_store_apps');
    }
};
