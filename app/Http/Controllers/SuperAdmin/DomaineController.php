<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\CenterDomain;
use App\Models\LanguageCenter;
use App\Services\Center\Domaines;
use App\Services\Center\ResolveurDns;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Les adresses d'un centre (lot B), gerees par le super administrateur seul :
 * un sous-domaine sous notre racine (ecole.prepla.mirlab.cloud), ou un
 * domaine propre du centre (prepa.ecole.cm).
 *
 * Un sous-domaine est verifie des sa creation : la zone est a nous, le DNS
 * joker le sert deja. Un domaine propre ne l'est qu'apres verification DNS :
 * TXT _prepla.<domaine> portant le jeton, ou CNAME vers la cible. Tant qu'il
 * ne l'est pas, il ne mene nulle part (400) et n'est pas accepte par
 * FaireConfianceAuxHotes.
 *
 * Le certificat et nginx restent a faire a la main sur le VPS pour chaque
 * domaine propre : docs/domaines-centres.md.
 */
class DomaineController extends Controller
{
    /** Pour la page admin/centers/{center} (CenterController::show). */
    public static function pourLaPage(LanguageCenter $center): array
    {
        return $center->domaines()->orderByDesc('is_primary')->orderBy('id')->get()
            ->map(fn (CenterDomain $d) => [
                'id' => $d->id,
                'host' => $d->host,
                'type' => $d->type,
                'verifie' => $d->estVerifie(),
                'verifie_le' => $d->verified_at?->toIso8601String(),
                'principal' => $d->is_primary,
                'txt_nom' => $d->nomTxt(),
                'txt_valeur' => $d->valeurTxt(),
            ])->all();
    }

    /** Ce que la page affiche pour aider a saisir et a configurer. */
    public static function reglages(): array
    {
        return [
            'racine' => Domaines::racine(),
            'cible_cname' => (string) config('domaines.cible_cname'),
        ];
    }

    public function store(Request $request, LanguageCenter $center): RedirectResponse
    {
        $request->validate(['host' => ['required', 'string', 'max:253']]);

        [$hote, $type] = $this->analyser((string) $request->input('host'));

        if (CenterDomain::where('host', $hote)->exists()) {
            throw ValidationException::withMessages(['host' => 'Cette adresse est déjà attribuée à un centre.']);
        }

        $sousDomaine = $type === CenterDomain::SOUS_DOMAINE;

        DB::transaction(function () use ($center, $hote, $type, $sousDomaine) {
            $domaine = $center->domaines()->create([
                'host' => $hote,
                'type' => $type,
                'verification_token' => Str::lower(Str::random(40)),
                // Sous notre racine : rien a prouver, la zone DNS est la notre.
                'verified_at' => $sousDomaine ? now() : null,
            ]);

            if ($sousDomaine) {
                $this->principalSiAucun($center, $domaine);
            }
        });

        Log::info('domaines: adresse ajoutee', ['center_id' => $center->id, 'host' => $hote, 'type' => $type, 'par' => $request->user()->id]);

        return back()->with('success', $sousDomaine
            ? "Sous-domaine « {$hote} » ajouté et actif."
            : "Domaine « {$hote} » ajouté. Il ne sera actif qu'après vérification DNS.");
    }

    public function verifier(Request $request, LanguageCenter $center, CenterDomain $domaine, ResolveurDns $dns): RedirectResponse
    {
        if ($domaine->estVerifie()) {
            return back()->with('success', "« {$domaine->host} » est déjà vérifié.");
        }

        $txt = collect($dns->txt($domaine->nomTxt()))->map(fn ($v) => trim((string) $v, " \"\t"));
        $parTxt = $txt->contains($domaine->valeurTxt()) || $txt->contains($domaine->verification_token);

        $cible = strtolower((string) config('domaines.cible_cname'));
        $parCname = $cible !== '' && in_array($cible, $dns->cname($domaine->host), true);

        if (! $parTxt && ! $parCname) {
            return back()->with('error', "Vérification impossible pour « {$domaine->host} » : ni l'enregistrement TXT {$domaine->nomTxt()}, "
                ."ni un CNAME vers {$cible} n'ont été trouvés. La propagation DNS peut prendre quelques heures.");
        }

        DB::transaction(function () use ($center, $domaine) {
            $domaine->update(['verified_at' => now()]);
            $this->principalSiAucun($center, $domaine);
        });

        Log::info('domaines: domaine verifie', ['center_id' => $center->id, 'host' => $domaine->host, 'par_txt' => $parTxt, 'par' => $request->user()->id]);

        return back()->with('success', "« {$domaine->host} » est vérifié. Pensez au certificat et à nginx sur le serveur (docs/domaines-centres.md).");
    }

