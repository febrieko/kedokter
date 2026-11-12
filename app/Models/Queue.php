<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Queue extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_WAITING = 'WAITING';
    public const STATUS_CALLED = 'CALLED';
    public const STATUS_IN_SERVICE = 'IN_SERVICE';
    public const STATUS_SKIPPED = 'SKIPPED';
    public const STATUS_DONE = 'DONE';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const SOURCE_WHATSAPP = 'WHATSAPP';
    public const SOURCE_MANUAL = 'MANUAL';

    protected $fillable = [
        'clinic_id',
        'doctor_id',
        'doctor_schedule_id',
        'patient_id',
        'queue_date',
        'number',
        'status',
        'doctor_notes',
        'source',
        'call_count',
        'last_called_at',
        'reminder_h1_sent_at',
        'near_turn_sent_at',
        'registered_at',
        'called_at',
        'started_at',
        'finished_at',
        'created_by',
    ];

    protected $casts = [
        'queue_date' => 'date:Y-m-d',
        'number' => 'integer',
        'call_count' => 'integer',
        'last_called_at' => 'datetime',
        'reminder_h1_sent_at' => 'datetime',
        'near_turn_sent_at' => 'datetime',
        'registered_at' => 'datetime',
        'called_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(DoctorSchedule::class, 'doctor_schedule_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canBeCalled(): bool
    {
        return in_array($this->status, [self::STATUS_WAITING, self::STATUS_SKIPPED, self::STATUS_CALLED]);
    }
}
