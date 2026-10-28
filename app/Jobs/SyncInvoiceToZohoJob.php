<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\ZohoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncInvoiceToZohoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 180];

    public function __construct(public Invoice $invoice) {}

    public function handle(ZohoService $zohoService): void
    {
        $zohoService->syncInvoice($this->invoice);
    }
}
