<?php
namespace App\Filament\Dokter\Resources\PatientResource\Pages;

use App\Filament\Dokter\Resources\PatientResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePatient extends CreateRecord
{

    protected static ?string $title = 'Buat Pasien';

    protected static string $resource = PatientResource::class;
}