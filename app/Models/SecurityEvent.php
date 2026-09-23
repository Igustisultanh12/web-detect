<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'event_type',
        'severity',
        'description',
        'ip_address',
        'user_agent',
        'user_id',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function fire(string $eventType, string $description, string $severity = 'INFO', ?array $metadata = null, ?int $userId = null): self
    {
        return static::create([
            'event_type' => $eventType,
            'severity' => $severity,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => $userId ?? auth()->id(),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
