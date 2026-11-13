<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Icd10Code extends Model
{
    use HasFactory;

    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name_en',
        'name_id',
        'category',
        'is_common',
    ];

    protected $casts = [
        'is_common' => 'boolean',
    ];

    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class, 'primary_icd10_code', 'code');
    }

    /**
     * Scope helper for searching by code or Indonesian name
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where('code', 'like', "%{$term}%")
            ->orWhere('name_id', 'like', "%{$term}%")
            ->orWhere('name_en', 'like', "%{$term}%");
    }
}

