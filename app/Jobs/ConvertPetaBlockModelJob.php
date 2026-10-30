<?php

namespace App\Jobs;

use App\Models\PetaBlockModel;
use App\Services\MapConversionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ConvertPetaBlockModelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $petaId;

    public $timeout = 1800;

    // Konversi gagal sudah dicatat sebagai status 'gagal'; jangan diulang otomatis.
    public $tries = 1;

    public function __construct(int $petaId)
    {
        $this->petaId = $petaId;
    }

    public function handle(MapConversionService $service): void
    {
        $peta = PetaBlockModel::find($this->petaId);
        if ($peta && in_array($peta->status, ['pending', 'gagal', 'processing'], true)) {
            $service->convert($peta, PetaBlockModel::FOLDER);
        }
    }
}