    public function principal(LanguageCenter $center, CenterDomain $domaine): RedirectResponse
    {
        if (! $domaine->estVerifie()) {
            return back()->with('error', 'Seule une adresse vérifiée peut devenir principale.');
        }

        DB::transaction(function () use ($center, $domaine) {
            $center->domaines()->where('id', '!=', $domaine->id)->where('is_primary', true)->get()
                ->each(fn (CenterDomain $d) => $d->update(['is_primary' => false]));
            $domaine->update(['is_primary' => true]);
        });

        return back()->with('success', "« {$domaine->host} » est désormais l'adresse principale du centre.");
    }

    public function destroy(Request $request, LanguageCenter $center, CenterDomain $domaine): RedirectResponse
    {
        $hote = $domaine->host;

        DB::transaction(function () use ($center, $domaine) {
            $etaitPrincipal = $domaine->is_primary;
            $domaine->delete();

            // Les liens du centre gardent une adresse si une autre est verifiee.
            if ($etaitPrincipal && ($suivant = $center->domaines()->whereNotNull('verified_at')->orderBy('id')->first())) {
                $suivant->update(['is_primary' => true]);
            }
        });

        Log::info('domaines: adresse retiree', ['center_id' => $center->id, 'host' => $hote, 'par' => $request->user()->id]);

        return back()->with('success', "« {$hote} » est retiré : cette adresse ne mène plus au centre.");
    }

    /**
     * La saisie, ramenee a un hote et a son type. Un mot seul (« ecole ») est
     * un sous-domaine de la racine.
     *
     * @return array{0: string, 1: string}
     */
    private function analyser(string $saisie): array
    {
        $hote = Domaines::normaliser($saisie);
        $racine = Domaines::racine();

        if ($hote !== null && ! str_contains($hote, '.')) {
            $hote .= '.'.$racine;
        }

        $refus = fn (string $message) => ValidationException::withMessages(['host' => $message]);

        if ($hote === null || filter_var($hote, FILTER_VALIDATE_IP) !== false) {
            throw $refus("Saisissez un nom de domaine, par exemple « ecole » ou « prepa.ecole.cm ».");
        }

        if (Domaines::estPrincipal($hote) || $hote === $racine) {
            throw $refus("C'est l'adresse principale de PrePla : elle ne peut pas être attribuée à un centre.");
        }

        if (Domaines::estSousLaRacine($hote)) {
            $etiquette = Domaines::etiquetteSousLaRacine($hote);
            if ($etiquette === null || ! Domaines::estUneEtiquetteValide($etiquette)) {
                throw $refus("Un sous-domaine tient en un seul mot de 2 à 63 caractères : lettres, chiffres et tirets.");
            }
            if (Domaines::estReserve($etiquette)) {
                throw $refus("« {$etiquette} » est réservé : choisissez un autre sous-domaine.");
            }

            return [$hote, CenterDomain::SOUS_DOMAINE];
        }

        if (! Domaines::estUnHoteValide($hote)) {
            throw $refus("Ce nom de domaine n'est pas valide.");
        }

        return [$hote, CenterDomain::DOMAINE_PROPRE];
    }

    /** Le premier domaine verifie du centre devient son adresse principale. */
    private function principalSiAucun(LanguageCenter $center, CenterDomain $domaine): void
    {
        $aDejaUnPrincipal = $center->domaines()
            ->where('id', '!=', $domaine->id)
            ->where('is_primary', true)
            ->whereNotNull('verified_at')
            ->exists();

        if (! $aDejaUnPrincipal) {
            $domaine->update(['is_primary' => true]);
        }
    }
}
