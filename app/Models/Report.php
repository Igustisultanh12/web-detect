<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'investigation_id',
        'report_number',
        'title',
        'format',
        'file_path',
        'file_size',
        'sha256',
        'generated_by',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'generated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }

            if (empty($model->report_number) && $model->investigation) {
                $model->report_number = $model->investigation->investigation_code . '-REP';
            }
        });
    }

    public function investigation(): BelongsTo
    {
        return $this->belongsTo(Investigation::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
