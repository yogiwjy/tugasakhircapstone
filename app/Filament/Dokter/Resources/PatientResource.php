<?php
namespace App\Filament\Dokter\Resources;

use App\Filament\Dokter\Resources\PatientResource\Pages;
use App\Models\Patient;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Data Pasien';
    protected static ?string $modelLabel = 'Pasien';
    protected static ?string $pluralModelLabel = 'Pasien';
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('medical_record_number')
                    ->label('No. Rekam Medis')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required(),
                Forms\Components\DatePicker::make('birth_date')
                    ->label('Tanggal Lahir')
                    ->required()
                    ->native(false),
                Forms\Components\Select::make('gender')
                    ->label('Jenis Kelamin')
                    ->options([
                        'male' => 'Laki-laki',
                        'female' => 'Perempuan',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('address')
                    ->label('Alamat')
                    ->required()
                    ->rows(3),
                Forms\Components\TextInput::make('phone')
                    ->label('No. Telepon')
                    ->tel(),
                Forms\Components\TextInput::make('emergency_contact')
                    ->label('Kontak Darurat'),
                Forms\Components\Select::make('blood_type')
                    ->label('Golongan Darah')
                    ->options([
                        'A+' => 'A+',
                        'A-' => 'A-',
                        'B+' => 'B+',
                        'B-' => 'B-',
                        'AB+' => 'AB+',
                        'AB-' => 'AB-',
                        'O+' => 'O+',
                        'O-' => 'O-',
                    ]),
                Forms\Components\Textarea::make('allergies')
                    ->label('Alergi')
                    ->rows(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('medical_record_number')
                    ->label('No. RM')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Pasien')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gender')
                    ->label('Jenis Kelamin')
                    ->formatStateUsing(fn (string $state): string => $state === 'male' ? 'Laki-laki' : 'Perempuan')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'male' ? 'info' : 'success'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->date('d/m/Y')
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
            'index' => Pages\ListPatients::route('/'),
            'create' => Pages\CreatePatient::route('/create'),
            'view' => Pages\ViewPatient::route('/{record}'),
            'edit' => Pages\EditPatient::route('/{record}/edit'),
        ];
    }
}