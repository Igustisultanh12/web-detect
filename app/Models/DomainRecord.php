<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'investigation_id',
        'domain',
        'tld',
        'registrar',
        'registered_at',
        'expires_at',
        'domain_status',
        'nameservers',
        'dnssec_status',
        'rdap_data',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'expires_at' => 'datetime',
            'domain_status' => 'array',
            'nameservers' => 'array',
            'rdap_data' => 'array',
        ];
    }

    public function investigation(): BelongsTo
    {
        return $this->belongsTo(Investigation::class);
    }
}
