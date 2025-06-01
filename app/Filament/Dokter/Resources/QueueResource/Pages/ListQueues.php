<?php
namespace App\Filament\Dokter\Resources\QueueResource\Pages;

use App\Filament\Dokter\Resources\QueueResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListQueues extends ListRecords
{
    protected static string $resource = QueueResource::class;
    protected static ?string $title = 'Kelola Antrian';

    public function getHeader(): ?View
    {
        return view('filament.dokter.queue.header');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}