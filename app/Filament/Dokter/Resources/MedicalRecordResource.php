<?php
namespace App\Filament\Dokter\Resources;

use App\Filament\Dokter\Resources\MedicalRecordResource\Pages;
use App\Models\MedicalRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MedicalRecordResource extends Resource
{
    protected static ?string $model = MedicalRecord::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Rekam Medis';
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('patient_id')
                    ->label('Pasien')
                    ->relationship('patient', 'name')
                    ->required(),
                Forms\Components\Textarea::make('chief_complaint')
                    ->label('Keluhan Utama')
                    ->required(),
                Forms\Components\Textarea::make('diagnosis')
                    ->label('Diagnosis')
                    ->required(),
                Forms\Components\Textarea::make('treatment_plan')
                    ->label('Rencana Pengobatan')
                    ->required(),
                Forms\Components\Hidden::make('doctor_id')
                    ->default(Auth::id()),
                Forms\Components\Select::make('queue_id')
                    ->label('Dari Antrian (Opsional)')
                    ->relationship('queue', 'number')
                    ->searchable()
                    ->preload()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $queue = \App\Models\Queue::find($state);
                            if ($queue && $queue->patient_id) {
                                $set('patient_id', $queue->patient_id);
            }
        }
    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('patient.name')
                    ->label('Nama Pasien'),
                Tables\Columns\TextColumn::make('chief_complaint')
                    ->label('Keluhan')
                    ->limit(50),
                Tables\Columns\TextColumn::make('diagnosis')
                    ->label('Diagnosis')
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->date(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMedicalRecords::route('/'),
            'create' => Pages\CreateMedicalRecord::route('/create'),
            'view' => Pages\ViewMedicalRecord::route('/{record}'),
            'edit' => Pages\EditMedicalRecord::route('/{record}/edit'),
        ];
    }
}