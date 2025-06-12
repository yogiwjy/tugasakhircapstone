<?php
namespace App\Filament\Dokter\Resources\QueueResource\Pages;

use App\Filament\Dokter\Resources\QueueResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQueues extends ListRecords
{
    protected static string $resource = QueueResource::class;
    protected static ?string $title = 'Kelola Antrian';

    protected function getHeaderActions(): array
    {
        return [
            // Test Audio Action
            Actions\Action::make('test_audio')
                ->label('🧪 Test Audio')
                ->color('info')
                ->action(function () {
                    $this->dispatch('queue-called', 'Test audio dari panel dokter berhasil');
                }),
                
            // Initialize Audio Action
            Actions\Action::make('init_audio')
                ->label('🔊 Aktifkan Audio')
                ->color('warning')
                ->action(function () {
                    $this->dispatch('init-audio');
                }),
                
            // Audio Status Action
            Actions\Action::make('audio_status')
                ->label('📊 Audio Status')
                ->color('success')
                ->action(function () {
                    $this->dispatch('show-audio-status');
                }),
        ];
    }
}