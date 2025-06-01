<?php
namespace App\Filament\Dokter\Resources\PatientResource\Pages;

use App\Filament\Dokter\Resources\PatientResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPatient extends ViewRecord
{

    protected static ?string $title = 'Lihat Pasien';
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}