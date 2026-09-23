<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Evidence extends Model
{
    use HasFactory;

    protected $table = 'evidences';

    protected $fillable = [
        'uuid',
        'investigation_id',
        'evidence_code',
        'type',
        'source',
        'raw_data',
        'parsed_data',
        'sha256',
        'collected_at',
        'created_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'parsed_data' => 'array',
            'collected_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }

            if (empty($model->evidence_code)) {
                $count = static::count() + 1;
                $model->evidence_code = sprintf('EV-%06d', $count);
            }

            if (empty($model->sha256) && !empty($model->raw_data)) {
                $model->sha256 = hash('sha256', $model->raw_data);
            }
        });
    }

    public function investigation(): BelongsTo
    {
        return $this->belongsTo(Investigation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(EvidenceVersion::class, 'evidence_id')->orderByDesc('version');
    }
}
