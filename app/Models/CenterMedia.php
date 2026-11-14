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
        'title',
        'source_rights',
        'rights_certified_at',
    ];

    protected function casts(): array
    {
        return ['rights_certified_at' => 'datetime'];
    }

    /** Ce que la mediatheque renvoie au navigateur pour un fichier. */
    public function fiche(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'url' => $this->url,
            'name' => $this->original_name,
            'title' => $this->title ?: $this->original_name,
            'source_rights' => $this->source_rights,
            'rights_certified_at' => $this->rights_certified_at?->toIso8601String(),
            'size' => $this->size,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

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
