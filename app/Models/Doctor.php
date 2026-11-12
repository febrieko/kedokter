<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'sip_number',
        'specialty',
        'avg_minutes_per_patient',
        'is_active',
    ];

    protected $casts = [
        'avg_minutes_per_patient' => 'integer',
        'is_active' => 'boolean',
    ];

    public function clinics(): BelongsToMany
    {
        return $this->belongsToMany(Clinic::class, 'clinic_doctors')
            ->withPivot('room_number', 'is_active')
            ->withTimestamps();
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class);
    }
}

