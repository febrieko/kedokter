<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleOverride extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'clinic_id',
        'doctor_schedule_id',
        'override_date',
        'start_time',
        'end_time',
        'quota',
        'is_cancelled',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'override_date' => 'date',
        'quota' => 'integer',
        'is_cancelled' => 'boolean',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(DoctorSchedule::class, 'doctor_schedule_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

