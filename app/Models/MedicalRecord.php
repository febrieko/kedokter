<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalRecord extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'encounter_id',
        'clinic_id',
        'patient_id',
        'doctor_id',
        'complaint',
        'history_of_present_illness',
        'past_medical_history',
        'allergy_history',
        'systolic',
        'diastolic',
        'heart_rate',
        'respiratory_rate',
        'temperature',
        'weight',
        'height',
        'bmi',
        'physical_examination',
        'primary_icd10_code',
        'secondary_icd10_codes',
        'diagnosis_notes',
        'treatment_plan',
    ];

    protected $casts = [
        'systolic' => 'integer',
        'diastolic' => 'integer',
        'heart_rate' => 'integer',
        'respiratory_rate' => 'integer',
        'temperature' => 'decimal:1',
        'weight' => 'decimal:2',
        'height' => 'decimal:1',
        'bmi' => 'decimal:1',
        'secondary_icd10_codes' => 'array',
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

    public function primaryIcd10(): BelongsTo
    {
        return $this->belongsTo(Icd10Code::class, 'primary_icd10_code', 'code');
    }

    public function addendums(): HasMany
    {
        return $this->hasMany(MedicalRecordAddendum::class)->orderBy('created_at', 'asc');
    }

    /**
     * Helper hitung IMT (Body Mass Index)
     */
    public function calculateBmi(): ?float
    {
        if ($this->weight > 0 && $this->height > 0) {
            $heightInMeters = $this->height / 100;
            return round($this->weight / ($heightInMeters * $heightInMeters), 1);
        }
        return null;
    }

    /**
     * Format Tekanan Darah (e.g. 120/80)
     */
    public function getBloodPressureAttribute(): ?string
    {
        if ($this->systolic && $this->diastolic) {
            return "{$this->systolic}/{$this->diastolic}";
        }
        return null;
    }
}

