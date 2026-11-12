<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationState extends Model
{
    use HasFactory;

    protected $primaryKey = 'phone';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'phone',
        'clinic_id',
        'step',
        'context',
        'expires_at',
    ];

    protected $casts = [
        'context' => 'array',
        'expires_at' => 'datetime',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
