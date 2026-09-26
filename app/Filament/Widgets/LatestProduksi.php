<?php

namespace App\Filament\Widgets;

use App\Models\Produksi;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestProduksi extends BaseWidget
{
    protected static ?string $heading = 'Laporan Produksi Terbaru';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Produksi::query()->with('front', 'pic1')->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')->date('d/m/Y'),
                Tables\Columns\TextColumn::make('shift'),
                Tables\Columns\TextColumn::make('front.nama_front')->label('Front'),
                Tables\Columns\TextColumn::make('pic1.nama')->label('PIC'),
                Tables\Columns\BadgeColumn::make('status_approval')
                    ->colors([
                        'warning' => 'menunggu',
                        'success' => 'disetujui',
                        'danger' => 'ditolak',
                    ]),
            ]);
    }
}
