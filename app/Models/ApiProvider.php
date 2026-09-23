<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'service_type',
        'is_active',
        'is_default',
        'api_endpoint',
        'api_key',
        'secret_key',
        'extra_config',
    ];

    protected $hidden = [
        'api_key',
        'secret_key',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'api_key' => 'encrypted',
            'secret_key' => 'encrypted',
            'extra_config' => 'encrypted:array',
        ];
    }
}
