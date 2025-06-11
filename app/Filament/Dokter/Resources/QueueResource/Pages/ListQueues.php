<?php
namespace App\Filament\Dokter\Resources\QueueResource\Pages;

use App\Filament\Dokter\Resources\QueueResource;
use Filament\Resources\Pages\ListRecords;

class ListQueues extends ListRecords
{
    protected static string $resource = QueueResource::class;
    protected static ?string $title = 'Kelola Antrian';

    protected function getHeaderActions(): array
    {
        return [
            // Kosong - tidak ada action buttons
        ];
    }
}