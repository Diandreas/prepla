<?php

namespace App\Http\Middleware;

use App\Services\Center\ParcoursEleveDeCentre;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ce que voit l'eleve d'un centre, selon le reglage « Contenu proposé aux
 * élèves » de son centre (LanguageCenter::contenuEleves()).
 *
 * - Partage au front l'etat du reglage (cle eleveDeCentre), pour le menu.
 * - En 'centre' (« Parcours créé par le centre »), l'entrainement libre PrePla
 *   lui est ferme : ses adresses le renvoient vers ses devoirs (la pratique,
 *   vers « Mon parcours ») avec un mot d'explication. Les exercices de ses
 *   devoirs et du parcours de sa classe restent jouables, meme pris dans le
 *   vivier PrePla.
 *
 * Ce qui est ferme, c'est ce que PrePla lui proposerait de lui-meme : la
 * pratique libre, le parcours personnel (lecons, etapes, paliers, objectif a
 * choisir), les outils IA qui fabriquent des exercices ou corrigent hors
 * devoir, le guide qui decrit ce parcours et les packs hors ligne. Ce qui
 * reste, c'est ce qui lui appartient ou sert ses devoirs : resultats, ses
 * mots, ses erreurs, le classement, rejoindre une classe, son compte, et les
 * appels du lecteur d'exercices (explication d'une erreur, voix, verification).
 */
class ContenuProposeAuxEleves
{
    /** Noms de routes fermes en 'centre' (motifs Str::is). */
    public const ROUTES_FERMEES = [
        'practice.*',
        'node.start',
        // La seance d'une etape du parcours personnel, ferme comme son entree.
        'exercise.submit_session',
        'lessons.*',
        'level.exam',
        'chapter.synthesis',
        'ai-tools.*',
        'guide',
        'offline.packs.*',
        'errors.similar',
        'onboarding.exam',
        'onboarding.exam.store',
        'onboarding.goal',
        'onboarding.goal.store',
        'onboarding.placement',
        'onboarding.placement.store',
        'onboarding.result',
        'onboarding.program',
        'onboarding.complete',
    ];

    public const MESSAGE = 'Ton centre te fait travailler avec ses devoirs : les voici.';

    /** La pratique libre renvoie vers le parcours que le centre a construit. */
    public const MESSAGE_PARCOURS = 'Ton centre a préparé ton parcours : le voici.';

    public function __construct(private ParcoursEleveDeCentre $parcours) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $centre = $this->parcours->centre($user);
        if (! $centre) {
            return $next($request);
        }

        Inertia::share('eleveDeCentre', ['contenu' => $centre->contenuEleves()]);

        if (! $centre->reserveSesElevesASonContenu()) {
            return $next($request);
        }

        $nom = (string) $request->route()?->getName();
        $ferme = $nom !== '' && Str::is(self::ROUTES_FERMEES, $nom);

        // Un exercice ne s'ouvre que s'il fait partie d'un de ses devoirs, ou du
        // parcours que le centre a construit pour sa classe. Sa correction non
        // plus : sinon un envoi direct enregistrait une tentative hors devoir,
        // comptee dans ses resultats.
        if ($nom === 'exercise.show' || $nom === 'exercise.submit') {
            $exercice = $request->route('exercise');
            $id = is_object($exercice) ? (int) $exercice->getKey() : (int) $exercice;
            $ferme = ! $this->parcours->exerciceDansSesDevoirs($user, $id);
        }

        if (! $ferme) {
            return $next($request);
        }

        // Un appel de donnees (fetch) recoit un refus lisible, pas une page.
        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['message' => self::MESSAGE], 403);
        }

        // « Pratiquer » mene a son parcours : c'est la qu'est son entrainement.
        if (Str::is('practice.*', $nom)) {
            return redirect()->route('parcours.index')->with('success', self::MESSAGE_PARCOURS);
        }

        return redirect()->route('devoirs.index')->with('success', self::MESSAGE);
    }
}
