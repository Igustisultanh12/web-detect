<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TakedownProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'type',
        'website',
        'abuse_email',
        'abuse_url',
        'api_endpoint',
        'report_types',
        'requirements',
        'sla_hours',
        'integration_status',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'report_types' => 'array',
            'sla_hours' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function takedownCases(): HasMany
    {
        return $this->hasMany(TakedownCase::class, 'provider_id');
    }
}
