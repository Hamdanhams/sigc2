<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RekonsiliasiResource\Pages;
use App\Models\Rekonsiliasi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RekonsiliasiResource extends Resource
{
    protected static ?string $model = Rekonsiliasi::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $modelLabel = 'Rekonsiliasi';

    protected static ?string $pluralModelLabel = 'Rekonsiliasi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Periode')
                    ->schema([
                        Forms\Components\TextInput::make('minggu_ke')
                            ->label('Minggu ke')
                            ->placeholder('W-40')
                            ->required()
                            ->maxLength(50),
                        Forms\Components\DatePicker::make('tanggal_mulai')
                            ->required(),
                        Forms\Components\DatePicker::make('tanggal_akhir')
                            ->required()
                            ->afterOrEqual('tanggal_mulai'),
                        Forms\Components\Select::make('parameter')
                            ->options([
                                'HGSO' => 'HGSO',
                                'LGSO' => 'LGSO',
                                'Waste' => 'Waste',
                            ])
                            ->required()
                            ->helperText('Total ORE tidak diinput, dihitung otomatis dari HGSO + LGSO.')
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn($rule, callable $get) => $rule->where('minggu_ke', $get('minggu_ke'))
                            ),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Block Model (BM)')
                    ->schema([
                        Forms\Components\TextInput::make('bcm_bm')->label('BCM')->numeric(),
                        Forms\Components\TextInput::make('ni_bm')->label('Ni')->numeric(),
                        Forms\Components\TextInput::make('fe_bm')->label('Fe')->numeric(),
                        Forms\Components\TextInput::make('sio2_bm')->label('SiO2')->numeric(),
                        Forms\Components\TextInput::make('mgo_bm')->label('MgO')->numeric(),
                    ])
                    ->columns(5),
                Forms\Components\Section::make('Real')
                    ->schema([
                        Forms\Components\TextInput::make('bcm_real')->label('BCM')->numeric(),
                        Forms\Components\TextInput::make('ni_real')->label('Ni')->numeric(),
                        Forms\Components\TextInput::make('fe_real')->label('Fe')->numeric(),
                        Forms\Components\TextInput::make('sio2_real')->label('SiO2')->numeric(),
                        Forms\Components\TextInput::make('mgo_real')->label('MgO')->numeric(),
                    ])
                    ->columns(5),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('tanggal_mulai', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('minggu_ke')->label('Minggu')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('tanggal_mulai')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('tanggal_akhir')->date('d/m/Y'),
                Tables\Columns\BadgeColumn::make('parameter')
                    ->colors([
                        'success' => 'HGSO',
                        'warning' => 'LGSO',
                        'gray' => 'Waste',
                    ]),
                Tables\Columns\TextColumn::make('bcm_bm')->label('BCM BM')->numeric(2),
                Tables\Columns\TextColumn::make('bcm_real')->label('BCM Real')->numeric(2),
                Tables\Columns\TextColumn::make('ni_bm')->label('Ni BM')->numeric(3)->toggleable(),
                Tables\Columns\TextColumn::make('ni_real')->label('Ni Real')->numeric(3)->toggleable(),
                Tables\Columns\TextColumn::make('fe_bm')->label('Fe BM')->numeric(3)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('fe_real')->label('Fe Real')->numeric(3)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sio2_bm')->label('SiO2 BM')->numeric(3)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sio2_real')->label('SiO2 Real')->numeric(3)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('mgo_bm')->label('MgO BM')->numeric(3)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('mgo_real')->label('MgO Real')->numeric(3)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('minggu_ke')
                    ->label('Minggu')
                    ->options(fn() => Rekonsiliasi::query()
                        ->selectRaw('minggu_ke, MAX(tanggal_mulai) as mulai_terakhir')
                        ->groupBy('minggu_ke')
                        ->orderByDesc('mulai_terakhir')
                        ->pluck('minggu_ke', 'minggu_ke')
                        ->all()),
                Tables\Filters\SelectFilter::make('parameter')
                    ->options(['HGSO' => 'HGSO', 'LGSO' => 'LGSO', 'Waste' => 'Waste']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRekonsiliasis::route('/'),
            'create' => Pages\CreateRekonsiliasi::route('/create'),
            'edit' => Pages\EditRekonsiliasi::route('/{record}/edit'),
        ];
    }
}
