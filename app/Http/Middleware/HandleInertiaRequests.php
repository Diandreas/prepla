<?php

namespace App\Http\Middleware;

use App\Models\UserExerciseAttempt;
use App\Services\Center\Marque;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user = $request->user()?->load('profile');

        // Le centre dans lequel la personne agit EN CE MOMENT, et son role la. Une
        // personne peut en gerer plusieurs : c'etait une appartenance prise au
        // hasard, qui pouvait ne pas etre celle des droits appliques.
        $center = null;
        $centres = [];
        if ($appartenance = $user?->appartenanceCourante()) {
            $c = $appartenance->center;
            $center = [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'role' => $appartenance->role, // center_admin | teacher | student
                // La barre « Parametres du centre » n'offre l'onglet Acces vendus
                // qu'en formule revente, comme la carte de la page d'accueil.
                'formule' => $c->formule,
            ];

            // Les centres entre lesquels passer, seulement quand il y en a plusieurs.
            // Pas sur l'adresse d'un centre (lot B) : elle fixe le centre, et
            // changer de centre n'y aurait aucun effet.
            $encadres = $user->centresEncadres();
            if ($encadres->count() > 1 && ResolveCenterFromHost::centre($request) === null) {
                $centres = $encadres->map(fn ($a) => [
                    'id' => $a->center_id,
                    'name' => $a->center->name,
                    'role' => $a->role,
                ])->all();
            }
        }

        $isPremium = $user?->hasPremiumAccess() ?? false;
        // Surfaced so the UI can show "2/3 free exercises today" BEFORE the
        // EnsureExerciseQuota middleware blocks a 4th submission — the same
        // "communicate the rule before, not just after" principle already
        // applied to the mastery threshold on the exercise player.
        $exercisesToday = (! $isPremium && $user)
            ? UserExerciseAttempt::where('user_id', $user->id)->whereDate('created_at', today())->count()
            : 0;

        return array_merge(parent::share($request), [
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $user,
                'role' => $user?->role,        // global role (super_admin | student)
                'center' => $center,           // null if the user has no center
                'centres' => $centres,         // vide sauf pour qui gere plusieurs centres
            ],
            'userProfile' => $request->user()?->profile,
            // Entrer par Google ne demande qu'un clic : personne n'a saisi de numero.
            // On le demande une fois, sans bloquer, pour pouvoir recueillir les retours.
            'needsPhone' => $user !== null && $user->phone === null && $user->phone_prompted_at === null,
            'practiceAvailability' => fn () => app(\App\Services\Content\PracticeAvailability::class)->forUser($user),
            'learningPreferences' => array_merge(['speaking_enabled' => true, 'audio_enabled' => true],
                $request->user()?->profile?->learning_preferences ?? []),
            'isPremium' => $isPremium,
            'onTrial' => $request->user()?->isOnTrial() ?? false,
            'trialDaysLeft' => $request->user()?->trialDaysLeft() ?? 0,
            'freeExercisesUsedToday' => $exercisesToday,
            'freeExercisesLimit' => EnsureExerciseQuota::DAILY_FREE_LIMIT,
            // Le personnel d'un espace libre-service est limite comme un apprenant :
            // l'interface doit le lui annoncer avant le blocage, pas le cacher.
            'quotaExempt' => fn () => $user !== null && ! $isPremium && EnsureExerciseQuota::exempte($user),
            // La marque affichee (PrePla, ou celle du centre une fois le lot A livre).
            // Aussi pour les eleves et les pages publiques : jamais derriere un role.
            'marque' => fn () => app(Marque::class)->pourRequete($request),
            // Entrees de menu des fonctions livrees par lots : le menu ne propose
            // une page que si sa route existe cote serveur.
            'menus' => fn () => [
                'personnalisation' => Route::has('center.personnalisation.edit'),
                'acces' => Route::has('center.acces.index'),
                'releves' => Route::has('admin.releves.index'),
                'parrainage' => Route::has('center.parrainage.index'),
                // L'accueil des parametres du centre, qui rassemble les reglages ci-dessus.
                'parametres' => Route::has('center.parametres'),
            ],
            'vapidPublicKey' => config('webpush.vapid.public_key'),
            'flash' => [
                'correction' => $request->session()->get('correction'),
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                // Le lien d'invitation n'est affiche qu'une fois : la base n'en garde
                // qu'une empreinte, on ne peut pas le reconstituer ensuite.
                'invitationLink' => $request->session()->get('invitationLink'),
            ],
        ]);
    }
}
