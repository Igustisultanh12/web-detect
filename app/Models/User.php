<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'nrp',
        'rank_id',
        'position_id',
        'unit_id',
        'phone',
        'whatsapp_number',
        'email',
        'email_verified_at',
        'password',
        'profile_photo_path',
        'status',
        'active_from',
        'activated_by_admin_at',
        'activated_by_admin_id',
        'deactivated_at',
        'deactivated_by',
        'deactivation_reason',
        'two_factor_enabled',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
            'active_from' => 'datetime',
            'activated_by_admin_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });
    }

    public function rank(): BelongsTo
    {
        return $this->belongsTo(Rank::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function security(): HasOne
    {
        return $this->hasOne(UserSecurity::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(UserDocument::class);
    }

    public function userSessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function investigations(): HasMany
    {
        return $this->hasMany(Investigation::class);
    }

    public function takedownCases(): HasMany
    {
        return $this->hasMany(TakedownCase::class, 'assigned_to');
    }

    public function defensiveActions(): HasMany
    {
        return $this->hasMany(DefensiveAction::class, 'recommended_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function activatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by_admin_id');
    }

    public function deactivatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deactivated_by');
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];
        return $this->roles->pluck('name')->intersect($roles)->isNotEmpty();
    }

    public function hasPermission(string $permissionName): bool
    {
        if ($this->hasRole('super_admin')) {
            return true;
        }

        foreach ($this->roles as $role) {
            if ($role->hasPermission($permissionName)) {
                return true;
            }
        }

        return false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(['super_admin', 'admin']);
    }

    public function isInvestigator(): bool
    {
        return $this->hasRole(['super_admin', 'admin', 'investigator']);
    }

    public function isViewer(): bool
    {
        return $this->hasRole('viewer');
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    public function assignRole(Role|string $role): void
    {
        $roleModel = is_string($role)
            ? Role::firstOrCreate(['name' => $role], ['label' => strtoupper(str_replace('_', ' ', $role))])
            : $role;

        if (!$this->roles()->where('roles.id', $roleModel->id)->exists()) {
            $this->roles()->attach($roleModel->id);
        }
    }

    public function getMaskedWhatsappAttribute(): ?string
    {
        if (!$this->whatsapp_number) {
            return null;
        }

        $num = $this->whatsapp_number;
        $len = strlen($num);
        if ($len <= 6) {
            return $num;
        }

        return substr($num, 0, 5) . str_repeat('*', max(3, $len - 7)) . substr($num, -2);
    }
}
