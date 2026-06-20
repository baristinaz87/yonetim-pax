<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopify_stores', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique();
            $table->string('shop_id')->nullable();
            $table->string('name')->nullable();
            $table->string('shop_owner')->nullable();
            $table->string('email')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address1')->nullable();
            $table->string('city')->nullable();
            $table->string('zip')->nullable();
            $table->string('country')->nullable();
            $table->string('country_code')->nullable();
            $table->string('currency')->nullable();
            $table->string('plan_name')->nullable();
            $table->string('plan_display_name')->nullable();
            $table->string('timezone')->nullable();
            $table->string('language')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_stores');
    }
};
