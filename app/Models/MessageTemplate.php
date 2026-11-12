<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageTemplate extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'clinic_id',
        'key',
        'body',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * Render message template with dynamic variables.
     */
    public function render(array $data): string
    {
        $message = $this->body;
        foreach ($data as $key => $val) {
            $message = str_replace('{' . $key . '}', (string) $val, $message);
        }
        return $message;
    }
}
