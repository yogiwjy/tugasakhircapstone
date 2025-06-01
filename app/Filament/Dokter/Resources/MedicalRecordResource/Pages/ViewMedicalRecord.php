<?php
namespace App\Filament\Dokter\Resources\MedicalRecordResource\Pages;

use App\Filament\Dokter\Resources\MedicalRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMedicalRecord extends ViewRecord
{
    protected static string $resource = MedicalRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}