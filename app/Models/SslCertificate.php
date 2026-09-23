<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SslCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'investigation_id',
        'subject_cn',
        'subject_org',
        'issuer_cn',
        'issuer_org',
        'san_list',
        'valid_from',
        'valid_until',
        'is_valid',
        'tls_version',
        'cipher',
        'signature_algorithm',
        'public_key_bits',
        'cert_chain',
        'ct_status',
    ];

    protected function casts(): array
    {
        return [
            'san_list' => 'array',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'is_valid' => 'boolean',
            'cert_chain' => 'array',
            'public_key_bits' => 'integer',
        ];
    }

    public function investigation(): BelongsTo
    {
        return $this->belongsTo(Investigation::class);
    }
}
