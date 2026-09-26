<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserPegawaiResource\Pages;
use App\Filament\Resources\UserPegawaiResource\RelationManagers;
use App\Models\UserPegawai;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserPegawaiResource extends Resource
{
    protected static ?string $model = UserPegawai::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('npp')
                    ->label('NPP')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\FileUpload::make('ttd')
                    ->label('Tanda Tangan')
                    ->image()
                    ->disk('local'),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->default('active')
                    ->required(),
                Forms\Components\Section::make('Akun Login (untuk approval di aplikasi)')
                    ->schema([
                        Forms\Components\TextInput::make('username')
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Kosongkan jika belum perlu akses login'),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn($state) => filled($state) ? bcrypt($state) : null)
                            ->dehydrated(fn($state) => filled($state))
                            ->maxLength(255)
                            ->helperText('Kosongkan jika tidak ingin mengubah password'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')->searchable(),
                Tables\Columns\TextColumn::make('npp')->label('NPP')->searchable(),
                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->placeholder('Belum diatur')
                    ->searchable(),
                Tables\Columns\IconColumn::make('has_login')
                    ->label('Bisa Login')
                    ->boolean()
                    ->getStateUsing(fn($record) => filled($record->username) && filled($record->password)),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ]),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserPegawais::route('/'),
            'create' => Pages\CreateUserPegawai::route('/create'),
            'edit' => Pages\EditUserPegawai::route('/{record}/edit'),
        ];
    }
}
