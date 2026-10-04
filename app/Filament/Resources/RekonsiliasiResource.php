<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RekonsiliasiResource\Pages;
use App\Models\Rekonsiliasi;
use App\Services\RekonsiliasiService;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RekonsiliasiResource extends Resource
{
    protected static ?string $model = Rekonsiliasi::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $modelLabel = 'Rekonsiliasi';

    protected static ?string $pluralModelLabel = 'Rekonsiliasi';

    /** Blok input 1 parameter: Block Model dan Real, masing-masing BCM/Ni/Fe/SiO2/MgO. */
    private static function blokParameter(string $label, string $kunci): Forms\Components\Section
    {
        $fields = fn(string $sisi) => [
            Forms\Components\TextInput::make("bcm_$sisi")->label('BCM')->numeric(),
            Forms\Components\TextInput::make("ni_$sisi")->label('Ni')->numeric(),
            Forms\Components\TextInput::make("fe_$sisi")->label('Fe')->numeric(),
            Forms\Components\TextInput::make("sio2_$sisi")->label('SiO2')->numeric(),
            Forms\Components\TextInput::make("mgo_$sisi")->label('MgO')->numeric(),
        ];

        return Forms\Components\Section::make("Parameter $label")
            ->description('Kosongkan seluruh isian kalau parameter ini tidak ada di minggu tersebut.')
            ->collapsible()
            ->schema([
                Forms\Components\Group::make([
                    Forms\Components\Fieldset::make('Block Model')
                        ->schema($fields('bm'))
                        ->columns(5),
                    Forms\Components\Fieldset::make('Real')
                        ->schema($fields('real'))
                        ->columns(5),
                ])->statePath($kunci),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Periode')
                    ->description('Diisi sekali untuk seluruh parameter di bawah. Total ORE tidak diinput, dihitung otomatis dari HGSO + LGSO.')
                    ->schema([
                        Forms\Components\TextInput::make('minggu_ke')
                            ->label('Minggu ke')
                            ->placeholder('W-40')
                            ->required()
                            ->maxLength(50)
                            ->rules([
                                // Nama minggu harus unik; saat edit boleh tetap memakai nama sendiri.
                                fn(?Model $record): Closure => function (string $attribute, $value, Closure $fail) use ($record) {
                                    $ada = Rekonsiliasi::where('minggu_ke', $value)
                                        ->when($record, fn($q) => $q->where('minggu_ke', '!=', $record->minggu_ke))
                                        ->exists();
                                    if ($ada) {
                                        $fail("Minggu $value sudah ada. Buka dan edit data minggu tersebut.");
                                    }
                                },
                            ]),
                        Forms\Components\DatePicker::make('tanggal_mulai')
                            ->required(),
                        Forms\Components\DatePicker::make('tanggal_akhir')
                            ->required()
                            ->afterOrEqual('tanggal_mulai'),
                    ])
                    ->columns(3),
                self::blokParameter('HGSO', 'hgso'),
                self::blokParameter('LGSO', 'lgso'),
                self::blokParameter('Waste', 'waste'),
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
                // Edit membuka seluruh minggu (HGSO + LGSO + Waste) sekaligus.
                Tables\Actions\EditAction::make()->label('Edit minggu'),
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
