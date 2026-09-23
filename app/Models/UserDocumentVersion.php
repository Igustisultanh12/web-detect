<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDocumentVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'version',
        'file_path',
        'sha256',
        'uploaded_by',
        'uploaded_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(UserDocument::class, 'document_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
