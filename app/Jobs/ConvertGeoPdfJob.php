<?php

namespace App\Jobs;

use App\Models\PetaLayer;
use App\Services\MapConversionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ConvertGeoPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $petaId;

    public $timeout = 900; // 15 menit, jaga-jaga file besar

    public function __construct(int $petaId)
    {
        $this->petaId = $petaId;
    }

    public function handle(MapConversionService $service): void
    {
        $peta = PetaLayer::find($this->petaId);
        if ($peta) {
            $service->convert($peta);
        }
    }
}
