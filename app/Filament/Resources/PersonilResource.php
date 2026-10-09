<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PersonilResource\Pages;
use App\Filament\Resources\PersonilResource\RelationManagers;
use App\Models\Personil;
use App\Services\CutiService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PersonilResource extends Resource
{
    protected static ?string $model = Personil::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('id_personil')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('inisial')
                    ->required()
                    ->maxLength(3),
                Forms\Components\FileUpload::make('ttd')
                    ->label('Tanda Tangan')
                    ->image()
                    ->disk('local'),
                Forms\Components\TextInput::make('username')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn($state) => bcrypt($state))
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn(string $context) => $context === 'create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')->searchable(),
                Tables\Columns\TextColumn::make('id_personil'),
                Tables\Columns\TextColumn::make('inisial'),
                Tables\Columns\TextColumn::make('username'),
                Tables\Columns\TextColumn::make('saldo_cuti')
                    ->label('Saldo Cuti')
                    ->suffix(' hari')
                    ->sortable()
                    ->color(fn($state) => $state < 0 ? 'danger' : null),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Saldo hanya diubah lewat sini agar setiap perubahan tercatat di riwayat.
                Tables\Actions\Action::make('atur_saldo')
                    ->label('Atur saldo cuti')
                    ->icon('heroicon-o-calendar-days')
                    ->fillForm(fn(Personil $record) => ['saldo_baru' => $record->saldo_cuti])
                    ->form([
                        Forms\Components\TextInput::make('saldo_baru')
                            ->label('Saldo baru (hari)')
                            ->numeric()
                            ->integer()
                            ->required(),
                        Forms\Components\TextInput::make('catatan')
                            ->label('Alasan / catatan')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (Personil $record, array $data) {
                        app(CutiService::class)->aturSaldo(
                            $record,
                            (int) $data['saldo_baru'],
                            $data['catatan'],
                            Auth::user()?->name
                        );
                        Notification::make()->title('Saldo cuti diperbarui')->success()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPersonils::route('/'),
            'create' => Pages\CreatePersonil::route('/create'),
            'edit' => Pages\EditPersonil::route('/{record}/edit'),
        ];
    }
}
