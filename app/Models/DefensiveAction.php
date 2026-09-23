<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DefensiveAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'action_code',
        'investigation_id',
        'takedown_case_id',
        'rule_type',
        'target_type',
        'target_value',
        'title',
        'description',
        'scope',
        'rule_payload',
        'status',
        'recommended_by',
        'reviewed_by',
        'approved_by',
        'rejection_reason',
        'approved_at',
        'deployed_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'deployed_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }

            if (empty($model->action_code)) {
                $year = date('Y');
                $count = static::whereYear('created_at', $year)->count() + 1;
                $model->action_code = sprintf('DEF-%s-%06d', $year, $count);
            }
        });
    }

    public function investigation(): BelongsTo
    {
        return $this->belongsTo(Investigation::class);
    }

    public function takedownCase(): BelongsTo
    {
        return $this->belongsTo(TakedownCase::class);
    }

    public function recommender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recommended_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(IncidentChecklist::class);
    }
}
