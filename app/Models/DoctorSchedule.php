<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DoctorSchedule extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'clinic_id',
        'doctor_id',
        'weekday',
        'start_time',
        'end_time',
        'quota',
        'is_active',
    ];

    protected $casts = [
        'weekday' => 'integer',
        'quota' => 'integer',
        'is_active' => 'boolean',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function overrides(): HasMany
    {
        return $this->hasMany(ScheduleOverride::class);
    }

    public function stats(): HasMany
    {
        return $this->hasMany(ScheduleStat::class);
    }

    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class);
    }

    /**
     * Get day name in Indonesian (0 = Ahad, 1 = Senin, ...)
     */
    public function getDayNameAttribute(): string
    {
        $days = [
            0 => 'Ahad',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];

        return $days[$this->weekday] ?? 'Unknown';
    }
}
