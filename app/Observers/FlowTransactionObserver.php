<?php

namespace App\Observers;

use App\Jobs\Shopify\FlowTransactionJob;
use App\Models\Shopify\FlowTransaction;

class FlowTransactionObserver
{
    public function created(FlowTransaction $flowTransaction): void
    {
        FlowTransactionJob::dispatch($flowTransaction->id)->delay($flowTransaction->scheduled_at);
    }
}
