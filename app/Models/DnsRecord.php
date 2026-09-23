<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DnsRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'investigation_id',
        'record_type',
        'host',
        'target',
        'ttl',
        'priority',
        'raw_entry',
    ];

    protected function casts(): array
    {
        return [
            'ttl' => 'integer',
            'priority' => 'integer',
        ];
    }

    public function investigation(): BelongsTo
    {
        return $this->belongsTo(Investigation::class);
    }
}
