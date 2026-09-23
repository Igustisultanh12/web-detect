<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Technology extends Model
{
    use HasFactory;

    protected $fillable = [
        'investigation_id',
        'category',
        'name',
        'version',
        'confidence',
        'matched_pattern',
        'icon',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'integer',
        ];
    }

    public function investigation(): BelongsTo
    {
        return $this->belongsTo(Investigation::class);
    }
}
