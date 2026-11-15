<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Le Google Drive d'un centre : ses contenus lourds (videos, audios, PDF) et
 * ses sauvegardes y vivent, pas sur le serveur PrePla. Le jeton de
 * rafraichissement est chiffre en base (APP_KEY) et jamais renvoye au navigateur.
 */
class CenterGoogleDrive extends Model
{
    public const SOUS_DOSSIERS = ['Videos', 'Audios', 'Documents', 'Sauvegardes'];

    protected $fillable = [
        'center_id', 'email', 'refresh_token', 'root_folder_id', 'folders',
        'connected_by', 'connecte_le', 'derniere_sauvegarde_le', 'derniere_sauvegarde_id',
    ];

    protected $hidden = ['refresh_token'];

    protected function casts(): array
    {
        return [
            'refresh_token' => 'encrypted',
            'folders' => 'array',
            'connecte_le' => 'datetime',
            'derniere_sauvegarde_le' => 'datetime',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(LanguageCenter::class, 'center_id');
    }

    /** L'id du sous-dossier (Videos, Audios, Documents, Sauvegardes), sinon la racine. */
    public function dossier(string $nom): ?string
    {
        return $this->folders[$nom] ?? $this->root_folder_id;
    }
}
