<?php

namespace App\Filament\Resources\BlockModelResource\Pages;

use App\Filament\Resources\BlockModelResource;
use App\Imports\BlockModelImport;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListBlockModels extends ListRecords
{
    protected static string $resource = BlockModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import')
                ->label('Import Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('File Excel')
                        ->acceptedFileTypes([
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->required()
                        ->disk('local')
                        ->directory('imports'),
                ])
                ->action(function (array $data) {
                    $path = Storage::disk('local')->path($data['file']);
                    Excel::import(new BlockModelImport, $path);

                    \Filament\Notifications\Notification::make()
                        ->title('Import dimulai, diproses di background')
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
