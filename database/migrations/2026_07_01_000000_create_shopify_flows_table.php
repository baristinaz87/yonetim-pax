<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopify_flows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('event_type');
            $table->json('app_ids');
            $table->json('channels');
            $table->unsignedInteger('delay_minutes')->default(0);
            $table->string('whatsapp_template_id')->nullable();
            $table->unsignedBigInteger('email_template_id')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['event_type', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_flows');
    }
};
