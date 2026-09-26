<?php

namespace App\Filament\Widgets;

use App\Models\Produksi;
use App\Models\Fsbs;
use App\Models\Permintaan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProduksiStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Laporan Produksi Hari Ini', Produksi::whereDate('tanggal', today())->count())
                ->description('Laporan yang masuk hari ini')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary'),

            Stat::make('Menunggu Approval', Produksi::where('status_approval', 'menunggu')->count())
                ->description('Laporan produksi belum disetujui')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('FSBS Hari Ini', Fsbs::whereDate('created_at', today())->count())
                ->description('Sampel FSBS yang di-plot hari ini')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('success'),

            Stat::make('Permintaan Menunggu', Permintaan::where('status', 'menunggu')->count())
                ->description('Permintaan belum diproses')
                ->descriptionIcon('heroicon-m-inbox')
                ->color('danger'),
        ];
    }
}
