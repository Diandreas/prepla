<?php

namespace App\Http\Controllers;

use App\Models\KidsEnfant;
use App\Services\Kids\KidsProgres;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PrePla Kids : apprendre l'anglais, le francais ou l'allemand en jouant, de
 * 6 a 12 ans. Le parent a le compte ; l'enfant est choisi sur « Qui joue ? »
 * et joue dans la session de son parent, sans e-mail ni mot de passe.
 */
class KidsController extends Controller
{
    private const SESSION = 'kids_enfant';

    /** /kids : la presentation, ou « Qui joue ? » pour un parent connecte. */
    public function accueil(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('kids/accueil', [
            'connecte' => $user !== null,
            'enfants' => $user ? KidsEnfant::where('parent_id', $user->id)->orderBy('prenom')->get(['id', 'prenom', 'avatar', 'langue', 'etoiles']) : [],
            'langues' => config('kids.langues'),
        ]);
    }

    /** « Creer mon compte parent » : apres l'inscription, retour a l'espace parent. */
    public function inscription(Request $request): RedirectResponse
    {
        $request->session()->put('kids_parent', true);

        return $request->user() ? redirect()->route('kids.parents') : redirect()->route('register');
    }

    public function parents(Request $request): Response
    {
        $request->session()->forget(self::SESSION);
        $user = $request->user();

        return Inertia::render('kids/parents', [
            'enfants' => KidsEnfant::where('parent_id', $user->id)->orderBy('prenom')->get()
                ->map(fn (KidsEnfant $e) => [
                    'id' => $e->id, 'prenom' => $e->prenom, 'age' => $e->age, 'avatar' => $e->avatar,
                    'langue' => $e->langue, 'langue_aide' => $e->langue_aide,
                    'bilan' => KidsProgres::bilan($e),
                ]),
            'langues' => config('kids.langues'),
            'avatars' => config('kids.avatars'),
            'premium' => $user->hasPremiumAccess(),
            'gratuits' => (int) config('kids.gratuits'),
            'nbThemes' => count(config('kids.themes')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->valider($request);
        abort_if(KidsEnfant::where('parent_id', $request->user()->id)->count() >= 6, 422, 'Six enfants au plus par compte.');
        $enfant = KidsEnfant::create($data + ['parent_id' => $request->user()->id]);

        return back()->with('success', "{$enfant->prenom} est prêt à jouer !");
    }

    public function update(Request $request, KidsEnfant $enfant): RedirectResponse
    {
        $this->autoriser($request, $enfant);
        $enfant->update($this->valider($request));

        return back()->with('success', "Profil de {$enfant->prenom} mis à jour.");
    }

    public function destroy(Request $request, KidsEnfant $enfant): RedirectResponse
    {
        $this->autoriser($request, $enfant);
        $enfant->delete();

        return back()->with('success', "Le profil de {$enfant->prenom} et ses parties sont supprimés.");
    }

    /** « Qui joue ? » : l'enfant choisi joue jusqu'a ce qu'un parent ressorte. */
    public function jouer(Request $request, KidsEnfant $enfant): RedirectResponse
    {
        $this->autoriser($request, $enfant);
        $request->session()->put(self::SESSION, $enfant->id);

        return redirect()->route('kids.carte');
    }

    public function quitter(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION);

        return redirect()->route('kids.parents');
    }

    public function carte(Request $request): Response|RedirectResponse
    {
        if (! $enfant = $this->enfant($request)) {
            return redirect()->route('kids.accueil');
        }

        return Inertia::render('kids/carte', [
            'enfant' => $this->profil($enfant),
            'themes' => KidsProgres::carte($enfant, $request->user()),
        ]);
    }

    public function theme(Request $request, string $theme): Response|RedirectResponse
    {
        if (! $enfant = $this->enfant($request)) {
            return redirect()->route('kids.accueil');
        }
        abort_unless(isset(KidsProgres::themes()[$theme]), 404);
        if (! KidsProgres::themeOuvert($enfant, $theme, $request->user())) {
            return redirect()->route('kids.carte');
        }

        $meilleures = KidsProgres::meilleures($enfant)[$theme] ?? [];
        $t = KidsProgres::themes()[$theme];

        return Inertia::render('kids/theme', [
            'enfant' => $this->profil($enfant),
            'theme' => ['slug' => $theme, 'nom' => $enfant->langue_aide === 'en' ? $t['en'] : $t['fr'], 'icone' => $t['icone'], 'illustration' => KidsProgres::fichier("themes/{$theme}"), 'couleur' => $t['couleur']],
            'activites' => collect(KidsProgres::activites())->map(fn ($a, $slug) => [
                'slug' => $slug, 'nom' => $enfant->langue_aide === 'en' ? $a['en'] : $a['fr'], 'icone' => $a['icone'], 'etoiles' => $meilleures[$slug] ?? 0,
            ])->values(),
            'mots' => KidsProgres::mots($theme, $enfant->langue, $enfant->langue_aide),
        ]);
    }

    public function jeu(Request $request, string $theme, string $activite): Response|RedirectResponse
    {
        if (! $enfant = $this->enfant($request)) {
            return redirect()->route('kids.accueil');
        }
        abort_unless(isset(KidsProgres::themes()[$theme], KidsProgres::activites()[$activite]), 404);
        if (! KidsProgres::themeOuvert($enfant, $theme, $request->user())) {
            return redirect()->route('kids.carte');
        }
        $t = KidsProgres::themes()[$theme];

        return Inertia::render('kids/jeu', [
            'enfant' => $this->profil($enfant),
            'theme' => ['slug' => $theme, 'nom' => $enfant->langue_aide === 'en' ? $t['en'] : $t['fr'], 'couleur' => $t['couleur']],
            'activite' => $activite,
            'mots' => KidsProgres::mots($theme, $enfant->langue, $enfant->langue_aide),
            'tts' => config("kids.langues.{$enfant->langue}.tts", 'en'),
        ]);
    }

    /** La fin d'une partie, envoyee par le jeu. Repond en JSON : le jeu reste a l'ecran. */
    public function resultat(Request $request): JsonResponse
    {
        $enfant = $this->enfant($request);
        abort_unless($enfant, 403);
        $data = $request->validate([
            'theme' => ['required', Rule::in(array_keys(KidsProgres::themes()))],
            'activite' => ['required', Rule::in(array_keys(KidsProgres::activites()))],
            'score' => ['required', 'integer', 'min:0', 'max:100'],
            'duree' => ['nullable', 'integer', 'min:0', 'max:36000'],
            'mots_rates' => ['nullable', 'array', 'max:30'],
            'mots_rates.*' => ['string', 'max:60'],
        ]);
        abort_unless(KidsProgres::themeOuvert($enfant, $data['theme'], $request->user()), 403);

        return response()->json(KidsProgres::enregistrer($enfant, $data['theme'], $data['activite'], $data['score'], (int) ($data['duree'] ?? 0), $data['mots_rates'] ?? []));
    }

    private function enfant(Request $request): ?KidsEnfant
    {
        $id = $request->session()->get(self::SESSION);

        return $id ? KidsEnfant::where('id', $id)->where('parent_id', $request->user()->id)->first() : null;
    }

    private function profil(KidsEnfant $e): array
    {
        return ['prenom' => $e->prenom, 'avatar' => $e->avatar, 'langue' => $e->langue, 'aide' => $e->langue_aide,
            'etoiles' => $e->etoiles, 'serie' => $e->serie, 'drapeau' => config("kids.langues.{$e->langue}.drapeau")];
    }

    private function autoriser(Request $request, KidsEnfant $enfant): void
    {
        abort_unless($enfant->parent_id === $request->user()->id, 403);
    }

    private function valider(Request $request): array
    {
        return $request->validate([
            'prenom' => ['required', 'string', 'max:40'],
            'age' => ['required', 'integer', 'min:3', 'max:14'],
            'avatar' => ['required', Rule::in(config('kids.avatars'))],
            'langue' => ['required', Rule::in(array_keys(config('kids.langues')))],
            'langue_aide' => ['required', Rule::in(['fr', 'en'])],
        ]);
    }
}
