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
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('medical_record_number')
                    ->label('No. Rekam Medis')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required(),
                Forms\Components\DatePicker::make('birth_date')
                    ->label('Tanggal Lahir')
                    ->required(),
                Forms\Components\Select::make('gender')
                    ->label('Jenis Kelamin')
                    ->options([
                        'male' => 'Laki-laki',
                        'female' => 'Perempuan',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('address')
                    ->label('Alamat')
                    ->required(),
                Forms\Components\TextInput::make('phone')
                    ->label('No. Telepon'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('medical_record_number')
                    ->label('No. RM'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Pasien'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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