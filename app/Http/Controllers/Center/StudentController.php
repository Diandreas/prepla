<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserError;
use App\Models\UserExerciseAttempt;
use App\Services\Center\SuiviApprenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');
        $user = $request->user();

        // center_id doit être sélectionné : le filtre ->where('center_id', ...)
        // plus bas s'applique sur la collection déjà chargée en mémoire, pas en
        // SQL — sans cette colonne, il ne matchait jamais rien (toujours null),
        // et la liste de classes affichée restait vide pour tous les élèves.
        $query = $center->students()->with('classrooms:id,name,center_id');

        // A teacher only sees students of the classrooms they teach; a
        // center_admin sees every student of the center.
        if (! $user->centerRoleIs('center_admin')) {
            $query->whereHas(
                'classrooms',
                fn ($q) => $q->where('center_id', $center->id)
                    ->whereHas('teachers', fn ($q2) => $q2->whereKey($user->id))
            );
        }

        $students = $query->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'classrooms' => $u->classrooms
                    ->where('center_id', $center->id)
                    ->pluck('name')
                    ->values(),
                'attempts' => UserExerciseAttempt::where('user_id', $u->id)->count(),
            ]);

        return Inertia::render('center/students/index', [
            'students' => $students,
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

        return Inertia::render('center/students/show', [
            'student' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'level' => $user->profile?->current_level,
                'classes' => $user->classrooms()->where('center_id', $center->id)->pluck('name'),
            ],
            'resume' => $suivi->resume($user, $suivi->fuseau($center)),
            'historique' => $suivi->historique($user),
            // Les faiblesses avec leurs preuves, et le profil tire des redactions
            // et des prises de parole : c'est la qu'un centre voit quoi travailler.
            'faiblesses' => $suivi->faiblesses($user),
            'profil' => $suivi->profil($user),
        ]);
    }

    /** Une tentative en detail : chaque question, la reponse donnee, la correction. */
    public function tentative(Request $request, User $user, UserExerciseAttempt $attempt, SuiviApprenant $suivi): Response
    {
        $this->verifierAcces($request, $user);

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
        $fuseau = $suivi->fuseau($center);
        $nom = 'suivi-'.Str::slug($user->name ?: 'apprenant').'-'.now($fuseau)->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($suivi, $user, $fuseau) {
            $sortie = fopen('php://output', 'w');
            // Le BOM fait ouvrir le fichier en UTF-8 par Excel : sans lui, les
            // accents des intitules sortent en caracteres casses.
            fwrite($sortie, "\xEF\xBB\xBF");
            foreach ($suivi->lignesCsv($user, $fuseau) as $ligne) {
                fputcsv($sortie, $ligne, ';');
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

        abort_unless(
            $user->centers()->where('language_centers.id', $center->id)->exists(),
            403
        );

        if (! $actor->centerRoleIs('center_admin')) {
            $sharesClassroom = $user->classrooms()
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
        if (! $stillInCenter && $user->centerRoleIs('student')) {
            $user->centerMembership()->where('center_id', $center->id)->delete();
        }

        return back()->with('success', "{$user->name} a été retiré de la classe.");
    }
}
