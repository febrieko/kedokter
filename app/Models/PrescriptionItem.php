<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'prescription_id',
        'medicine_name',
        'dosage',
        'form',
        'frequency',
        'quantity',
        'instructions',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }
}
