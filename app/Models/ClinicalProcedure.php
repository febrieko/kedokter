<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalProcedure extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'encounter_id',
        'clinic_id',
        'patient_id',
        'doctor_id',
        'procedure_name',
        'icd9cm_code',
        'notes',
        'cost',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
