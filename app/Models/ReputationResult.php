<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReputationResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'investigation_id',
        'provider_name',
        'status',
        'threat_type',
        'score',
        'checked_at',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'checked_at' => 'datetime',
            'details' => 'array',
        ];
    }

    public function investigation(): BelongsTo
    {
        return $this->belongsTo(Investigation::class);
    }
}
