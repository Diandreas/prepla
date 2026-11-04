<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\LanguageCenter;
use App\Services\Center\Couleurs;
use App\Services\Center\Marque;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * La personnalisation d'un centre, par son responsable : nom affiche, logo,
 * deux couleurs, marque blanche et examens proposes.
 *
 * Le centre vient TOUJOURS de EnsureCenterStaff (centre courant), jamais d'un
 * identifiant de l'adresse : un responsable ne peut toucher que le sien. Un
 * enseignant passe EnsureCenterStaff ; chaque action le refuse ici (403).
 */
class PersonnalisationController extends Controller
{
    /** Taille maximale du logo, en kilo-octets (1 Mo). */
    public const LOGO_KO_MAX = 1024;

    /** Dossier des logos sur le disque public, un sous-dossier par centre. */
    public const DOSSIER_LOGOS = 'center-branding';

    private function centreDuResponsable(Request $request): LanguageCenter
    {
        abort_unless($request->user()->centerRoleIs('center_admin'), 403);

        /** @var LanguageCenter $centre */
        $centre = $request->attributes->get('center');

        return $centre;
    }

    public function edit(Request $request): Response
    {
        $centre = $this->centreDuResponsable($request);

        // Tous les examens proposables, sans le filtre du centre : c'est ici
        // qu'il choisit lesquels garder. Groupes par langue, chacun a part.
        $examens = Exam::proposables()->with('language:id,name')->orderBy('name')->get(['id', 'name', 'language_id'])
            ->groupBy(fn (Exam $e) => $e->language?->name ?? '—')
            ->map(fn ($liste, $langue) => [
                'langue' => $langue,
                'examens' => $liste->map(fn (Exam $e) => ['id' => $e->id, 'name' => $e->name])->values(),
            ])
            ->sortBy('langue')
            ->values();

        return Inertia::render('center/personnalisation', [
            'centre' => [
                'id' => $centre->id,
                'name' => $centre->name,
                'display_name' => $centre->display_name,
                'logo_url' => $centre->urlDuLogo(),
                'brand_primary' => $centre->brand_primary,
                'brand_accent' => $centre->brand_accent,
                'hide_prepla_branding' => (bool) $centre->hide_prepla_branding,
            ],
            'examens' => $examens,
            // Vide : le centre propose tous les examens.
            'retenus' => $centre->examensRetenus() ?? [],
            'defauts' => ['primaire' => Marque::PRIMAIRE_PREPLA, 'accent' => Marque::ACCENT_PREPLA],
            'contrasteMinimal' => Couleurs::CONTRASTE_MINIMAL,
            'logoKoMax' => self::LOGO_KO_MAX,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $centre = $this->centreDuResponsable($request);

        $couleur = function (string $attribut, mixed $valeur, \Closure $echec) {
            if (! Couleurs::estHexa($valeur)) {
                $echec('Choisissez une couleur au format #RRGGBB.');

                return;
            }
            $contraste = Couleurs::contraste($valeur, Couleurs::BLANC);
            if ($contraste < Couleurs::CONTRASTE_MINIMAL) {
                $echec(sprintf(
                    'Cette couleur est trop claire : sur fond blanc, elle serait difficile à lire (contraste %s:1, minimum %s:1). Choisissez une teinte plus foncée.',
                    number_format($contraste, 1, ',', ''),
                    number_format(Couleurs::CONTRASTE_MINIMAL, 1, ',', ''),
                ));
            }
        };

        $proposables = Exam::proposables()->pluck('exams.id')->map(fn ($id) => (int) $id)->all();

        $valide = $request->validate([
            'display_name' => ['nullable', 'string', 'max:80'],
            'brand_primary' => ['nullable', 'string', $couleur],
            'brand_accent' => ['nullable', 'string', $couleur],
            'hide_prepla_branding' => ['boolean'],
            // Au moins un examen : un centre sans examen n'aurait rien a proposer.
            'examens' => ['required', 'array', 'min:1'],
            'examens.*' => ['integer', 'distinct', Rule::in($proposables)],
        ], [
            'examens.required' => 'Retenez au moins un examen.',
            'examens.min' => 'Retenez au moins un examen.',
            'examens.*.in' => "Cet examen n'est pas proposé.",
        ]);

        $retenus = array_values(array_unique(array_map('intval', $valide['examens'])));
        sort($retenus);
        $tous = $proposables;
        sort($tous);

        DB::transaction(function () use ($centre, $valide, $retenus, $tous) {
            $nom = trim((string) ($valide['display_name'] ?? ''));

            $centre->update([
                'display_name' => $nom !== '' ? $nom : null,
                'brand_primary' => isset($valide['brand_primary']) ? Couleurs::normaliser($valide['brand_primary']) : null,
                'brand_accent' => isset($valide['brand_accent']) ? Couleurs::normaliser($valide['brand_accent']) : null,
                'hide_prepla_branding' => (bool) ($valide['hide_prepla_branding'] ?? false),
            ]);

            // Tout coche revient a « tous » : un examen ajoute plus tard par
            // PrePla sera propose aussi, sans repasser par cette page.
            $centre->examensProposes()->sync($retenus === $tous ? [] : $retenus);
        });

        return back()->with('success', 'Personnalisation enregistrée.');
    }

    public function storeLogo(Request $request): RedirectResponse
    {
        $centre = $this->centreDuResponsable($request);

        // png, jpg ou webp, verifies sur le contenu. Jamais de SVG : il peut
        // porter du script (la regle « image » de Laravel le refuse aussi).
        $format = 'Le logo doit être une image PNG, JPG ou WebP (le SVG n’est pas accepté).';
        // En plus des regles de Laravel : l'image doit se lire comme une vraie
        // image matricielle. Un SVG renomme en .png n'en est pas une.
        $matricielle = function (string $attribut, mixed $fichier, \Closure $echec) use ($format) {
            if (! $fichier instanceof UploadedFile || self::typeDImage($fichier) === null) {
                $echec($format);
            }
        };
        $request->validate([
            'logo' => ['bail', 'required', 'file', 'max:'.self::LOGO_KO_MAX, 'image', 'mimes:png,jpg,jpeg,webp', 'mimetypes:image/png,image/jpeg,image/webp', $matricielle],
        ], [
            'logo.required' => 'Choisissez un fichier.',
            'logo.image' => $format,
            'logo.mimes' => $format,
            'logo.mimetypes' => $format,
            'logo.max' => 'Le logo ne doit pas dépasser 1 Mo.',
        ]);

        $fichier = $request->file('logo');
        // Extension deduite du contenu, nom genere : rien ne vient du nom envoye,
        // et aucun centre ne peut ecraser le fichier d'un autre.
        $extension = self::typeDImage($fichier);
        $chemin = $fichier->storeAs(self::DOSSIER_LOGOS.'/'.$centre->id, Str::uuid().'.'.$extension, 'public');
        abort_if($chemin === false, 500);

        $ancien = $centre->logo_path;
        $centre->update(['logo_path' => $chemin]);
        $this->supprimerFichier($centre, $ancien);

        return back()->with('success', 'Logo enregistré.');
    }

    public function destroyLogo(Request $request): RedirectResponse
    {
        $centre = $this->centreDuResponsable($request);

        $ancien = $centre->logo_path;
        $centre->update(['logo_path' => null]);
        $this->supprimerFichier($centre, $ancien);

        return back()->with('success', 'Logo retiré.');
    }

    /**
     * Le type reel de l'image, lu dans son contenu : « png », « jpg » ou « webp »,
     * ou null pour tout le reste (SVG compris, quel que soit son nom).
     */
    private static function typeDImage(UploadedFile $fichier): ?string
    {
        $info = @getimagesize((string) $fichier->getRealPath());

        return match ($info[2] ?? null) {
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_WEBP => 'webp',
            default => null,
        };
    }

    /** Ne supprime qu'un fichier range dans le dossier de CE centre. */
    private function supprimerFichier(LanguageCenter $centre, ?string $chemin): void
    {
        if ($chemin && str_starts_with($chemin, self::DOSSIER_LOGOS.'/'.$centre->id.'/')) {
            Storage::disk('public')->delete($chemin);
        }
    }
}
