<?php

namespace App\Filament\Widgets;

use App\Models\Permintaan;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestPermintaan extends BaseWidget
{
    protected static ?string $heading = 'Permintaan Terbaru';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Permintaan::query()->with('personil', 'front')->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('personil.nama')->label('Personil'),
                Tables\Columns\TextColumn::make('front.nama_front')->label('Front'),
                Tables\Columns\TextColumn::make('jenis_permintaan')->label('Jenis'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'menunggu',
                        'info' => 'diproses',
                        'success' => 'selesai',
                    ]),
                Tables\Columns\TextColumn::make('created_at')->label('Diajukan')->dateTime('d/m/Y H:i'),
            ]);
    }
}
