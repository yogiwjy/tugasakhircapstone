<?php
namespace App\Filament\Dokter\Resources\QueueResource\Pages;

use App\Filament\Dokter\Resources\QueueResource;
use Filament\Resources\Pages\ListRecords;

class ListQueues extends ListRecords
{
    protected static string $resource = QueueResource::class;
}