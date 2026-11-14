<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserError;
use App\Models\UserExerciseAttempt;
use App\Services\Center\ClassementCentre;
use App\Services\Center\StatistiquesCentre;
use App\Services\Center\SuiviApprenant;
use App\Support\ExportTableur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    public function index(Request $request, ClassementCentre $classement): Response
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        // Classes, salle, langue, niveau, exercices, score et statut de chaque
        // eleve, en un nombre fixe de requetes (il y en avait une par eleve).
        // Le perimetre est le meme qu'avant : un enseignant ne voit que les
        // eleves des classes qu'il encadre.
        return Inertia::render('center/students/index', [
            'students' => $classement->eleves($center, $request->user()),
            'joursAvantRelance' => DashboardController::JOURS_AVANT_RELANCE,
        ]);
    }

    /**
     * La fiche d'un apprenant : ce qu'il a fait, quand, a quelle heure, combien
     * de temps, et avec quel resultat.
     *
     * Elle ne montrait que les vingt dernieres tentatives, par type d'exercice et
     * pourcentage — la date partait vers la page sans y etre affichee. Tout le
     * reste etait pourtant enregistre.
     */
    public function show(Request $request, User $user, SuiviApprenant $suivi): Response
    {
        $center = $this->verifierAcces($request, $user);
        // Le suivi lit au nom de CE centre : le contenu prive d'un autre centre,
        // ou l'eleve est passe avant, ne lui est pas montre.
        $suivi = $suivi->pourLeCentre($center);

        return Inertia::render('center/students/show', [
            'student' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'level' => $user->profile?->current_level,
                'classes' => $user->classrooms()->where('center_id', $center->id)->pluck('name'),
                // Actions rapides de la fiche : « Donner un devoir » a sa classe, et
                // « Relancer sur WhatsApp » quand on a son numero.
                'classe_ids' => $user->classrooms()->where('center_id', $center->id)->whereNull('classrooms.archived_at')->pluck('classrooms.id'),
                'phone' => $user->phone,
            ],
            'resume' => $suivi->resume($user, $suivi->fuseau($center)),
            'historique' => $suivi->historique($user),
            // Les faiblesses avec leurs preuves, et le profil tire des redactions
            // et des prises de parole : c'est la qu'un centre voit quoi travailler.
            'faiblesses' => $suivi->faiblesses($user),
            'profil' => $suivi->profil($user),
            'statistiques' => $this->statistiques($request, $user, $center, $suivi->fuseau($center)),
        ]);
    }

    /**
     * Les courbes de la fiche : huit semaines de l'eleve et de sa classe, ses
     * competences, son calendrier, ses devoirs. Sa classe : celles du centre ou
     * il est eleve — pour un enseignant, seulement celles qu'il anime.
     *
     * @return array<string, mixed>
     */
    private function statistiques(Request $request, User $user, LanguageCenter $center, string $fuseau): array
    {
        $stats = app(StatistiquesCentre::class);
        $actor = $request->user();

        $classIds = $user->classrooms()
            ->wherePivot('role_in_class', 'student')
            ->where('center_id', $center->id)
            ->when(! $actor->centerRoleIs('center_admin'), fn ($q) => $q->whereHas('teachers', fn ($t) => $t->whereKey($actor->id)))
            ->pluck('classrooms.id')
            ->all();

        $camarades = $classIds === [] ? [] : DB::table('classroom_user')
            ->whereIn('classroom_id', $classIds)
            ->where('role_in_class', 'student')
            ->distinct()
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return [
            'semaines' => $stats->semaines([$user->id]),
            'semaines_classe' => count($camarades) > 1 ? $stats->semaines($camarades) : null,
            'competences' => $stats->competences([$user->id]),
            'calendrier' => $stats->calendrier($user, $fuseau),
            'devoirs' => $stats->devoirsDeLEleve($user, $classIds),
        ];
    }

    /** Une tentative en detail : chaque question, la reponse donnee, la correction. */
    public function tentative(Request $request, User $user, UserExerciseAttempt $attempt, SuiviApprenant $suivi): Response
    {
        $suivi = $suivi->pourLeCentre($this->verifierAcces($request, $user));

        // La tentative doit appartenir a CET apprenant : sinon l'identifiant d'un
        // eleve qu'on suit ouvrirait la copie de n'importe qui d'autre.
        abort_unless($attempt->user_id === $user->id, 404);

        return Inertia::render('center/students/attempt', [
            'student' => ['id' => $user->id, 'name' => $user->name],
            'tentative' => $suivi->detail($attempt),
        ]);
    }

    /** L'historique complet en CSV, a l'heure du centre. */
    public function exportCsv(Request $request, User $user, SuiviApprenant $suivi): StreamedResponse
    {
        $center = $this->verifierAcces($request, $user);
        $suivi = $suivi->pourLeCentre($center);
        $fuseau = $suivi->fuseau($center);
        $nom = 'suivi-'.Str::slug($user->name ?: 'apprenant').'-'.now($fuseau)->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($suivi, $user, $fuseau) {
            $sortie = fopen('php://output', 'w');
            // Le BOM fait ouvrir le fichier en UTF-8 par Excel : sans lui, les
            // accents des intitules sortent en caracteres casses.
            fwrite($sortie, "\xEF\xBB\xBF");
            foreach ($suivi->lignesCsv($user, $fuseau) as $ligne) {
                fputcsv($sortie, ExportTableur::neutraliser($ligne), ';');
            }
            fclose($sortie);
        }, $nom, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Le centre a-t-il le droit de suivre cet apprenant ?
     *
     * Un apprenant d'un AUTRE centre est hors d'atteinte. Dans le centre, le
     * responsable voit tout le monde ; un enseignant ne voit que les eleves de
     * ses propres classes.
     */
    private function verifierAcces(Request $request, User $user): LanguageCenter
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');
        $actor = $request->user();

        // Un ELEVE du centre : depuis qu'on peut appartenir a plusieurs centres,
        // la fiche s'ouvrait aussi sur un enseignant, avec ce qu'il avait fait
        // ailleurs.
        abort_unless(
            $user->centers()->where('language_centers.id', $center->id)->wherePivot('role', 'student')->exists(),
            403
        );

        if (! $actor->centerRoleIs('center_admin')) {
            $sharesClassroom = $user->classrooms()
                ->wherePivot('role_in_class', 'student')
                ->where('center_id', $center->id)
                ->whereHas('teachers', fn ($q) => $q->whereKey($actor->id))
                ->exists();

            abort_unless($sharesClassroom, 403);
        }

        return $center;
    }

    public function remove(Request $request, Classroom $classroom, User $user)
    {
        $this->authorize('update', $classroom);

        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        // La cible doit etre un ELEVE DE CETTE CLASSE. Sans ce controle, un
        // identifiant saisi a la main suffisait pour qu'un enseignant detache un
        // collegue — ou le responsable de l'etablissement — et lui supprime son
        // appartenance au centre, donc tout son acces.
        abort_unless($classroom->students()->whereKey($user->id)->exists(), 403);

        // Remove from the classroom; if no longer in any classroom of the center,
        // also free the center seat.
        $classroom->members()->detach($user->id);

        $stillInCenter = $user->classrooms()->where('center_id', $center->id)->exists();
        // Eleve DE CE CENTRE, lu directement : centerRoleIs() donnait son role dans
        // SON centre courant, qui peut etre un autre ou il enseigne.
        if (! $stillInCenter && $center->aPourEleve($user->id)) {
            // Supprime PAR LE MODELE : CenterUserObserver ferme sa periode
            // d'appartenance, et l'eleve reste compte actif pour le mois s'il a
            // travaille avant son depart. Une suppression par requete l'evitait.
            $user->centerMembership()->where('center_id', $center->id)->get()->each->delete();

            // Il quitte le centre : l'acces que le centre lui avait vendu s'arrete
            // (hasPremiumAccess l'ignore deja, la coupure le dit et le date).
            if ($coupes = $center->couperLesAccesDe($user->id, $request->user()->id, 'Retiré du centre')) {
                \Illuminate\Support\Facades\Log::info('acces-vendus: acces coupe au retrait du centre', [
                    'center_id' => $center->id, 'user_id' => $user->id,
                    'par' => $request->user()->id, 'acces' => $coupes,
                ]);
            }
        }

        return back()->with('success', "{$user->name} a été retiré de la classe.");
    }
}
