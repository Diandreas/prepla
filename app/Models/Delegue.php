<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Une personne qui fait connaitre PrePla (delegue de salle, ambassadeur).
 * Son code rattache les etudiants qu'elle amene, leur donne une reduction, et
 * lui rapporte un pourcentage de chacun de leurs paiements.
 */
class Delegue extends Model
{
    protected $table = 'delegues';

    protected $fillable = ['user_id', 'nom', 'telephone', 'code', 'salle', 'taux', 'reduction', 'prix_mensuel', 'prix_barre', 'actif'];

    /** Les valeurs par defaut de la table, connues du modele des sa creation. */
    protected $attributes = ['taux' => 20, 'reduction' => 10, 'actif' => true];

    protected function casts(): array
    {
        return ['actif' => 'boolean', 'taux' => 'integer', 'reduction' => 'integer', 'prix_mensuel' => 'integer', 'prix_barre' => 'integer'];
    }

    /** « marie-20 », « Marie 20 » -> « MARIE20 ». Lettres et chiffres, 3 a 20. */
    public static function normaliser(?string $code): ?string
    {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $code));

        return strlen($code) >= 3 && strlen($code) <= 20 ? $code : null;
    }

    /** Le delegue actif qui porte ce code, ou rien. */
    public static function parCode(?string $code): ?self
    {
        $code = self::normaliser($code);

        return $code ? self::where('code', $code)->where('actif', true)->first() : null;
    }

    /** Un prix apres la reduction du code, arrondi a la centaine de FCFA. */
    public function prixReduit(int $prix): int
    {
        return (int) (round($prix * (100 - $this->reduction) / 100 / 100) * 100);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function etudiants(): HasMany
    {
        return $this->hasMany(User::class, 'delegue_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(CommissionDelegue::class);
    }
}
