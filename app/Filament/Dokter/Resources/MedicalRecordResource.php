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
    protected static ?string $modelLabel = 'Rekam Medis';
    protected static ?string $pluralModelLabel = 'Rekam Medis';
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('patient_id')
                    ->label('Pasien')
                    ->relationship('patient', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Textarea::make('chief_complaint')
                    ->label('Keluhan Utama')
                    ->required()
                    ->rows(3),
                Forms\Components\Textarea::make('history_of_present_illness')
                    ->label('Riwayat Penyakit Sekarang')
                    ->rows(3),
                Forms\Components\Textarea::make('physical_examination')
                    ->label('Pemeriksaan Fisik')
                    ->rows(3),
                Forms\Components\TextInput::make('vital_signs')
                    ->label('Tanda Vital')
                    ->placeholder('Tekanan Darah, Nadi, Suhu, dll'),
                Forms\Components\Textarea::make('diagnosis')
                    ->label('Diagnosis')
                    ->required()
                    ->rows(2),
                Forms\Components\Textarea::make('treatment_plan')
                    ->label('Rencana Pengobatan')
                    ->required()
                    ->rows(3),
                Forms\Components\Textarea::make('prescription')
                    ->label('Resep Obat')
                    ->rows(3),
                Forms\Components\Textarea::make('additional_notes')
                    ->label('Catatan Tambahan')
                    ->rows(2),
                Forms\Components\DatePicker::make('follow_up_date')
                    ->label('Tanggal Kontrol'),
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
                    ->label('Nama Pasien')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('chief_complaint')
                    ->label('Keluhan Utama')
                    ->limit(50)
                    ->wrap(),
                Tables\Columns\TextColumn::make('diagnosis')
                    ->label('Diagnosis')
                    ->limit(50)
                    ->wrap(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Pemeriksaan')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat'),
                Tables\Actions\EditAction::make()
                    ->label('Edit'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus'),
                ]),
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