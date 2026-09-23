<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TakedownCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'case_number',
        'investigation_id',
        'provider_id',
        'target_domain',
        'target_url',
        'target_ip',
        'category',
        'allegation_summary',
        'legal_or_policy_basis',
        'evidence_summary',
        'selected_evidence_ids',
        'provider_type',
        'provider_name',
        'provider_contact',
        'external_reference_number',
        'submitted_at',
        'acknowledged_at',
        'last_follow_up_at',
        'next_follow_up_at',
        'resolved_at',
        'status',
        'priority',
        'assigned_to',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'selected_evidence_ids' => 'array',
            'submitted_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'last_follow_up_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }

            if (empty($model->case_number)) {
                $year = date('Y');
                $count = static::whereYear('created_at', $year)->count() + 1;
                $model->case_number = sprintf('TKD-%s-%06d', $year, $count);
            }
        });
    }

    public function investigation(): BelongsTo
    {
        return $this->belongsTo(Investigation::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(TakedownProvider::class, 'provider_id');
    }

    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(TakedownFollowUp::class)->orderByDesc('created_at');
    }

    public function defensiveActions(): HasMany
    {
        return $this->hasMany(DefensiveAction::class);
    }

    public function incidentChecklists(): HasMany
    {
        return $this->hasMany(IncidentChecklist::class)->orderBy('step_number');
    }

    /**
     * Check if follow-up is overdue.
     */
    public function isFollowUpOverdue(): bool
    {
        if (in_array($this->status, ['RESOLVED', 'CLOSED', 'ACTION_TAKEN', 'REJECTED'])) {
            return false;
        }

        return $this->next_follow_up_at && $this->next_follow_up_at->isPast();
    }
}
