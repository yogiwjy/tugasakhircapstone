<?php
namespace App\Filament\Dokter\Resources\MedicalRecordResource\Pages;

use App\Filament\Dokter\Resources\MedicalRecordResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMedicalRecord extends CreateRecord
{
    protected static string $resource = MedicalRecordResource::class;

    protected static ?string $title = 'Rekam Medis';
}