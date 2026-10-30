<?php

namespace App\Filament\Resources\PetaBlockModelResource\Pages;

use App\Filament\Resources\PetaBlockModelResource;
use App\Models\PetaBlockModel;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPetaBlockModels extends ListRecords
{
    protected static string $resource = PetaBlockModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('konversi_semua')
                ->label('Konversi Semua')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Konversi semua peta yang belum selesai?')
                ->modalDescription(fn() => PetaBlockModel::whereIn('status', ['pending', 'gagal'])->count()
                    . ' peta (status pending/gagal) akan dimasukkan ke antrean dan diproses satu per satu. Setiap peta bisa memakan beberapa menit.')
                ->action(fn() => PetaBlockModelResource::antrekan(
                    PetaBlockModel::whereIn('status', ['pending', 'gagal'])->orderBy('id')->get()
                )),
            Actions\CreateAction::make(),
        ];
    }
}
