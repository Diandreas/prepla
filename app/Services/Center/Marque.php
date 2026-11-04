<?php

namespace App\Services\Center;

use App\Http\Middleware\EnsureExerciseQuota;
use App\Models\LanguageCenter;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * La marque affichee a la personne : celle de PrePla, ou celle de son centre.
 *
 * Partagee avec l'interface par HandleInertiaRequests (cle « marque »), pour les
 * eleves comme pour le personnel du centre. Les pages publiques d'un centre
 * (invitation d'un enseignant, rejoindre une classe) la passent elles-memes.
 *
 * Un centre qui n'a rien personnalise garde la marque PrePla : les centres
 * existants, et la demonstration, ne changent pas d'apparence.
 *
 * La forme du tableau est un contrat avec le front (resources/js/types/index.ts,
 * Marque) : ne pas renommer ses cles.
 */
class Marque
{
    /** Logo et couleurs actuels de PrePla (app-logo.tsx, app.css --primary). */
    public const LOGO_PREPLA = '/icons/logo.png?v=4';

    public const PRIMAIRE_PREPLA = '#4A90E2';

    public const ACCENT_PREPLA = '#0B2D63';

    /**
     * Cle des attributs de requete ou le lot B deposera le centre resolu par
     * l'hote (sous-domaine ou domaine propre). Il prime sur l'appartenance.
     */
    public const CENTRE_DE_L_HOTE = 'centre_hote';

    /**
     * La marque de la requete : le centre passe en parametre, sinon celui de
     * l'hote (lot B), sinon le centre courant de la personne connectee.
     *
     * @return array<string, mixed>
     */
    public function pourRequete(Request $request, ?LanguageCenter $centre = null): array
    {
        if ($centre === null) {
            $hote = $request->attributes->get(self::CENTRE_DE_L_HOTE);
            $centre = $hote instanceof LanguageCenter ? $hote : null;
        }

        if ($centre === null && ($user = $request->user()) instanceof User) {
            $centre = $this->centreDe($user);
        }

        return $this->pourCentre($centre);
    }

    /** @return array<string, mixed> */
    public function pourUtilisateur(?User $user): array
    {
        return $this->pourCentre($user ? $this->centreDe($user) : null);
    }

    /**
     * Un centre en marque blanche ne montre pas l'offre PrePla Plus a ses
     * membres. Qui est deja abonne la garde, pour gerer ou resilier.
     *
     * Seulement a qui le quota gratuit ne s'applique pas (EnsureExerciseQuota::exempte,
     * seule source de la regle) : l'eleve d'un centre en revente et le personnel
     * d'un espace libre-service restent limites, et l'abonnement est leur seule
     * facon de lever la limite. Le leur masquer les bloquerait sans issue.
     */
    public function offrePrePlaMasquee(?User $user): bool
    {
        return $user !== null
            && ! $user->subscribed('default')
            && (bool) $this->pourUtilisateur($user)['masquer_prepla']
            && EnsureExerciseQuota::exempte($user);
    }

    /**
     * Le centre dont la personne voit la marque : celui ou elle agit en ce
     * moment, eleve comme personnel. Le super administrateur garde PrePla.
     */
    public function centreDe(User $user): ?LanguageCenter
    {
        if ($user->isSuperAdmin()) {
            return null;
        }

        return $user->appartenanceCourante()?->center;
    }

    /**
     * @return array{nom: string, logo_url: ?string, couleurs: array{primaire: string, accent: string}, masquer_prepla: bool, centre_id: ?int, theme: ?array<string, string>}
     */
    public function pourCentre(?LanguageCenter $centre): array
    {
        // Un centre ferme n'impose plus sa marque : ses membres retrouvent PrePla.
        if ($centre === null || ! $centre->is_active || ! $centre->aUneMarque()) {
            return self::parDefaut();
        }

        $primaire = Couleurs::estHexa($centre->brand_primary) ? Couleurs::normaliser($centre->brand_primary) : self::PRIMAIRE_PREPLA;
        $accent = Couleurs::estHexa($centre->brand_accent) ? Couleurs::normaliser($centre->brand_accent) : self::ACCENT_PREPLA;
        $primaireSombre = Couleurs::pourFondSombre($primaire);

        return [
            'nom' => $centre->nomAffiche(),
            'logo_url' => $centre->urlDuLogo(),
            'couleurs' => [
                'primaire' => $primaire,
                'accent' => $accent,
            ],
            'masquer_prepla' => (bool) $centre->hide_prepla_branding,
            'centre_id' => $centre->id,
            // Les valeurs posees en variables CSS (--primary...), calculees ici
            // pour que le texte pose sur la couleur reste lisible.
            'theme' => [
                'primaire' => $primaire,
                'texte_sur_primaire' => Couleurs::texteSur($primaire),
                'primaire_sombre' => $primaireSombre,
                'texte_sur_primaire_sombre' => Couleurs::texteSur($primaireSombre),
                'accent' => $accent,
                'accent_sombre' => Couleurs::pourFondSombre($accent),
            ],
        ];
    }

    /**
     * @return array{nom: string, logo_url: string, couleurs: array{primaire: string, accent: string}, masquer_prepla: bool, centre_id: null, theme: null}
     */
    public static function parDefaut(): array
    {
        return [
            'nom' => 'PrePla',
            'logo_url' => self::LOGO_PREPLA,
            'couleurs' => [
                'primaire' => self::PRIMAIRE_PREPLA,
                'accent' => self::ACCENT_PREPLA,
            ],
            'masquer_prepla' => false,
            // Pas de centre : le front garde les couleurs d'app.css telles quelles.
            'centre_id' => null,
            'theme' => null,
        ];
    }
}
