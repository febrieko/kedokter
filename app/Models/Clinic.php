<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clinic extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'address',
        'phone',
        'timezone',
        'whatsapp_provider',
        'whatsapp_config',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'whatsapp_config' => 'encrypted:array',
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'clinic_doctors')
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

    public function patients(): BelongsToMany
    {
        return $this->belongsToMany(Patient::class, 'clinic_patients')
            ->withPivot('medical_record_number')
            ->withTimestamps();
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(MessageTemplate::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

