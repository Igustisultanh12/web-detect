<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsnRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'investigation_id',
        'ip_address',
        'asn',
        'asn_org',
        'bgp_prefix',
        'registry',
    ];

    public function investigation(): BelongsTo
    {
        return $this->belongsTo(Investigation::class);
    }
}
