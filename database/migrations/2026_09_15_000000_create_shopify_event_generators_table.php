<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopify_event_generators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('handle')->unique();
            $table->json('app_ids');
            $table->json('conditions');
            $table->string('condition_logic')->default('all');
            $table->unsignedInteger('cooldown_minutes')->default(0);
            $table->unsignedInteger('max_data_age_minutes')->nullable();
            $table->json('schedule')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['active', 'id']);
        });

        Schema::table('shopify_events', function (Blueprint $table) {
            $table->foreignId('event_generator_id')
                ->nullable()
                ->after('app_id')
                ->constrained('shopify_event_generators')
                ->nullOnDelete();

            $table->index(
                ['event_generator_id', 'store_id', 'app_id', 'created_at'],
                'shopify_events_generator_cooldown_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('shopify_events', function (Blueprint $table) {
            $table->dropIndex('shopify_events_generator_cooldown_index');
            $table->dropConstrainedForeignId('event_generator_id');
        });

        Schema::dropIfExists('shopify_event_generators');
    }
};
