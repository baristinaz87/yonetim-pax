<?php

declare(strict_types=1);

namespace App\Jobs\Shopify;

use App\Services\Shopify\EventGeneratorEvaluator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class GenerateShopifyEventsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(
        public readonly ?int $generatorId = null,
        public readonly bool $dryRun = false,
    ) {}

    public function middleware(): array
    {
        return [(new WithoutOverlapping('shopify-event-generators'))->dontRelease()];
    }

    public function handle(EventGeneratorEvaluator $evaluator): void
    {
        $evaluator->evaluate($this->generatorId, $this->dryRun);
    }
}
