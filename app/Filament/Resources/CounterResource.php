<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CounterResource\Pages;
use App\Filament\Resources\CounterResource\RelationManagers;
use App\Models\Counter;
use App\Services\QueueService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Livewire\Notifications;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('service_id')
                    ->required()
                    ->relationship('service', 'name'),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('name')
                ->label('Nama')
                ->searchable(),
            Tables\Columns\TextColumn::make('service.name')
                ->label('Layanan')
                ->sortable(),
            Tables\Columns\TextColumn::make('activeQueue.number')
                ->label('Nomor Antrian Saat ini')
                ->searchable(),
            Tables\Columns\TextColumn::make('activeQueue.status')
                ->label('Status Antrian')
                ->sortable(),
            Tables\Columns\IconColumn::make('is_active')
                ->label('Status Aktif')
                ->boolean(),
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('updated_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            //
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
            self::getCallNextQueueAction(),
            self::getServeQueueAction(),
            self::getFinishQueueAction(),
            self::getCancelQueueAction(),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ])
        ->poll("3s");
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
        ->button()
        ->visible(fn(Counter $record) => $record->hasNextQueue)
        ->action(function (Counter $record, $livewire) {
            $nextQueue = app(QueueService::class)->callNextQueue($record->id);

            if (!$nextQueue) {
                Notification::make()
                    ->title('No queue Available')
                    ->danger()
                    ->send();

                $livewire->dispatch('queue-called', 'Tidak ada antrean saat ini di ' . $record->name);

                return;
            }

            $livewire->dispatch('queue-called', 'Nomor Antrian ' . $nextQueue->number . ' Segera Ke  ' . $record->name);
        })
        ->label('Panggil')
        ->icon('heroicon-o-speaker-wave');
}


private static function getServeQueueAction(): Action
{
    return Action::make('serve')
        ->label('Layani')
        ->button()
        ->color('success')
        ->icon('heroicon-o-check-circle')
        ->action(function (Counter $record) {
            app(QueueService::class)->serveQueue($record->activeQueue);
        })
        ->requiresConfirmation()
        ->visible(fn(Counter $record) => $record->is_available && $record->activeQueue);
}

private static function getFinishQueueAction(): Action
{
        return Action::make('FinishQueue')
        ->label('selesai')
        ->button()
        ->color('success')
        ->icon('heroicon-o-check')
        ->action(function (Counter $record) {
            app(QueueService::class)->finishQueue($record->activeQueue);
        })
        ->requiresConfirmation()
        ->visible(fn(Counter $record) => $record->activeQueue?->status === 'serving');
}

private static function getCancelQueueAction(): Action
{
        return Action::make('CancelQueue')
        ->label('Batalkan')
        ->button()
        ->color('danger')
        ->icon('heroicon-o-x-circle')
        ->action(function (Counter $record) {
            app(QueueService::class)->cancelQueue($record->activeQueue);
        })
        ->requiresConfirmation()
        ->visible(fn(Counter $record) => $record->is_available && $record->activeQueue);
}
}