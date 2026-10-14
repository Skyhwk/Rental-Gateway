<?php

namespace App\Jobs;

use App\Services\Gateway\GatewaySyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ApplyGatewayConfigJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    public $syncLogId;

    public $tries = 3;

    public function __construct($syncLogId)
    {
        $this->syncLogId = $syncLogId;
    }

    public function handle(GatewaySyncService $syncService)
    {
        $syncService->applySyncLog($this->syncLogId);
    }
}
