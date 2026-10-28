<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\ZohoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncProjectToZohoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 180];

    public function __construct(public Project $project) {}

    public function handle(ZohoService $zohoService): void
    {
        $zohoService->syncProject($this->project);
    }
}
