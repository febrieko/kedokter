<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleStat extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'clinic_id',
        'doctor_schedule_id',
        'stat_date',
        'patients_served',
        'total_service_minutes',
        'avg_minutes',
    ];

    protected $casts = [
        'stat_date' => 'date',
        'patients_served' => 'integer',
        'total_service_minutes' => 'integer',
        'avg_minutes' => 'float',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(DoctorSchedule::class, 'doctor_schedule_id');
    }
}

