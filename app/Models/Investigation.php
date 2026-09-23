<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Investigation extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'investigation_code',
        'user_id',
        'target_url',
        'target_domain',
        'category',
        'reason',
        'priority',
        'tags',
        'status',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }

            if (empty($model->investigation_code)) {
                $year = date('Y');
                $count = static::whereYear('created_at', $year)->count() + 1;
                $model->investigation_code = sprintf('WG-%s-%06d', $year, $count);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function domainRecord(): HasOne
    {
        return $this->hasOne(DomainRecord::class);
    }

    public function dnsRecords(): HasMany
    {
        return $this->hasMany(DnsRecord::class);
    }

    public function ipAddresses(): HasMany
    {
        return $this->hasMany(IpAddress::class);
    }

    public function asnRecords(): HasMany
    {
        return $this->hasMany(AsnRecord::class);
    }

    public function hostingRecords(): HasMany
    {
        return $this->hasMany(HostingRecord::class);
    }

    public function sslCertificate(): HasOne
    {
        return $this->hasOne(SslCertificate::class);
    }

    public function httpResult(): HasOne
    {
        return $this->hasOne(HttpResult::class);
    }

    public function technologies(): HasMany
    {
        return $this->hasMany(Technology::class);
    }

    public function subdomains(): HasMany
    {
        return $this->hasMany(Subdomain::class);
    }

    public function reputationResults(): HasMany
    {
        return $this->hasMany(ReputationResult::class);
    }

    public function screenshots(): HasMany
    {
        return $this->hasMany(Screenshot::class);
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(Evidence::class);
    }

    public function timeline(): HasMany
    {
        return $this->hasMany(InvestigationTimeline::class)->orderBy('created_at', 'asc');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function addTimeline(string $action, string $description, string $eventType = 'INFO', ?array $metadata = null): InvestigationTimeline
    {
        return $this->timeline()->create([
            'action' => $action,
            'event_type' => $eventType,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
