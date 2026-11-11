<?php

namespace App\Jobs;

use App\Models\LoopSampel;
use App\Services\LoopEmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class HitungEmbeddingLoopJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;

    public int $tries = 1;

    public function __construct(public int $sampelId) {}

    public function handle(LoopEmbeddingService $service): void
    {
        $sampel = LoopSampel::find($this->sampelId);
        if (!$sampel) {
            return;
        }

        $service->hitung($sampel);
    }
}
