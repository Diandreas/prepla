<?php

namespace App\Services\Center\Import;

use App\Models\CenterUser;
use App\Models\Classroom;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Notifications\InvitationEleveNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Import d'une liste d'eleves par le centre : nom, email, telephone (facultatif), classe.
 *
 * analyser() ne touche a rien : il dit, ligne par ligne, ce que l'import fera.
 * importer() refait la meme analyse (le fichier est renvoye a la validation,
 * rien n'est garde entre les deux) et applique seulement les lignes valides.
 *
 * Statuts : « nouveau » (compte cree), « existant » (compte deja la, rattache),
 * « doublon » (deja dans la classe, ou deux fois dans le fichier : ignore),
 * « erreur » (rien n'est fait, le motif est donne).
 */
class ImportEleves
{
    public const MAX_LIGNES = 500;

    public const COLONNES = ['nom', 'email', 'telephone', 'classe'];

    /**
     * @param  list<array<string, mixed>>  $lignes
     * @return list<array{ligne:int, nom:string, email:string, telephone:?string, classe:string, statut:string, erreurs:list<string>}>
     */
    public function analyser(LanguageCenter $centre, User $auteur, array $lignes): array
    {
        // Les classes ou cet auteur peut inscrire : celles de CE centre, ouvertes,
        // et pour un enseignant celles qu'il encadre (ClassroomPolicy::update).
        $classes = Classroom::where('center_id', $centre->id)->whereNull('archived_at')->get()
            ->filter(fn (Classroom $c) => $auteur->can('update', $c))
            ->keyBy(fn (Classroom $c) => mb_strtolower(trim($c->name)));

        $vus = [];
        $placesRestantes = $centre->seatsAvailable();
        $resultat = [];

        foreach ($lignes as $l) {
            $nom = trim((string) ($l['nom'] ?? ''));
            $email = mb_strtolower(trim((string) ($l['email'] ?? $l['e_mail'] ?? $l['courriel'] ?? '')));
            $tel = trim((string) ($l['telephone'] ?? $l['tel'] ?? ''));
            $nomClasse = trim((string) ($l['classe'] ?? ''));
            $erreurs = [];

            if ($nom === '') {
                $erreurs[] = 'Le nom est requis.';
            } elseif (mb_strlen($nom) > 255) {
                $erreurs[] = 'Le nom dépasse 255 caractères.';
            }
            if ($email === '') {
                $erreurs[] = "L'email est requis.";
            } elseif (mb_strlen($email) > 255 || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erreurs[] = "L'email « {$email} » n'est pas valide.";
            }
            if ($tel !== '' && ! preg_match('/^\+?[0-9][0-9 .\-()]{5,24}$/', $tel)) {
                $erreurs[] = "Le téléphone « {$tel} » n'est pas valide.";
            }
            $classe = $classes->get(mb_strtolower($nomClasse));
            if ($nomClasse === '') {
                $erreurs[] = 'La classe est requise.';
            } elseif (! $classe) {
                $erreurs[] = "La classe « {$nomClasse} » n'existe pas dans votre centre, ou vous ne l'encadrez pas.";
            }

            $statut = 'erreur';
            if ($erreurs === []) {
                [$statut, $motif] = $this->statut($centre, $classe, $email, $vus);
                if ($motif !== null) {
                    $erreurs[] = $motif;
                }
                // Une place par eleve qui entre dans le centre.
                if (in_array($statut, ['nouveau', 'existant'], true) && ! $this->dejaEleve($centre, $email)) {
                    if ($placesRestantes < 1) {
                        $statut = 'erreur';
                        $erreurs[] = "Plus de licence disponible dans votre centre pour cet élève.";
                    } else {
                        $placesRestantes--;
                    }
                }
            }
            if ($statut !== 'erreur' && $email !== '') {
                $vus[$email] = true;
            }

            $resultat[] = [
                'ligne' => (int) $l['_ligne'],
                'nom' => $nom,
                'email' => $email,
                'telephone' => $tel !== '' ? $tel : null,
                'classe' => $classe?->name ?? $nomClasse,
                'classe_id' => $classe?->id,
                'statut' => $statut,
                'erreurs' => $erreurs,
            ];
        }

        return $resultat;
    }

    /**
     * Applique les lignes « nouveau » et « existant » d'une analyse.
     *
     * @return array{crees:int, rattaches:int, ignores:int, erreurs:int}
     */
    public function importer(LanguageCenter $centre, array $analyse): array
    {
        $bilan = ['crees' => 0, 'rattaches' => 0, 'ignores' => 0, 'erreurs' => 0];
        $invitations = [];

        DB::transaction(function () use ($centre, $analyse, &$bilan, &$invitations) {
            foreach ($analyse as $ligne) {
                if ($ligne['statut'] === 'doublon') {
                    $bilan['ignores']++;

                    continue;
                }
                if ($ligne['statut'] === 'erreur') {
                    $bilan['erreurs']++;

                    continue;
                }

                $classe = Classroom::where('center_id', $centre->id)->findOrFail($ligne['classe_id']);
                $user = User::where('email', $ligne['email'])->first();
                $jeton = null;

                if (! $user) {
                    $user = User::create([
                        'name' => $ligne['nom'],
                        'email' => $ligne['email'],
                        'phone' => $ligne['telephone'],
                        // Personne ne connait ce mot de passe : l'eleve choisit le sien par le lien.
                        'password' => Hash::make(Str::random(40)),
                    ]);
                    $jeton = Password::broker()->createToken($user);
                    $bilan['crees']++;
                } else {
                    if ($ligne['telephone'] && ! $user->phone) {
                        $user->forceFill(['phone' => $ligne['telephone']])->save();
                    }
                    $bilan['rattaches']++;
                }

                CenterUser::firstOrCreate(
                    ['center_id' => $centre->id, 'user_id' => $user->id],
                    ['role' => 'student', 'joined_at' => now()],
                );
                $classe->members()->syncWithoutDetaching([$user->id => ['role_in_class' => 'student']]);

                $invitations[] = [$user, InvitationEleveNotification::pour($classe->setRelation('center', $centre), $user->email, $jeton)];
            }
        });

        // Apres la transaction : pas de courriel pour un import annule.
        foreach ($invitations as [$user, $notification]) {
            $user->notify($notification);
        }

        return $bilan;
    }

    /** @return array{0:string, 1:?string} */
    private function statut(LanguageCenter $centre, Classroom $classe, string $email, array $vus): array
    {
        if (isset($vus[$email])) {
            return ['doublon', null];
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            return ['nouveau', null];
        }

        $appartenances = CenterUser::where('user_id', $user->id)->get();
        if ($appartenances->contains(fn ($a) => $a->center_id !== $centre->id)) {
            return ['erreur', 'Ce compte appartient à un autre établissement.'];
        }
        $ici = $appartenances->firstWhere('center_id', $centre->id);
        if ($ici && $ici->role !== 'student') {
            return ['erreur', "Ce compte fait partie de l'équipe du centre, pas des élèves."];
        }
        if ($classe->members()->where('users.id', $user->id)->exists()) {
            return ['doublon', null];
        }

        return ['existant', null];
    }

    private function dejaEleve(LanguageCenter $centre, string $email): bool
    {
        return CenterUser::where('center_id', $centre->id)->where('role', 'student')
            ->whereHas('user', fn ($q) => $q->where('email', $email))->exists();
    }
}
