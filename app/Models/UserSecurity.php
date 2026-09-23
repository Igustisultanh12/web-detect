<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSecurity extends Model
{
    use HasFactory;

    protected $table = 'user_security';

    protected $fillable = [
        'user_id',
        'totp_secret',
        'google_id',
        'google_email',
        'passkey_credential_id',
        'passkey_public_key',
        'recovery_codes',
        'failed_login_attempts',
        'locked_until',
    ];

    protected $hidden = [
        'totp_secret',
        'recovery_codes',
        'passkey_public_key',
    ];

    protected function casts(): array
    {
        return [
            'totp_secret' => 'encrypted',
            'recovery_codes' => 'encrypted:array',
            'locked_until' => 'datetime',
            'failed_login_attempts' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
