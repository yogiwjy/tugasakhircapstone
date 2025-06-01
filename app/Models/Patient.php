<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    protected $fillable = [
        'medical_record_number',
        'name',
        'birth_date',
        'gender',
        'address',
        'phone',
        'emergency_contact',
        'blood_type',
        'allergies',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class);
    }

    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class);
    }

    public function getAgeAttribute(): int
    {
        return $this->birth_date->diffInYears(now());
    }

    public function getGenderLabelAttribute(): string
    {
        return $this->gender === 'male' ? 'Laki-laki' : 'Perempuan';
    }
}