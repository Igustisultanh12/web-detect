<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TakedownFollowUp extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'takedown_case_id',
        'user_id',
        'channel',
        'direction',
        'ticket_number',
        'subject',
        'message',
        'provider_status',
        'attachment_path',
        'attachment_sha256',
        'follow_up_date',
        'next_action',
    ];

    protected function casts(): array
    {
        return [
            'follow_up_date' => 'datetime',
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

    public function takedownCase(): BelongsTo
    {
        return $this->belongsTo(TakedownCase::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
