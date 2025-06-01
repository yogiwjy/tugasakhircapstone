<?php
namespace App\Filament\Dokter\Resources\PatientResource\Pages;

use App\Filament\Dokter\Resources\PatientResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPatient extends EditRecord
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}