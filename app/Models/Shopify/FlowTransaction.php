<?php

declare(strict_types=1);

namespace App\Models\Shopify;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class FlowTransaction extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';
    public const STATUS_TRY_AGAIN = 'try_again';

    protected $table = 'shopify_flow_transactions';

    protected $fillable = [
        'flow_id',
        'event_id',
        'channel',
        'template_id',
        'targets',
        'delay_minutes',
        'scheduled_at',
        'started_at',
        'finished_at',
        'attempts',
        'status',
        'fail_reason',
        'result',
        'flow_snapshot',
    ];

    protected $casts = [
        'delay_minutes' => 'integer',
        'attempts' => 'integer',

        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',

        'result' => 'array',
        'targets' => 'array',
        'flow_snapshot' => 'array',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function tryAgain(): void
    {
        DB::transaction(function () {
            $this->update(['status' => self::STATUS_TRY_AGAIN]);
            $scheduledAt = now()->addMinutes($this->delay_minutes);

            $duplicate = $this->replicate();
            $duplicate->fill([
                'status' => self::STATUS_PENDING,
                'scheduled_at' => $scheduledAt,
                'started_at' => null,
                'finished_at' => null,
                'attempts' => 0,
                'fail_reason' => null,
                'result' => null,
                'targets' => null,
            ]);
            $duplicate->save();

            return $duplicate;
        });
    }

    public function markProcessing(): void
    {
        $this->increment('attempts');

        $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => now(),
            'fail_reason' => null,
        ]);
    }

    public function markDone(?array $result = null): void
    {
        $this->update([
            'status' => self::STATUS_DONE,
            'finished_at' => now(),
            'result' => $result,
            'fail_reason' => null,
        ]);
    }

    public function markFailed(?string $failReason = null, ?array $result = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'finished_at' => now(),
            'fail_reason' => $failReason,
            'result' => $result,
        ]);
    }
}
