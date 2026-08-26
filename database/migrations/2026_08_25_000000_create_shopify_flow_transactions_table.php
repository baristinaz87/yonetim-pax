<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopify_flow_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_id')->nullable()->constrained('shopify_flows')->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('shopify_events')->nullOnDelete();
            $table->string('channel');
            $table->string('template_id');
            $table->json('targets')->nullable();
            $table->unsignedInteger('delay_minutes')->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->string('status')->default('pending');
            $table->text('fail_reason')->nullable();
            $table->json('result')->nullable();
            $table->json('flow_snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_flow_transactions');
    }
};
