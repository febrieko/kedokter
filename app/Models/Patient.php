<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'nik',
        'name',
        'phone',
        'birth_place',
        'birth_date',
        'gender',
        'address',
        'insurance_type',
        'insurance_number',
        'consent_at',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'consent_at' => 'datetime',
    ];

    public function clinics(): BelongsToMany
    {
        return $this->belongsToMany(Clinic::class, 'clinic_patients')
            ->withPivot('medical_record_number')
            ->withTimestamps();
    }

    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(MessageLog::class);
    }
}

