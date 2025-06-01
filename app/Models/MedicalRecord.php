<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalRecord extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id', 
        'queue_id',
        'chief_complaint',
        'history_of_present_illness',
        'physical_examination',
        'vital_signs',
        'diagnosis',
        'treatment_plan',
        'prescription',
        'additional_notes',
        'follow_up_date',
    ];

    protected $casts = [
        'follow_up_date' => 'date',
        'vital_signs' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class);
    }
}