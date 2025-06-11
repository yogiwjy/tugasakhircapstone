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
    protected static ?string $pluralModelLabel = 'Antrian';

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
                    ->searchable()
                    ->weight('bold')
                    ->size('sm'),
                    
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Layanan')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('patient.name')
                    ->label('Nama Pasien')
                    ->default('Pasien Walk-in')
                    ->searchable()
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Status Antrian')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'waiting' => 'Menunggu',
                        'serving' => 'Dilayani',
                        'finished' => 'Selesai',
                        'canceled' => 'Dibatalkan',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'waiting' => 'warning',
                        'serving' => 'success',
                        'finished' => 'primary',
                        'canceled' => 'danger',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Daftar')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since(),
                    
                Tables\Columns\TextColumn::make('called_at')
                    ->label('Waktu Dipanggil')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Belum dipanggil'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'waiting' => 'Menunggu',
                        'serving' => 'Dilayani',
                        'finished' => 'Selesai',
                        'canceled' => 'Dibatalkan',
                    ]),
                    
                Tables\Filters\SelectFilter::make('service')
                    ->label('Layanan')
                    ->relationship('service', 'name'),
                    
                Tables\Filters\Filter::make('created_at')
                    ->label('Hari Ini')
                    ->query(fn ($query) => $query->whereDate('created_at', today()))
                    ->default(),
            ])
            ->actions([
                Action::make('call')
                    ->label('Panggil')
                    ->icon('heroicon-o-megaphone')
                    ->color('warning')
                    ->size('sm')
                    ->visible(fn (Queue $record) => $record->status === 'waiting')
                    ->action(function (Queue $record, $livewire) {
                        try {
                            // Update status antrian
                            $record->update([
                                'status' => 'serving',
                                'called_at' => now(),
                            ]);

                            // Tentukan pesan berdasarkan konteks
                            $serviceName = $record->service->name ?? 'ruang periksa';
                            $message = "Nomor antrian {$record->number} silakan menuju {$serviceName}";

                            // Kirim notifikasi sukses
                            Notification::make()
                                ->title("Antrian {$record->number} berhasil dipanggil!")
                                ->body($message)
                                ->success()
                                ->duration(5000)
                                ->send();

                            // Dispatch event untuk trigger audio
                            $livewire->dispatch('queue-called', $message);
                            
                            // Set session sebagai fallback
                            session()->flash('queue_called', [
                                'number' => $record->number,
                                'message' => $message
                            ]);

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error')
                                ->body('Gagal memanggil antrian: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Panggil Antrian')
                    ->modalDescription(fn (Queue $record) => "Apakah Anda yakin ingin memanggil antrian {$record->number}?")
                    ->modalSubmitActionLabel('Ya, Panggil')
                    ->modalCancelActionLabel('Batal'),

                Action::make('serve')
                    ->label('Buat Rekam Medis')
                    ->icon('heroicon-o-document-plus')
                    ->color('success')
                    ->size('sm')
                    ->visible(fn (Queue $record) => $record->status === 'serving')
                    ->url(fn (Queue $record) => route('filament.dokter.resources.medical-records.create', [
                        'queue_id' => $record->id,
                        'patient_id' => $record->patient_id,
                    ])),

                Action::make('finish')
                    ->label('Selesai')
                    ->icon('heroicon-o-check')
                    ->color('primary')
                    ->size('sm')
                    ->visible(fn (Queue $record) => $record->status === 'serving')
                    ->action(function (Queue $record) {
                        try {
                            app(QueueService::class)->finishQueue($record);
                            
                            Notification::make()
                                ->title("Antrian {$record->number} selesai dilayani")
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error')
                                ->body('Gagal menyelesaikan antrian: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Selesaikan Antrian')
                    ->modalDescription(fn (Queue $record) => "Tandai antrian {$record->number} sebagai selesai?"),

                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->size('sm'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('call_multiple')
                        ->label('Panggil Terpilih')
                        ->icon('heroicon-o-megaphone')
                        ->color('warning')
                        ->action(function ($records, $livewire) {
                            $called = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'waiting') {
                                    $record->update([
                                        'status' => 'serving',
                                        'called_at' => now(),
                                    ]);
                                    
                                    $serviceName = $record->service->name ?? 'ruang periksa';
                                    $message = "Nomor antrian {$record->number} silakan menuju {$serviceName}";
                                    
                                    // Dispatch audio dengan delay
                                    $livewire->dispatch('queue-called', $message);
                                    $called++;
                                    
                                    // Delay between calls
                                    if ($called < count($records)) {
                                        sleep(2);
                                    }
                                }
                            }
                            
                            Notification::make()
                                ->title("{$called} antrian berhasil dipanggil")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Panggil Multiple Antrian')
                        ->modalDescription('Panggil semua antrian yang dipilih? (dengan jeda 2 detik)'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('3s')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQueues::route('/'),
            'view' => Pages\ViewQueue::route('/{record}'),
        ];
    }
}