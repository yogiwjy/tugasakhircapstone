<?php

namespace App\Filament\Dokter\Resources;

use App\Filament\Dokter\Resources\QueueResource\Pages;
use App\Models\Queue;
use App\Services\QueueService;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class QueueResource extends Resource
{
    protected static ?string $model = Queue::class;
    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $navigationLabel = 'Kelola Antrian';
    protected static ?string $modelLabel = 'Antrian';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('Nomor Antrian')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Layanan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('patient.name')
                    ->label('Nama Pasien')
                    ->default('Ahmad Suryadi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status Antrian')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'waiting' => 'warning',
                        'serving' => 'success',
                        'finished' => 'primary',
                        'canceled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Daftar')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('called_at')
                    ->label('Waktu Dipanggil')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Action::make('call')
                    ->label('Panggil')
                    ->icon('heroicon-o-megaphone')
                    ->color('warning')
                    ->visible(fn (Queue $record) => $record->status === 'waiting')
                    ->action(function (Queue $record, $livewire) {
                        $record->update([
                            'status' => 'serving',
                            'called_at' => now(),
                        ]);

                        $livewire->dispatchBrowserEvent('queue-called', [
                            'message' => "Nomor antrian {$record->number} silakan menuju ruang periksa",
                        ]);

                        Notification::make()
                            ->title("Antrian {$record->number} berhasil dipanggil!")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Panggil Antrian')
                    ->modalDescription(fn (Queue $record) => "Apakah Anda yakin ingin memanggil antrian {$record->number}?"),

                Action::make('serve')
                    ->label('Layani')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Queue $record) => $record->status === 'serving')
                    ->action(fn (Queue $record) => redirect()->route('filament.dokter.resources.medical-records.create', [
                        'queue_id' => $record->id,
                        'patient_id' => $record->patient_id,
                    ])),

                Action::make('finish')
                    ->label('Selesai')
                    ->icon('heroicon-o-check')
                    ->color('primary')
                    ->visible(fn (Queue $record) => $record->status === 'serving')
                    ->action(function (Queue $record) {
                        app(QueueService::class)->finishQueue($record);
                        Notification::make()
                            ->title("Antrian {$record->number} selesai dilayani")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Selesaikan Antrian'),

                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('3s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQueues::route('/'),
            'view' => Pages\ViewQueue::route('/{record}'),
        ];
    }
}
