<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'takedown_case_id',
        'defensive_action_id',
        'step_number',
        'phase',
        'task_name',
        'instructions',
        'is_completed',
        'completed_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
            'step_number' => 'integer',
        ];
    }

    public function takedownCase(): BelongsTo
    {
        return $this->belongsTo(TakedownCase::class);
    }

    public function defensiveAction(): BelongsTo
    {
        return $this->belongsTo(DefensiveAction::class);
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
