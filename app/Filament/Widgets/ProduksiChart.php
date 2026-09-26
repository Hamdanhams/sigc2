<?php

namespace App\Filament\Widgets;

use App\Models\Produksi;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class ProduksiChart extends ChartWidget
{
    protected static ?string $heading = 'Laporan Produksi (14 Hari Terakhir)';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $days = collect(range(13, 0))->map(fn($i) => Carbon::today()->subDays($i));

        $counts = $days->map(function ($day) {
            return Produksi::whereDate('tanggal', $day)->count();
        });

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Laporan',
                    'data' => $counts->toArray(),
                    'backgroundColor' => 'rgba(212, 160, 23, 0.2)',
                    'borderColor' => '#1E3A5F',
                ],
            ],
            'labels' => $days->map(fn($d) => $d->format('d/m'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
