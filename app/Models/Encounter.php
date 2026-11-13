<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Encounter extends Model
{
    use HasFactory, HasUuids;

    public const TYPE_OUTPATIENT = 'OUTPATIENT';
    public const TYPE_EMERGENCY = 'EMERGENCY';
    public const TYPE_TELECONSULTATION = 'TELECONSULTATION';

    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_LOCKED = 'LOCKED';

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'doctor_id',
        'queue_id',
        'encounter_date',
        'start_time',
        'end_time',
        'encounter_type',
        'status',
        'locked_at',
        'locked_by',
        'created_by',
    ];

    protected $casts = [
        'encounter_date' => 'date:Y-m-d',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'locked_at' => 'datetime',
    ];

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

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class);
    }

    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function medicalRecord(): HasOne
    {
        return $this->hasOne(MedicalRecord::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function clinicalProcedures(): HasMany
    {
        return $this->hasMany(ClinicalProcedure::class);
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }
}

