<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Listeners\EnregistrerCommission;
use App\Models\CenterAccessGrant;
use App\Models\CenterCommission;
use App\Models\CenterDomain;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\LanguageCenter;
use App\Models\TeacherInvitation;
use App\Services\Center\ElevesActifs;
use App\Services\Center\Marque;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

/**
 * L'accueil des parametres du centre, pour son responsable : une carte par
 * reglage (marque, examens, adresse web, formule et sieges, acces vendus,
 * parrainage, equipe), qui resume l'etat actuel et mene a la page de reglage.
 *
 * Les reglages etaient eparpilles dans le menu : un responsable ne trouvait pas
 * « les parametres de son centre ». Cette page les rassemble sans rien changer
 * aux pages existantes.
 *
 * Le centre vient TOUJOURS de EnsureCenterStaff (centre courant), jamais d'un
 * identifiant de l'adresse : un responsable ne lit que le sien. Un enseignant
 * passe EnsureCenterStaff ; il est refuse ici (403).
 */
class ParametresController extends Controller
{
    public function __invoke(Request $request, ElevesActifs $actifs): Response
    {
        abort_unless($request->user()->centerRoleIs('center_admin'), 403);

        /** @var LanguageCenter $centre */
        $centre = $request->attributes->get('center');

        $mois = ElevesActifs::moisCourant();
        $elevesActifs = $actifs->compter($centre->id, $mois);

        return Inertia::render('center/parametres', [
            'centre' => ['id' => $centre->id, 'name' => $centre->name],
            'identite' => $this->identite($centre),
            'examens' => $this->examens($centre),
            'adresse' => $this->adresse($centre),
            'formule' => [
                'formule' => $centre->formule(),
                'sieges_utilises' => $centre->seatsUsed(),
                'sieges_limite' => (int) $centre->seats_limit,
                'eleves_actifs' => $elevesActifs,
                'palier' => ElevesActifs::palier($elevesActifs),
                'mois' => $mois,
            ],
            // Seulement en formule revente : ailleurs, il n'y a rien a vendre.
            'acces' => $centre->estEnRevente() && Route::has('center.acces.index') ? [
                'en_cours' => CenterAccessGrant::where('center_id', $centre->id)->enCours()->distinct()->count('user_id'),
                'prix' => $centre->prix_acces,
                'duree_jours' => $centre->dureeAcces(),
                'devise' => CenterAccessGrant::DEVISE,
            ] : null,
            'parrainage' => Route::has('center.parrainage.index') ? $this->parrainage($centre) : null,
            'equipe' => $this->equipe($centre),
        ]);
    }

    private function identite(LanguageCenter $centre): array
    {
        $marque = app(Marque::class)->pourCentre($centre);

        return [
            'nom_affiche' => $centre->nomAffiche(),
            'nom_personnalise' => $centre->display_name !== null && trim((string) $centre->display_name) !== '',
            'logo_url' => $centre->urlDuLogo(),
            'primaire' => $marque['couleurs']['primaire'] ?? Marque::PRIMAIRE_PREPLA,
            'accent' => $marque['couleurs']['accent'] ?? Marque::ACCENT_PREPLA,
            'couleurs_personnalisees' => $centre->brand_primary !== null || $centre->brand_accent !== null,
            'masquer_prepla' => (bool) $centre->hide_prepla_branding,
        ];
    }

    private function examens(LanguageCenter $centre): array
    {
        // Les examens que voient ses eleves : tous les proposables, ou ceux retenus.
        $liste = Exam::proposables($centre)->with('language:id,name')->orderBy('name')->get(['exams.id', 'exams.name', 'exams.language_id']);

        return [
            'tous' => $centre->examensRetenus() === null,
            'total_proposables' => Exam::proposables()->count(),
            // « Contenu proposé aux élèves » (AContenuEleves), regle sur la meme page.
            'contenu_eleves' => $centre->contenuEleves(),
            'liste' => $liste->map(fn (Exam $e) => [
                'id' => $e->id,
                'name' => $e->name,
                'langue' => $e->language?->name,
            ])->values(),
        ];
    }

    private function adresse(LanguageCenter $centre): array
    {
        return [
            // L'adresse commune de PrePla : elle marche toujours.
            'commune' => parse_url((string) config('app.url'), PHP_URL_HOST) ?: (string) config('app.url'),
            // Pas de jeton de verification ici : le super administrateur gere les domaines.
            'domaines' => $centre->domaines()->orderByDesc('is_primary')->orderBy('id')->get()
                ->map(fn (CenterDomain $d) => [
                    'id' => $d->id,
                    'host' => $d->host,
                    'type' => $d->type,
                    'verifie' => $d->estVerifie(),
                    'principal' => (bool) $d->is_primary,
                ])->values(),
        ];
    }

    private function parrainage(LanguageCenter $centre): array
    {
        $periode = now(EnregistrerCommission::FUSEAU)->format('Y-m');

        // Une ligne par devise : on n'additionne pas des euros et des francs.
        $totaux = CenterCommission::where('center_id', $centre->id)
            ->where('periode', $periode)
            ->get(['commission', 'currency'])
            ->groupBy('currency')
            ->map(fn ($l, $devise) => ['devise' => $devise, 'commission' => (int) $l->sum('commission')])
            ->values();

        return [
            'lien' => route('parrainage.lien', $centre->codeParrainage()),
            'comptes' => $centre->comptesParraines()->count(),
            'taux' => CenterCommission::TAUX,
            'periode' => $periode,
            'totaux' => $totaux,
        ];
    }

    private function equipe(LanguageCenter $centre): array
    {
        return [
            'responsables' => $centre->members()->wherePivot('role', 'center_admin')->count(),
            'enseignants' => $centre->members()->wherePivot('role', 'teacher')->count(),
            'classes' => Classroom::where('center_id', $centre->id)->whereNull('archived_at')->count(),
            'invitations' => TeacherInvitation::where('center_id', $centre->id)->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->count(),
        ];
    }
}
