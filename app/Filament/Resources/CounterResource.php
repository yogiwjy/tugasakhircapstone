<?php
namespace App\Filament\Resources;

use App\Filament\Resources\CounterResource\Pages;
use App\Models\Counter;
use App\Services\QueueService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class CounterResource extends Resource
{
    protected static ?string $model = Counter::class;
    protected static ?string $label = "Loket";
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Administrasi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Loket')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Contoh: Loket 1'),
                    
                Forms\Components\Select::make('service_id')
                    ->label('Layanan')
                    ->required()
                    ->relationship('service', 'name')
                    ->searchable()
                    ->preload(),
                    
                Forms\Components\Toggle::make('is_active')
                    ->label('Status Aktif')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Loket')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Layanan')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('activeQueue.number')
                    ->label('Antrian Saat Ini')
                    ->placeholder('Tidak ada')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                    
                Tables\Columns\TextColumn::make('activeQueue.status')
                    ->label('Status Antrian')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'waiting' => 'Menunggu',
                        'serving' => 'Dilayani',
                        'finished' => 'Selesai',
                        'canceled' => 'Dibatalkan',
                        null => 'Kosong',
                        default => $state,
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'waiting' => 'warning',
                        'serving' => 'success',
                        'finished' => 'primary',
                        'canceled' => 'danger',
                        null => 'gray',
                        default => 'gray',
                    }),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status Loket')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),
                    
                Tables\Filters\SelectFilter::make('service')
                    ->label('Layanan')
                    ->relationship('service', 'name'),
            ])
            ->actions([
                self::getCallNextQueueAction(),
                self::getServeQueueAction(),
                self::getFinishQueueAction(),
                self::getCancelQueueAction(),
                
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                ->label('Kelola')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktifkan')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => true]);
                            }
                            
                            Notification::make()
                                ->title('Loket berhasil diaktifkan')
                                ->success()
                                ->send();
                        }),
                        
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Nonaktifkan')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => false]);
                            }
                            
                            Notification::make()
                                ->title('Loket berhasil dinonaktifkan')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->poll("5s")
            ->striped()
            ->paginated([10, 25, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCounters::route('/'),
        ];
    }

    private static function getCallNextQueueAction()
    {
        return Action::make('callNextQueue')
            ->label('Panggil')
            ->icon('heroicon-o-speaker-wave')
            ->color('warning')
            ->size('sm')
            ->button()
            ->visible(fn(Counter $record) => $record->hasNextQueue && $record->is_active)
            ->action(function (Counter $record, $livewire) {
                try {
                    $nextQueue = app(QueueService::class)->callNextQueue($record->id);

                    if (!$nextQueue) {
                        Notification::make()
                            ->title('Tidak ada antrian tersedia')
                            ->body('Tidak ada antrian yang menunggu di ' . $record->name)
                            ->warning()
                            ->send();

                        // Dispatch audio untuk tidak ada antrian
                        $livewire->dispatch('queue-called', 'Tidak ada antrean saat ini di ' . $record->name);
                        return;
                    }

                    // Update status antrian
                    $nextQueue->update([
                        'status' => 'serving',
                        'called_at' => now(),
                    ]);

                    // Kirim notifikasi sukses
                    Notification::make()
                        ->title("Antrian {$nextQueue->number} berhasil dipanggil!")
                        ->body("Mengarahkan ke {$record->name}")
                        ->success()
                        ->duration(5000)
                        ->send();

                    // Dispatch event audio dengan pesan yang jelas
                    $message = "Nomor antrian {$nextQueue->number} segera ke {$record->name}";
                    $livewire->dispatch('queue-called', $message);
                    
                    // Set session sebagai fallback
                    session()->flash('queue_called', [
                        'number' => $nextQueue->number,
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
            ->modalHeading('Panggil Antrian Berikutnya')
            ->modalDescription(fn(Counter $record) => "Panggil antrian berikutnya untuk {$record->name}?")
            ->modalSubmitActionLabel('Ya, Panggil')
            ->modalCancelActionLabel('Batal');
    }

    private static function getServeQueueAction(): Action
    {
        return Action::make('serve')
            ->label('Layani')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->size('sm')
            ->button()
            ->visible(fn(Counter $record) => $record->is_active && $record->activeQueue && $record->activeQueue->status === 'waiting')
            ->action(function (Counter $record) {
                try {
                    app(QueueService::class)->serveQueue($record->activeQueue);
                    
                    Notification::make()
                        ->title("Antrian {$record->activeQueue->number} mulai dilayani")
                        ->success()
                        ->send();
                        
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error')
                        ->body('Gagal melayani antrian: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            })
            ->requiresConfirmation()
            ->modalHeading('Mulai Melayani')
            ->modalDescription(fn(Counter $record) => "Mulai melayani antrian {$record->activeQueue?->number}?");
    }

    private static function getFinishQueueAction(): Action
    {
        return Action::make('finishQueue')
            ->label('Selesai')
            ->icon('heroicon-o-check')
            ->color('primary')
            ->size('sm')
            ->button()
            ->visible(fn(Counter $record) => $record->activeQueue?->status === 'serving')
            ->action(function (Counter $record) {
                try {
                    app(QueueService::class)->finishQueue($record->activeQueue);
                    
                    Notification::make()
                        ->title("Antrian {$record->activeQueue->number} selesai dilayani")
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
            ->modalDescription(fn(Counter $record) => "Selesaikan antrian {$record->activeQueue?->number}?");
    }

    private static function getCancelQueueAction(): Action
    {
        return Action::make('cancelQueue')
            ->label('Batalkan')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->size('sm')
            ->button()
            ->visible(fn(Counter $record) => $record->is_active && $record->activeQueue && in_array($record->activeQueue->status, ['waiting', 'serving']))
            ->action(function (Counter $record) {
                try {
                    app(QueueService::class)->cancelQueue($record->activeQueue);
                    
                    Notification::make()
                        ->title("Antrian {$record->activeQueue->number} dibatalkan")
                        ->warning()
                        ->send();
                        
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error')
                        ->body('Gagal membatalkan antrian: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            })
            ->requiresConfirmation()
            ->modalHeading('Batalkan Antrian')
            ->modalDescription(fn(Counter $record) => "Batalkan antrian {$record->activeQueue?->number}?");
    }
}