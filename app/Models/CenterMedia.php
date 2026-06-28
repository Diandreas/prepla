<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CenterMedia extends Model
{
    protected $table = 'center_media';

    protected $fillable = [
        'center_id',
        'uploaded_by',
        'type',
        'path',
        'original_name',
        'mime',
        'size',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(LanguageCenter::class, 'center_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** Public URL served from storage/app/public via the storage symlink. */
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
