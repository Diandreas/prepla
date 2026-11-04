<?php

namespace App\Models\Concerns;

use App\Models\CenterAccessGrant;
use App\Models\CenterStatement;
use App\Models\CenterUser;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Acces vendus par un centre a ses eleves (formule « revente ») : relation vers
 * les acces ouverts, prix, releve. Cote LanguageCenter. Reserve au lot C ; le
 * pendant cote eleve est App\Models\Concerns\Utilisateur\AAccesVendus.
 */
trait AAccesVendus
{
    /** Le centre paie selon ses eleves actifs ; ses eleves sont illimites. */
    public const FORMULE_PALIERS = 'paliers';

    /** Ses eleves suivent le quota gratuit, sauf ceux a qui il a ouvert un acces. */
    public const FORMULE_REVENTE = 'revente';

    /** Espace ouvert seul sur /enseigner (settings.libre_service). */
    public const FORMULE_LIBRE_SERVICE = 'libre_service';

    public const FORMULES = [self::FORMULE_PALIERS, self::FORMULE_REVENTE, self::FORMULE_LIBRE_SERVICE];

    /** Duree d'un acces quand le centre n'en a pas fixe. */
    public const DUREE_ACCES_PAR_DEFAUT = 30;

    /** Part de PrePla sur les acces vendus, en pourcentage. */
    public const PART_PREPLA_REVENTE = 30;

    public function accesVendus(): HasMany
    {
        return $this->hasMany(CenterAccessGrant::class, 'center_id');
    }

    public function releves(): HasMany
    {
        return $this->hasMany(CenterStatement::class, 'center_id');
    }

    /**
     * La formule appliquee. Le marqueur settings.libre_service, pose a l'ouverture
     * d'un espace sur /enseigner, l'emporte : la regle de ces espaces ne change pas,
     * quelle que soit la colonne.
     */
    public function formule(): string
    {
        if ($this->estLibreService()) {
            return self::FORMULE_LIBRE_SERVICE;
        }

        $colonne = $this->getAttribute('formule');

        return in_array($colonne, self::FORMULES, true) ? $colonne : self::FORMULE_PALIERS;
    }

    public function estEnRevente(): bool
    {
        return $this->formule() === self::FORMULE_REVENTE;
    }

    /** Un acces ne s'ouvre qu'a un ELEVE de ce centre : jamais au personnel, jamais ailleurs. */
    public function aPourEleve(int $userId): bool
    {
        return CenterUser::where('center_id', $this->id)
            ->where('user_id', $userId)
            ->where('role', 'student')
            ->exists();
    }

    public function dureeAcces(): int
    {
        return (int) ($this->duree_acces_jours ?: self::DUREE_ACCES_PAR_DEFAUT);
    }

    /**
     * Coupe les acces non termines d'un eleve dans ce centre (il en est retire).
     *
     * @return int le nombre d'acces coupes
     */
    public function couperLesAccesDe(int $userId, ?int $parQui, string $note): int
    {
        return CenterAccessGrant::where('center_id', $this->id)
            ->where('user_id', $userId)
            ->nonTermines()
            ->update(['revoked_at' => now(), 'revoked_by' => $parQui, 'note' => $note]);
    }
}
