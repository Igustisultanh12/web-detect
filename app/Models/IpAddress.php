<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'investigation_id',
        'ip_address',
        'ip_version',
        'is_cdn_or_proxy',
        'cdn_provider',
        'reverse_dns',
    ];

    protected function casts(): array
    {
        return [
            'is_cdn_or_proxy' => 'boolean',
        ];
    }

    public function investigation(): BelongsTo
    {
        return $this->belongsTo(Investigation::class);
    }
}
