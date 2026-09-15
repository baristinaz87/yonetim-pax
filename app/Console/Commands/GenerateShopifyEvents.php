<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Shopify\EventGeneratorEvaluator;
use Illuminate\Console\Command;

class GenerateShopifyEvents extends Command
{
    protected $signature = 'shopify:generate-events
        {--generator= : Sadece belirtilen generator ID için çalıştır}
        {--dry-run : Event yazmadan eşleşmeleri raporla}';

    protected $description = 'Aktif Shopify event generator koşullarını değerlendirir ve uygun event kayıtlarını üretir.';

    public function handle(EventGeneratorEvaluator $evaluator): int
    {
        $generatorId = $this->option('generator') ? (int) $this->option('generator') : null;
        $stats = $evaluator->evaluate($generatorId, (bool) $this->option('dry-run'));

        $this->table(['Kontrol', 'Adet'], [
            ['İncelenen kayıt', $stats['checked']],
            ['Koşulu sağlayan', $stats['matched']],
            ['Cooldownda atlanan', $stats['cooldown_skipped']],
            ['Verisi eski olduğu için atlanan', $stats['stale_skipped']],
            ['Saat dışında atlanan', $stats['schedule_skipped']],
            [$this->option('dry-run') ? 'Oluşacak event' : 'Oluşan event', $stats['emitted']],
        ]);

        return self::SUCCESS;
    }
}
