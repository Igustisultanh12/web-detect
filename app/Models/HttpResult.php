<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HttpResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'investigation_id',
        'http_status',
        'https_available',
        'final_url',
        'redirect_chain',
        'server_header',
        'content_type',
        'content_length',
        'compression',
        'hsts_header',
        'csp_header',
        'x_frame_options',
        'x_content_type_options',
        'referrer_policy',
        'permissions_policy',
        'cookies_data',
        'security_score',
        'security_notes',
        'raw_headers',
    ];

    protected function casts(): array
    {
        return [
            'http_status' => 'integer',
            'https_available' => 'boolean',
            'redirect_chain' => 'array',
            'content_length' => 'integer',
            'cookies_data' => 'array',
            'security_score' => 'integer',
            'security_notes' => 'array',
            'raw_headers' => 'array',
        ];
    }

    public function investigation(): BelongsTo
    {
        return $this->belongsTo(Investigation::class);
    }
}
