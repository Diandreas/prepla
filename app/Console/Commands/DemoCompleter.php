<?php

namespace App\Console\Commands;

use App\Models\Assignment;
use App\Models\CenterUser;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\Exercise;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserError;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;
use App\Services\AI\CorrectionsDeProduction;
use App\Services\AI\DiagnosticDeProduction;
use App\Services\AI\WritingCorrectorService;
use App\Support\Demo\CopiesDeDemo;
use Carbon\Carbon;
use Database\Seeders\B2BDemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * ENRICHIT le centre de demonstration, sans rien supprimer.
 *
 * La demo n'avait que des classes DELF, et des eleves sans aucune erreur ni
 * production corrigee : la fiche d'un eleve affichait « Aucune faiblesse » et
 * aucun profil. Or les centres visites preparent surtout Goethe, telc, TCF, TEF
 * et IELTS. Cette commande ajoute ces classes, leurs eleves, leurs devoirs, un
 * historique credible sur six semaines (sur de VRAIS exercices du vivier), les
 * erreurs et les productions corrigees qui nourrissent la fiche — et donne aux
 * eleves DELF deja la des erreurs et des productions corrigees.
 *
 * TROIS REGLES :
 *
 * 1. Sans --appliquer, rien n'est ecrit : tout est fait dans une transaction
 *    annulee a la fin, et la commande dit ce qu'elle aurait cree.
 * 2. Rien n'est supprime ni modifie, sauf des champs VIDES : la salle et
 *    l'horaire d'une classe, le retour d'une production jamais corrigee. Le
 *    nombre de places n'est augmente que sur --agrandir-sieges.
 * 3. Relancee, elle ne cree pas de doublon : un eleve se reconnait a son
 *    e-mail, une classe a son nom dans le centre, une tentative ajoutee a son
 *    marqueur (feedback[].origine), une erreur a sa cle.
 *
 * Contrairement au seeder de la demo (qui SUPPRIME le centre) et a
 * prepla:demo-marque, elle tourne en production : c'est son but. Le garde-fou
 * est le centre lui-meme : tous ses membres doivent etre des comptes de demo, et
 * il ne doit avoir aucun paiement ni abonnement reel.
 */
class DemoCompleter extends Command
{
    protected $signature = 'prepla:demo-completer
        {--centre= : numero du centre de demonstration (obligatoire)}
        {--appliquer : ecrit vraiment ; sans cette option, simple rapport}
        {--agrandir-sieges : augmenter le nombre de places si les nouveaux eleves n en ont pas assez}
        {--sans-ia : ne pas faire corriger par l IA les productions deja ecrites et jamais corrigees}';

    protected $description = 'Ajoute au centre de demonstration des classes Goethe, telc, TCF, TEF, IELTS, leurs eleves et leur suivi (rapport seulement sans --appliquer)';

    public const DOMAINE = '@demo.prepla.local';

    /** Le marqueur pose dans chaque retour de tentative ajoutee : la cle de l'idempotence. */
    public const ORIGINE = 'demo-completer';

    private const NIVEAUX = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    /** Les profils d'eleves, dans l'ordre ou on les distribue dans une classe. */
    private const PROFILS = ['regulier', 'progresse', 'decroche', 'ecrit_fort', 'moyen', 'regulier', 'absent', 'moyen', 'progresse'];

    /** Les reponses a choix fixes qui ne sont pas des lettres. */
    private const JEUX_FERMES = [
        ['true', 'false', 'not given'],
        ['yes', 'no', 'not given'],
        ['vrai', 'faux', 'on ne sait pas'],
        ['richtig', 'falsch'],
        ['ja', 'nein'],
    ];

    /** Salle et horaire des classes deja la, si elles n'en ont pas. */
    private const CRENEAUX_EXISTANTS = [
        ['Salle 1', 'Lun et mer 8 h – 10 h'],
        ['Salle 1', 'Mar et jeu 17 h – 19 h'],
        ['Salle 2', 'Lun au jeu 10 h – 12 h'],
        ['Salle 2', 'Ven 15 h – 18 h'],
        ['Salle 5', 'Sam 13 h – 16 h'],
    ];

    /**
     * Les classes ajoutees : examen (slug puis debut du nom), niveau, salle,
     * horaire, et leurs eleves. Les noms sont camerounais, de toutes les
     * regions ; l'IELTS est surtout prepare par des anglophones du Nord-Ouest.
     */
    private const CLASSES = [
        [
            'nom' => 'Goethe A1 — Samedi matin', 'examen' => ['goethe', 'Goethe'], 'niveau' => 'A1',
            'salle' => 'Salle 3', 'horaire' => 'Sam 8 h – 12 h',
            'eleves' => ['Brice Atangana', 'Larissa Mbarga', 'Steve Fotso', 'Carine Ngo Bassa', 'Hervé Kamga', 'Aïcha Bello', 'Junior Essomba', 'Danielle Tchoumi'],
        ],
        [
            'nom' => 'Goethe B1 — Soir', 'examen' => ['goethe', 'Goethe'], 'niveau' => 'B1',
            'salle' => 'Salle 3', 'horaire' => 'Mar et jeu 17 h 30 – 19 h 30',
            'eleves' => ['Ulrich Nguema', 'Prisca Djuikom', 'Franck Tchatchoua', 'Murielle Abena', 'Arnaud Kouam', 'Sandrine Onana', 'Rodrigue Mvondo', 'Estelle Nkeng'],
        ],
        [
            'nom' => 'telc B1 — Intensif', 'examen' => ['telc', 'telc'], 'niveau' => 'B1',
            'salle' => 'Salle 4', 'horaire' => 'Lun au ven 8 h – 11 h',
            'eleves' => ['Christian Wamba', 'Gaëlle Ngono', 'Yannick Feudjio', 'Mireille Ekotto', 'Boris Tagne', 'Nadège Mballa', 'Cédric Djomo', 'Raïssa Owona', 'Ibrahim Moussa'],
        ],
        [
            'nom' => 'TCF Canada — Soir', 'examen' => ['tcf', 'TCF'], 'niveau' => 'B2',
            'salle' => 'Salle 2', 'horaire' => 'Lun et mer 18 h – 20 h',
            'eleves' => ['Patrick Nana', 'Stéphanie Biloa', 'Joël Tsafack', 'Viviane Ndzana', 'Landry Simo', 'Armelle Eyenga', 'Hamadou Oumarou'],
        ],
        [
            'nom' => 'TEF Canada — Week-end', 'examen' => ['tef', 'TEF'], 'niveau' => 'B1',
            'salle' => 'Salle 2', 'horaire' => 'Sam 14 h – 17 h',
            'eleves' => ['Serge Ewane', 'Laure Manga', 'Thierry Kenfack', 'Josiane Belinga', 'William Ndjock', 'Clarisse Ngassa', 'Fadimatou Adamou'],
        ],
        [
            'nom' => 'IELTS — Préparation', 'examen' => ['ielts', 'IELTS'], 'niveau' => 'B2',
            'salle' => 'Salle 5', 'horaire' => 'Mar et jeu 16 h – 18 h',
            'eleves' => ['Emmanuel Fon', 'Blessing Ngwa', 'Divine Tanyi', 'Glory Achu', 'Peter Nkwenti', 'Mercy Ambe', 'Collins Tabi', 'Precious Ngum'],
        ],
    ];

    private bool $appliquer = false;

    private bool $avecIa = true;

    private ?string $motDePasse = null;

    private ?int $placesRestantes = null;

    /** @var array<string, int> */
    private array $compte = [];

    /** @var array<int, list<string>> les cles des copies deja rendues, par eleve */
    private array $copiesEcrites = [];

    /** @var array<int, array<string, int>> fois qu'une copie a ete rendue, par classe */
    private array $copiesParClasse = [];

    /** @var array<string, int> fois qu'une copie a ete rendue dans le centre */
    private array $copiesDuCentre = [];

    /** La classe de l'eleve en cours : deux eleves d'une classe ne rendent pas la meme copie. */
    private ?int $classeEnCours = null;

    /**
     * Les productions deja ecrites a faire corriger par l'IA, APRES la
     * transaction : un appel HTTP ne doit pas tenir le verrou d'ecriture de
     * SQLite, ou les vrais apprenants attendraient.
     *
     * @var list<array<string, mixed>>
     */
    private array $aCorriger = [];

    /** @var array<int, array<string, Collection>> viviers deja lus, par examen et niveau */
    private array $viviers = [];

    public function handle(WritingCorrectorService $correcteur): int
    {
        $this->appliquer = (bool) $this->option('appliquer');
        $this->avecIa = ! $this->option('sans-ia');
        $this->compte = array_fill_keys([
            'classes_creees', 'classes_deja_la', 'examens_absents', 'salles_remplies', 'eleves_crees', 'eleves_deja_la',
            'eleves_sans_place', 'devoirs', 'tentatives', 'erreurs', 'productions_corrigees_ia', 'productions_a_corriger_ia',
            'eleves_delf_enrichis',
        ], 0);
        $this->viviers = [];
        $this->copiesEcrites = [];
        $this->copiesParClasse = [];
        $this->copiesDuCentre = [];
        $this->classeEnCours = null;
        $this->aCorriger = [];
        $this->motDePasse = null;

        $centre = $this->option('centre') ? LanguageCenter::find((int) $this->option('centre')) : null;
        if (! $centre) {
            $this->error('Indiquez le centre de démonstration : --centre=ID (centre introuvable).');

            return self::FAILURE;
        }

        $refus = $this->refus($centre);
        if ($refus !== []) {
            $this->error("Centre « {$centre->name} » (#{$centre->id}) refusé : ce n’est pas un centre de démonstration.");
            foreach ($refus as $raison) {
                $this->line('  - '.$raison);
            }

            return self::FAILURE;
        }

        $this->line(sprintf('Centre « %s » (#%d)%s', $centre->name, $centre->id, $this->appliquer ? '' : ' — RAPPORT : rien ne sera écrit.'));

        DB::beginTransaction();
        try {
            $this->completer($centre, $correcteur);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
        $this->appliquer ? DB::commit() : DB::rollBack();

        // Hors transaction : chaque correction par l'IA ecrit ensuite seule.
        $this->corrigerParIa($correcteur);

        $c = $this->compte;
        $this->newLine();
        $this->line($this->appliquer ? 'Ce qui a été ajouté :' : 'Ce qui serait ajouté :');
        $this->line("  Classes : {$c['classes_creees']} créées, {$c['classes_deja_la']} déjà là, {$c['examens_absents']} sautées (examen absent).");
        $this->line("  Salle et horaire remplis : {$c['salles_remplies']} classe(s).");
        $this->line("  Élèves : {$c['eleves_crees']} créés, {$c['eleves_deja_la']} déjà là, {$c['eleves_sans_place']} sans place.");
        $this->line("  Devoirs : {$c['devoirs']}.");
        $this->line("  Tentatives : {$c['tentatives']} ; erreurs retenues : {$c['erreurs']}.");
        $this->line("  Élèves déjà présents enrichis (erreurs et productions corrigées) : {$c['eleves_delf_enrichis']}.");
        if ($c['productions_corrigees_ia'] > 0 || $c['productions_a_corriger_ia'] > 0) {
            $this->line("  Productions déjà écrites, jamais corrigées : {$c['productions_corrigees_ia']} corrigées par l’IA, {$c['productions_a_corriger_ia']} à corriger.");
        }
        if ($c['eleves_sans_place'] > 0) {
            $this->warn('  Places insuffisantes : relancez avec --agrandir-sieges pour ajouter les élèves manquants.');
        }
        $this->appliquer
            ? $this->info('Terminé. Rien n’a été supprimé.')
            : $this->warn('Rapport seulement : relancez avec --appliquer pour écrire.');

        return self::SUCCESS;
    }

    /**
     * Pourquoi ce centre n'est pas un centre de demonstration.
     *
     * @return list<string>
     */
    private function refus(LanguageCenter $centre): array
    {
        $raisons = [];
        $membres = CenterUser::where('center_id', $centre->id)->pluck('user_id');

        $horsDemo = User::whereIn('id', $membres)->where('email', 'not like', '%'.self::DOMAINE)->count();
        if ($horsDemo > 0) {
            $raisons[] = "{$horsDemo} membre(s) sans adresse en ".self::DOMAINE.'.';
        }

        if (Schema::hasTable('subscriptions') && DB::table('subscriptions')->whereIn('user_id', $membres)->exists()) {
            $raisons[] = 'des membres ont un abonnement.';
        }
        if (Schema::hasTable('center_commissions') && DB::table('center_commissions')->where('center_id', $centre->id)->exists()) {
            $raisons[] = 'le centre a des commissions sur des paiements Stripe.';
        }
        if (Schema::hasTable('center_statements') && DB::table('center_statements')->where('center_id', $centre->id)->where('statut', 'paye')->exists()) {
            $raisons[] = 'le centre a des relevés payés.';
        }
        if (Schema::hasTable('center_access_grants')) {
            // Un acces vendu a un compte de demo, pendant une presentation, n'est
            // pas un paiement reel ; un acces vendu a quelqu'un d'autre, si.
            $demo = User::whereIn('id', $membres)->where('email', 'like', '%'.self::DOMAINE)->pluck('id');
            $reels = DB::table('center_access_grants')->where('center_id', $centre->id)
                ->where(fn ($q) => $q->whereNull('user_id')->orWhereNotIn('user_id', $demo))
                ->where('amount', '>', 0)
                ->count();
            if ($reels > 0) {
                $raisons[] = "{$reels} accès payé(s) par des comptes hors démonstration.";
            }
        }

        return $raisons;
    }

    private function completer(LanguageCenter $centre, WritingCorrectorService $correcteur): void
    {
        $avecSalle = Schema::hasColumn('classrooms', 'room') && Schema::hasColumn('classrooms', 'schedule');
        $enseignants = $this->enseignants($centre);
        $this->placesRestantes = $this->places($centre);

        // Les classes deja la : salle et horaire, seulement s'ils sont vides.
        $existantes = Classroom::where('center_id', $centre->id)->orderBy('id')->get();
        if ($avecSalle) {
            $nomsAjoutes = array_column(self::CLASSES, 'nom');
            $rang = 0;
            foreach ($existantes as $classe) {
                if (in_array($classe->name, $nomsAjoutes, true)) {
                    continue;
                }
                [$salle, $horaire] = self::CRENEAUX_EXISTANTS[$rang++ % count(self::CRENEAUX_EXISTANTS)];
                $vide = [];
                if (blank($classe->room)) {
                    $vide['room'] = $salle;
                }
                if (blank($classe->schedule)) {
                    $vide['schedule'] = $horaire;
                }
                if ($vide !== []) {
                    // Requete directe : remplir un champ vide ne change pas la date de la classe.
                    DB::table('classrooms')->where('id', $classe->id)->update($vide);
                    $this->compte['salles_remplies']++;
                }
            }
        }

        $traites = collect();

        foreach (self::CLASSES as $spec) {
            $examen = $this->examen($spec['examen']);
            if (! $examen) {
                $this->warn("Examen « {$spec['examen'][1]} » absent de la base : classe « {$spec['nom']} » sautée.");
                $this->compte['examens_absents']++;

                continue;
            }

            $classe = Classroom::where('center_id', $centre->id)->where('name', $spec['nom'])->first();
            if ($classe) {
                $this->compte['classes_deja_la']++;
                if ($avecSalle) {
                    $vide = array_filter(['room' => blank($classe->room) ? $spec['salle'] : null, 'schedule' => blank($classe->schedule) ? $spec['horaire'] : null]);
                    if ($vide !== []) {
                        DB::table('classrooms')->where('id', $classe->id)->update($vide);
                        $this->compte['salles_remplies']++;
                    }
                }
            } else {
                $classe = Classroom::create(array_merge([
                    'center_id' => $centre->id,
                    'name' => $spec['nom'],
                    'level' => $spec['niveau'],
                    'exam_id' => $examen->id,
                    'invite_code' => Classroom::generateInviteCode(),
                ], $avecSalle ? ['room' => $spec['salle'], 'schedule' => $spec['horaire']] : []));
                $ouverte = Carbon::now()->subWeeks(8);
                DB::table('classrooms')->where('id', $classe->id)->update(['created_at' => $ouverte, 'updated_at' => $ouverte]);
                $this->compte['classes_creees']++;
                $this->line("  + classe « {$spec['nom']} » ({$examen->name}, {$spec['niveau']})");
            }

            $enseignant = $enseignants->isNotEmpty() ? $enseignants[($this->compte['classes_creees'] + $this->compte['classes_deja_la'] - 1) % $enseignants->count()] : null;
            if ($enseignant && ! $classe->teachers()->exists()) {
                $classe->members()->attach($enseignant->id, ['role_in_class' => 'teacher']);
            }

            $langue = $this->langue($examen);
            $vivier = $this->vivier($examen, $spec['niveau']);
            if ($vivier['fermes']->isEmpty() && $vivier['ecrits']->isEmpty() && $vivier['oraux']->isEmpty()) {
                $this->warn("  Aucun exercice {$examen->name} utilisable autour du {$spec['niveau']} : élèves sans historique.");
            }

            $devoirs = $this->devoirs($classe, $vivier, $enseignant ?? $enseignants->first());
            $this->classeEnCours = $classe->id;

            foreach ($spec['eleves'] as $i => $nom) {
                $eleve = $this->eleve($centre, $classe, $examen, $spec['niveau'], $nom, $i);
                if (! $eleve) {
                    continue;
                }
                $traites->push($eleve->id);

                if ($this->dejaComplete($eleve)) {
                    continue;
                }
                $this->historique($eleve, self::PROFILS[$i % count(self::PROFILS)], $vivier, $devoirs, $langue, $spec['niveau']);
            }
        }

        // Les eleves deja la (DELF) : des erreurs et des productions corrigees,
        // sans toucher a leurs tentatives.
        $autres = User::whereIn('id', CenterUser::where('center_id', $centre->id)->where('role', 'student')->pluck('user_id'))
            ->whereNotIn('id', $traites)
            ->where('email', 'like', '%'.self::DOMAINE)
            ->orderBy('id')
            ->get();
        foreach ($autres as $eleve) {
            $this->enrichir($centre, $eleve, $correcteur);
        }
    }

    /** @return Collection<int, User> */
    private function enseignants(LanguageCenter $centre): Collection
    {
        $ids = CenterUser::where('center_id', $centre->id)->where('role', 'teacher')->pluck('user_id');
        $profs = User::whereIn('id', $ids)->where('email', 'like', 'prof.%'.self::DOMAINE)->orderBy('id')->get();

        return $profs->isNotEmpty() ? $profs : User::whereIn('id', $ids)->orderBy('id')->get();
    }

    /** Les places libres, apres agrandissement si demande. Null : sans limite. */
    private function places(LanguageCenter $centre): ?int
    {
        if ($centre->seats_limit === null) {
            return null;
        }

        $nouveaux = 0;
        foreach (self::CLASSES as $spec) {
            foreach ($spec['eleves'] as $nom) {
                if (! User::where('email', $this->email($nom))->exists()) {
                    $nouveaux++;
                }
            }
        }

        $libres = $centre->seatsAvailable();
        if ($nouveaux > $libres && $this->option('agrandir-sieges')) {
            $limite = $centre->seatsUsed() + $nouveaux + 5;
            DB::table('language_centers')->where('id', $centre->id)->update(['seats_limit' => $limite]);
            $this->line("  Places : {$centre->seats_limit} → {$limite}.");

            return $limite - $centre->seatsUsed();
        }

        if ($nouveaux > $libres) {
            $this->warn("  Places : {$libres} libre(s) pour {$nouveaux} nouvel(le)s élève(s).");
        }

        return $libres;
    }

    /** @param  array{0: string, 1: string}  $cle */
    private function examen(array $cle): ?Exam
    {
        return Exam::where('slug', $cle[0])->first()
            ?? Exam::where('name', 'like', $cle[1].'%')->orderBy('id')->first();
    }

    private function langue(?Exam $examen): string
    {
        $slug = $examen?->language?->slug;
        if (in_array($slug, ['german', 'french', 'english'], true)) {
            return $slug;
        }

        return match ($examen?->slug) {
            'goethe', 'telc', 'testdaf', 'osd' => 'german',
            'ielts', 'toefl', 'cambridge' => 'english',
            default => 'french',
        };
    }

    private function email(string $nom): string
    {
        [$prenom, $famille] = array_pad(explode(' ', $nom, 2), 2, '');

        return Str::slug($prenom).'.'.Str::slug($famille).self::DOMAINE;
    }

    /** Le meme mot de passe que les comptes de demo deja la. */
    private function motDePasse(LanguageCenter $centre): string
    {
        if ($this->motDePasse !== null) {
            return $this->motDePasse;
        }

        $membre = User::whereIn('id', CenterUser::where('center_id', $centre->id)->pluck('user_id'))
            ->where('email', 'like', '%'.self::DOMAINE)
            ->orderByRaw("CASE WHEN email LIKE 'demo-admin%' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();
        $empreinte = $membre ? DB::table('users')->where('id', $membre->id)->value('password') : null;

        return $this->motDePasse = is_string($empreinte) && Hash::isHashed($empreinte)
            ? $empreinte
            : Hash::make(B2BDemoSeeder::DEMO_PASSWORD);
    }

    private function eleve(LanguageCenter $centre, Classroom $classe, Exam $examen, string $niveau, string $nom, int $rang): ?User
    {
        $email = $this->email($nom);
        $eleve = User::where('email', $email)->first();

        if ($eleve) {
            $membre = CenterUser::where('center_id', $centre->id)->where('user_id', $eleve->id)->exists();
            if (! $membre) {
                $this->warn("  {$email} existe hors du centre : laissé tel quel.");

                return null;
            }
            if (! $classe->members()->where('users.id', $eleve->id)->exists()) {
                $classe->members()->attach($eleve->id, ['role_in_class' => 'student']);
            }
            $this->compte['eleves_deja_la']++;

            return $eleve;
        }

        if ($this->placesRestantes !== null && $this->placesRestantes <= 0) {
            $this->compte['eleves_sans_place']++;

            return null;
        }

        mt_srand(crc32('inscription|'.$email));
        $inscrit = Carbon::now()->subDays(mt_rand(48, 75))->setTime(mt_rand(8, 17), mt_rand(0, 59));

        $id = DB::table('users')->insertGetId([
            'name' => $nom,
            'email' => $email,
            'password' => $this->motDePasse($centre),
            'email_verified_at' => $inscrit,
            'created_at' => $inscrit,
            'updated_at' => $inscrit,
        ]);
        $eleve = User::findOrFail($id);

        $profil = self::PROFILS[$rang % count(self::PROFILS)];
        UserProfile::create([
            'user_id' => $eleve->id,
            'target_exam_id' => $examen->id,
            'current_level' => $niveau,
            'onboarding_completed_at' => $inscrit->copy()->addMinutes(20),
            'xp_total' => match ($profil) {
                'regulier' => mt_rand(600, 1200),
                'absent' => 0,
                'decroche' => mt_rand(60, 180),
                default => mt_rand(200, 600),
            },
            'streak_current' => $profil === 'regulier' ? mt_rand(4, 14) : ($profil === 'progresse' ? mt_rand(1, 5) : 0),
        ]);

        // Par le modele : l'observateur ouvre la periode d'appartenance.
        CenterUser::create([
            'center_id' => $centre->id,
            'user_id' => $eleve->id,
            'role' => 'student',
            'joined_at' => $inscrit,
        ]);
        $classe->members()->attach($eleve->id, ['role_in_class' => 'student']);

        if ($this->placesRestantes !== null) {
            $this->placesRestantes--;
        }
        $this->compte['eleves_crees']++;

        return $eleve;
    }

    /** L'eleve a-t-il deja les tentatives de cette commande ? */
    private function dejaComplete(User $eleve): bool
    {
        return UserExerciseAttempt::where('user_id', $eleve->id)
            // Sans les guillemets : une base qui range le JSON avec des espaces
            // (« "origine": "demo-completer" ») le trouve aussi.
            ->where('feedback', 'like', '%'.self::ORIGINE.'%')
            ->exists();
    }

    /**
     * Les exercices du vivier pour un examen et un niveau, repli sur les niveaux
     * voisins : questions fermees, redactions, prises de parole.
     *
     * @return array{fermes: Collection<int, Exercise>, ecrits: Collection<int, Exercise>, oraux: Collection<int, Exercise>}
     */
    private function vivier(Exam $examen, string $niveau): array
    {
        $cle = $examen->id.'|'.$niveau;
        if (isset($this->viviers[$cle])) {
            return $this->viviers[$cle];
        }

        $rang = array_search($niveau, self::NIVEAUX, true);
        $rang = $rang === false ? 2 : $rang;
        $vivier = ['fermes' => collect(), 'ecrits' => collect(), 'oraux' => collect()];

        foreach ([[0], [-1, 1], [-2, 2]] as $ecarts) {
            $niveaux = [];
            foreach ($ecarts as $e) {
                if (isset(self::NIVEAUX[$rang + $e])) {
                    $niveaux[] = self::NIVEAUX[$rang + $e];
                }
            }

            // Le vivier partage seulement : jamais l'exercice prive d'un centre,
            // ni celui d'une epreuve blanche, d'une lecon ou d'un parcours. Une
            // tentative y fige ce que les outils de maintenance ont le droit de
            // refaire (prepla:compose-mock-exams ne touche plus une epreuve
            // « deja travaillee ») : de fausses tentatives n'ont rien a y faire.
            $exercices = Exercise::with('exerciseType')
                ->where('exam_id', $examen->id)
                ->whereNull('center_id')
                ->whereNull('mock_exam_id')
                ->whereNull('lesson_id')
                ->whereNull('node_id')
                ->whereIn('difficulty', $niveaux)
                ->orderBy('id')
                ->limit(300)
                ->get();

            foreach ($exercices as $exercice) {
                $sorte = $this->sorte($exercice);
                if ($sorte && $vivier[$sorte]->count() < 30) {
                    $vivier[$sorte]->push($exercice);
                }
            }

            if ($vivier['fermes']->count() >= 6 && $vivier['ecrits']->isNotEmpty() && $vivier['oraux']->isNotEmpty()) {
                break;
            }
        }

        return $this->viviers[$cle] = $vivier;
    }

    /** 'fermes', 'ecrits', 'oraux', ou null pour un format qu'on ne sait pas remplir. */
    private function sorte(Exercise $exercice): ?string
    {
        $competence = $exercice->exerciseType?->skill_type;
        $composant = $exercice->exerciseType?->component_key;
        $questions = is_array($exercice->questions) ? array_values($exercice->questions) : [];
        if ($questions === [] || ! is_array($questions[0])) {
            return null;
        }

        if (in_array($competence, ['writing', 'speaking'], true)) {
            $premiere = $questions[0];
            $evaluee = in_array($composant, Exercise::AI_EVALUATED_TYPES, true)
                || in_array($premiere['type'] ?? null, Exercise::AI_EVALUATED_TYPES, true);

            return $evaluee && isset($premiere['id']) ? ($competence === 'writing' ? 'ecrits' : 'oraux') : null;
        }

        if (! in_array($competence, ['reading', 'listening', 'grammar', 'vocabulary'], true)) {
            return null;
        }

        foreach ($questions as $q) {
            if (! is_array($q) || ! isset($q['id']) || ! $this->enonce($q)) {
                return null;
            }
            $juste = $q['correct_answer'] ?? null;
            if (! is_scalar($juste) || trim((string) $juste) === '') {
                return null;
            }
            $options = Exercise::optionList($q['options'] ?? []);
            if ($options !== [] && $this->rangJuste($juste, $options) === null) {
                return null;
            }
        }

        return 'fermes';
    }

    private function enonce(array $q): ?string
    {
        foreach (['text', 'prompt', 'statement', 'question'] as $cle) {
            if (is_string($q[$cle] ?? null) && trim($q[$cle]) !== '') {
                return trim($q[$cle]);
            }
        }

        return null;
    }

    /** Le rang de la bonne reponse parmi les options : lettre « B » ou texte de l'option. */
    private function rangJuste(mixed $juste, array $options): ?int
    {
        $juste = trim((string) $juste);
        if (preg_match('/^[A-Ha-h]$/', $juste)) {
            $rang = ord(strtoupper($juste)) - 65;

            return $rang < count($options) ? $rang : null;
        }
        foreach ($options as $i => $option) {
            if (mb_strtolower(trim((string) $option)) === mb_strtolower($juste)) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Deux devoirs par nouvelle classe, autour d'aujourd'hui : un en retard pour
     * certains, un en cours.
     *
     * @return Collection<int, Assignment>
     */
    private function devoirs(Classroom $classe, array $vivier, ?User $auteur): Collection
    {
        $plan = [
            ['titre' => 'Devoir 1 — Compréhension', 'publie' => Carbon::now()->subDays(6)->setTime(9, 0), 'rendu' => Carbon::now()->subDay()->setTime(20, 0), 'exercices' => $vivier['fermes']->take(2)],
            ['titre' => 'Devoir 2 — Expression écrite', 'publie' => Carbon::now()->subDays(2)->setTime(9, 0), 'rendu' => Carbon::now()->addDays(5)->setTime(20, 0), 'exercices' => $vivier['ecrits']->take(1)->concat($vivier['fermes']->slice(2, 1))],
        ];

        $devoirs = collect();
        foreach ($plan as $p) {
            $devoir = Assignment::where('classroom_id', $classe->id)->where('title', $p['titre'])->first();
            if (! $devoir) {
                if ($p['exercices']->isEmpty() || ! $auteur) {
                    continue;
                }
                $devoir = Assignment::create([
                    'classroom_id' => $classe->id,
                    'created_by' => $auteur->id,
                    'title' => $p['titre'],
                    'instructions' => 'Faites les exercices ci-joints avant la date limite.',
                    'due_at' => $p['rendu'],
                    'published_at' => $p['publie'],
                ]);
                foreach ($p['exercices']->values() as $i => $exercice) {
                    $devoir->items()->create(['itemable_type' => Exercise::class, 'itemable_id' => $exercice->id, 'sort_order' => $i]);
                }
                DB::table('assignments')->where('id', $devoir->id)->update(['created_at' => $p['publie'], 'updated_at' => $p['publie']]);
                $devoir->refresh();
                $this->compte['devoirs']++;
            }
            $devoirs->push($devoir->load('items'));
        }

        return $devoirs;
    }

    /**
     * Six semaines de travail, selon le profil de l'eleve.
     *
     * @param  array{fermes: Collection, ecrits: Collection, oraux: Collection}  $vivier
     */
    private function historique(User $eleve, string $profil, array $vivier, Collection $devoirs, string $langue, string $niveau): void
    {
        if ($profil === 'absent' || ($vivier['fermes']->isEmpty() && $vivier['ecrits']->isEmpty() && $vivier['oraux']->isEmpty())) {
            return;
        }

        mt_srand(crc32('historique|'.$eleve->email));

        [$nombre, $recent] = match ($profil) {
            'regulier' => [mt_rand(24, 34), mt_rand(0, 2)],
            'progresse' => [mt_rand(16, 24), mt_rand(0, 3)],
            'ecrit_fort' => [mt_rand(14, 22), mt_rand(0, 4)],
            'decroche' => [mt_rand(7, 12), mt_rand(10, 15)],
            default => [mt_rand(10, 18), mt_rand(1, 5)],
        };

        for ($i = 0; $i < $nombre; $i++) {
            $jours = $i === 0 ? $recent : mt_rand($recent, 42);
            $date = $this->moment($jours);

            // Une production sur cinq environ, et toujours une redaction et une
            // prise de parole parmi les plus recentes : le profil les lit.
            $sorte = match (true) {
                $i === 1 => 'ecrits',
                $i === 3 => 'oraux',
                $i % 5 === 4 => mt_rand(0, 1) ? 'ecrits' : 'oraux',
                default => 'fermes',
            };
            $exercices = $vivier[$sorte]->isNotEmpty() ? $vivier[$sorte] : ($vivier['fermes']->isNotEmpty() ? $vivier['fermes'] : $vivier['ecrits']->concat($vivier['oraux']));
            $exercice = $exercices[mt_rand(0, $exercices->count() - 1)];

            $this->tentative($eleve, $exercice, $this->precision($profil, $exercice, $jours), $date, $langue, $niveau, $profil === 'progresse' && $jours > 21);
        }

        // Les devoirs deja publies, faits selon le profil.
        foreach ($devoirs->values() as $rang => $devoir) {
            $chance = match ($profil) {
                'regulier' => [90, 70],
                'ecrit_fort' => [80, 60],
                'progresse' => [70, 50],
                'moyen' => [60, 30],
                default => [0, 0],
            };
            if (! $devoir->published_at || $devoir->published_at->isFuture() || mt_rand(1, 100) > ($chance[$rang] ?? 0)) {
                continue;
            }
            foreach ($devoir->items as $item) {
                $exercice = Exercise::with('exerciseType')->find($item->itemable_id);
                if (! $exercice) {
                    continue;
                }
                $fait = $devoir->published_at->copy()->addHours(mt_rand(3, 40))->addMinutes(mt_rand(0, 59));
                if ($fait->isFuture()) {
                    $fait = Carbon::now()->subMinutes(mt_rand(20, 180));
                }
                $this->tentative($eleve, $exercice, $this->precision($profil, $exercice, (int) $fait->diffInDays(Carbon::now())), $fait, $langue, $niveau, false);
            }
        }
    }

    /** Un moment de la journee, il y a tant de jours, jamais dans le futur. */
    private function moment(int $jours): Carbon
    {
        if ($jours === 0) {
            return Carbon::now()->subMinutes(mt_rand(30, 360));
        }

        // Entre 6 h et 21 h a Douala (UTC+1).
        return Carbon::now()->subDays($jours)->setTime(mt_rand(5, 20), mt_rand(0, 59), mt_rand(0, 59));
    }

    private function precision(string $profil, Exercise $exercice, int $jours): float
    {
        $competence = $exercice->exerciseType?->skill_type;

        $p = match ($profil) {
            'regulier' => mt_rand(72, 94),
            'moyen' => mt_rand(50, 74),
            'decroche' => mt_rand(36, 62),
            // De 45 % il y a six semaines a 85 % aujourd'hui.
            'progresse' => 45 + (42 - min(42, $jours)) / 42 * 40 + mt_rand(-6, 6),
            'ecrit_fort' => in_array($competence, ['speaking', 'listening'], true) ? mt_rand(35, 55) : mt_rand(72, 90),
            default => mt_rand(50, 80),
        };

        return (float) max(5, min(100, $p));
    }

    /**
     * Une tentative coherente avec sa precision : ses reponses, la correction
     * que l'application aurait enregistree, et les erreurs qu'elle laisse.
     */
    private function tentative(User $eleve, Exercise $exercice, float $precision, Carbon $date, string $langue, string $niveau, bool $depuisMaitrise): void
    {
        $competence = $exercice->exerciseType?->skill_type;
        $questions = array_values((array) $exercice->questions);

        if (in_array($competence, ['writing', 'speaking'], true)) {
            $dejaEcrites = $this->copiesEcrites[$eleve->id] ?? [];
            $classe = $this->classeEnCours ?? 0;
            // Une seule redaction et une seule prise de parole corrigees par
            // eleve : les copies sont en nombre fini, et a deux par eleve une
            // classe de huit en rendrait deux fois les memes.
            $dejaCetteCompetence = collect($dejaEcrites)->contains(fn (string $cle) => str_contains($cle, '|'.$competence.'|'));
            $copie = $dejaCetteCompetence ? null : CopiesDeDemo::copie(
                $langue, $competence, (string) ($exercice->difficulty ?: $niveau), $precision,
                $this->prenom($eleve), $dejaEcrites, $this->copiesParClasse[$classe] ?? [], $this->copiesDuCentre,
            );
            if (! $copie) {
                // Plus de copie pour lui : une question fermee a la place.
                if ($autre = $this->autreQuestionFermee($exercice)) {
                    $this->tentative($eleve, $autre, $precision, $date, $langue, $niveau, $depuisMaitrise);
                }

                return;
            }
            $this->copiesEcrites[$eleve->id][] = $copie['cle'];
            $this->copiesParClasse[$classe][$copie['cle']] = ($this->copiesParClasse[$classe][$copie['cle']] ?? 0) + 1;
            $this->copiesDuCentre[$copie['cle']] = ($this->copiesDuCentre[$copie['cle']] ?? 0) + 1;
            $oral = $competence === 'speaking';
            $qid = (string) $questions[0]['id'];
            $note = round($precision / 100 * 9, 1);
            $ecart = fn (float $d) => max(0, min(9, round($note + $d, 1)));

            $this->enregistrer($eleve, $exercice, [$qid => $copie['texte']], [[
                'question_id' => $qid,
                'correct' => $precision >= 60,
                'accuracy' => $precision,
                'band_score' => $note,
                'sub_scores' => $oral ? [] : [
                    'task_achievement' => $ecart(0.5),
                    'coherence_cohesion' => $ecart(0),
                    'lexical_resource' => $ecart($copie['ecart_vocabulaire']),
                    'grammar_accuracy' => $ecart($copie['ecart_grammaire']),
                ],
                'corrections' => $copie['corrections'],
                'diagnostic' => DiagnosticDeProduction::nettoyer($copie['diagnostic']),
                'explanation' => $copie['bilan'],
                'transcription' => $oral ? $copie['texte'] : null,
                'technical_failure' => false,
                'origine' => self::ORIGINE,
            ]], $precision >= 60 ? 1 : 0, $precision, $oral ? mt_rand(150, 480) : mt_rand(900, 2100), $date);

            $this->erreursDeProduction($eleve, $copie['corrections'], $copie['texte'], $oral, $date, $depuisMaitrise);

            return;
        }

        $n = count($questions);
        if ($n === 0) {
            return;
        }
        $justes = (int) round($n * $precision / 100);
        $ordre = range(0, $n - 1);
        shuffle($ordre);
        $ratees = array_slice($ordre, 0, $n - $justes);

        $reponses = [];
        $retours = [];
        foreach ($questions as $i => $q) {
            $id = (string) $q['id'];
            $options = Exercise::optionList($q['options'] ?? []);
            $rate = in_array($i, $ratees, true);

            if ($options !== []) {
                $rangJuste = $this->rangJuste($q['correct_answer'], $options) ?? 0;
                $autres = array_values(array_diff(array_keys($options), [$rangJuste]));
                $rangDonne = $rate && $autres !== [] ? $autres[mt_rand(0, count($autres) - 1)] : $rangJuste;
                $donnee = chr(65 + $rangDonne);
                $attendue = (string) $options[$rangJuste];
                $lue = $donnee.') '.$options[$rangDonne];
                $attendueLue = chr(65 + $rangJuste).') '.$attendue;
            } else {
                $attendue = (string) $q['correct_answer'];
                $donnee = $rate ? $this->mauvaiseReponse($attendue) : $attendue;
                $lue = $donnee;
                $attendueLue = $attendue;
            }

            $reponses[$id] = $donnee;
            $retours[] = [
                'question_id' => $id,
                'correct' => ! $rate,
                'accuracy' => $rate ? 0 : 100,
                'correct_answer' => $attendue,
                'explanation' => is_string($q['explanation'] ?? null) ? $q['explanation'] : null,
                'origine' => self::ORIGINE,
            ];

            if ($rate) {
                [$categorie, $sous] = UserError::deriveCategory($this->concept($q, $competence), $competence, $exercice->exerciseType?->component_key);
                $this->erreur($eleve, $exercice->id, $id, [
                    'question_text' => $this->enonce($q),
                    'user_answer' => $lue,
                    'correct_answer' => $attendueLue,
                    'explanation' => is_string($q['explanation'] ?? null) ? $q['explanation'] : null,
                    'skill_type' => $competence,
                    'exercise_type_slug' => $exercice->exerciseType?->slug ?? 'mcq',
                    'error_category' => $categorie,
                    'subcategory' => $sous,
                ], $date, $depuisMaitrise);
            }
        }

        $this->enregistrer($eleve, $exercice, $reponses, $retours, $n - count($ratees), round(($n - count($ratees)) / $n * 100, 1), $n * mt_rand(25, 70), $date);
    }

    /**
     * Le point de langue que la question teste, quand elle le dit : sa propre
     * etiquette (« grammar.case »), sinon son concept (« articles ») pour une
     * question de grammaire ou de vocabulaire. Sans lui, la faute tombait dans
     * « Grammaire » sans sous-categorie et ne disait rien au centre.
     */
    private function concept(array $q, ?string $competence): ?string
    {
        $etiquette = $q['error_category'] ?? null;
        if (is_string($etiquette) && preg_match('/^(grammar|vocabulary|spelling|punctuation|coherence)(\.[a-z0-9_-]+)?$/', $etiquette)) {
            return $etiquette;
        }

        $concept = $q['concept'] ?? null;
        if (in_array($competence, ['grammar', 'vocabulary'], true) && is_string($concept) && Str::slug($concept) !== '') {
            return $competence.'.'.Str::slug($concept);
        }

        return null;
    }

    /** Le prenom de l'eleve, qui signe ses copies. */
    private function prenom(User $eleve): string
    {
        return (string) Str::of((string) $eleve->name)->trim()->before(' ');
    }

    /** Une question fermee du meme examen, dans les viviers deja lus. */
    private function autreQuestionFermee(Exercise $exercice): ?Exercise
    {
        foreach ($this->viviers as $cle => $vivier) {
            if (str_starts_with($cle, $exercice->exam_id.'|') && $vivier['fermes']->isNotEmpty()) {
                return $vivier['fermes'][mt_rand(0, $vivier['fermes']->count() - 1)];
            }
        }

        return null;
    }

    private function mauvaiseReponse(string $juste): string
    {
        $bas = mb_strtolower(trim($juste));
        foreach (self::JEUX_FERMES as $jeu) {
            if (in_array($bas, $jeu, true)) {
                $autres = array_values(array_diff($jeu, [$bas]));
                $autre = $autres[mt_rand(0, count($autres) - 1)];

                return $juste === mb_strtoupper($juste) ? mb_strtoupper($autre) : ($juste === ucfirst($bas) ? ucfirst($autre) : $autre);
            }
        }

        // Une faute d'orthographe ou de terminaison : « gelernt » → « gelern ».
        return mb_strlen($juste) > 3 ? mb_substr($juste, 0, -1) : $juste.'e';
    }

    private function enregistrer(User $eleve, Exercise $exercice, array $reponses, array $retours, int|float $score, float $precision, int $temps, Carbon $date): void
    {
        $tentative = new UserExerciseAttempt([
            'user_id' => $eleve->id,
            'exercise_id' => $exercice->id,
            'answers' => $reponses,
            'score' => $score,
            'accuracy_percent' => $precision,
            'time_spent' => $temps,
            'xp_earned' => (int) ($exercice->xp_reward ?? 10),
            'feedback' => $retours,
        ]);
        $tentative->timestamps = false;
        $tentative->created_at = $date;
        $tentative->updated_at = $date->copy()->addSeconds($temps);
        $tentative->save();

        $this->compte['tentatives']++;
    }

    /** Chaque faute d'une production devient une erreur retenue, comme dans l'application. */
    private function erreursDeProduction(User $eleve, array $corrections, string $texte, bool $oral, Carbon $date, bool $depuisMaitrise): void
    {
        foreach (CorrectionsDeProduction::nettoyer($corrections) as $c) {
            if ($c['original'] === $c['corrected']) {
                continue;
            }
            [$categorie, $sous] = array_pad(explode('.', mb_strtolower((string) $c['category']), 2), 2, null);
            // La meme cle que ErreursDeRedaction : la meme faute ne fait qu'une erreur.
            $cle = ($oral ? 'oral-' : 'redaction-').$categorie.'-'.substr(md5(mb_strtolower($c['original'])), 0, 10);

            $position = mb_stripos($texte, $c['original']);
            $extrait = $position === false
                ? $c['original']
                : '…'.trim(mb_substr($texte, max(0, $position - 40), mb_strlen($c['original']) + 80)).'…';

            $this->erreur($eleve, null, $cle, [
                'question_text' => ($oral ? 'Dans ta réponse orale' : 'Dans ta rédaction').' : « '.$extrait.' »',
                'user_answer' => $c['original'],
                'correct_answer' => $c['corrected'],
                'explanation' => $c['explanation'] ?: null,
                'skill_type' => $oral ? 'speaking' : 'writing',
                'exercise_type_slug' => $oral ? 'speaking-evaluation' : 'writing-corrector',
                'error_category' => $categorie,
                'subcategory' => $sous,
            ], $date, $depuisMaitrise);
        }
    }

    /** Une erreur retenue, seulement si l'eleve ne l'a pas deja : on ne reecrit jamais une erreur. */
    private function erreur(User $eleve, ?int $exerciceId, string $questionId, array $champs, Carbon $date, bool $depuisMaitrise): void
    {
        $existe = UserError::where('user_id', $eleve->id)
            ->where('question_id', $questionId)
            ->when($exerciceId, fn ($q) => $q->where('exercise_id', $exerciceId), fn ($q) => $q->whereNull('exercise_id'))
            ->exists();
        if ($existe) {
            return;
        }

        // Un eleve qui progresse a deja corrige une partie de ses anciennes fautes.
        $maitrisee = $depuisMaitrise && mt_rand(0, 1) === 1;

        $erreur = new UserError(array_merge($champs, [
            'user_id' => $eleve->id,
            'exercise_id' => $exerciceId,
            'question_id' => $questionId,
            'question_text' => (string) ($champs['question_text'] ?? ''),
            'mastered' => $maitrisee,
            'reviewed_count' => $maitrisee ? 3 : 0,
            'last_reviewed_at' => $maitrisee ? $date->copy()->addDays(6) : null,
            'ease_factor' => 2.5,
            'interval_days' => $maitrisee ? 6 : 1,
            'next_review_at' => $date->copy()->addDay(),
        ]));
        $erreur->timestamps = false;
        $erreur->created_at = $date;
        $erreur->updated_at = $maitrisee ? $date->copy()->addDays(6) : $date;
        $erreur->save();

        $this->compte['erreurs']++;
    }

    /**
     * Un eleve deja la (les DELF de la demo) : ses tentatives restent telles
     * quelles. Une production deja ecrite et jamais corrigee est corrigee par
     * l'IA ; et quelques productions corrigees sont ajoutees DANS la periode ou
     * il travaillait deja, pour que sa derniere activite ne change pas — un
     * decrocheur reste un decrocheur.
     */
    private function enrichir(LanguageCenter $centre, User $eleve, WritingCorrectorService $correcteur): void
    {
        $tentatives = UserExerciseAttempt::where('user_id', $eleve->id)
            ->with('exercise.exerciseType', 'exercise.exam')
            ->orderBy('created_at')
            ->get();
        if ($tentatives->isEmpty()) {
            // Jamais venu : rien a analyser, et c'est ce que le centre doit voir.
            return;
        }

        $classe = Classroom::where('center_id', $centre->id)
            ->whereHas('students', fn ($q) => $q->where('users.id', $eleve->id))
            ->orderBy('id')
            ->first();
        $examen = ($classe?->exam_id ? Exam::find($classe->exam_id) : null)
            ?? ($centre->default_exam_id ? Exam::find($centre->default_exam_id) : null)
            ?? $tentatives->last()->exercise?->exam;
        $niveau = $classe?->level ?: (UserProfile::where('user_id', $eleve->id)->value('current_level') ?: 'B1');
        $langue = $this->langue($examen);
        $dejaFait = $this->dejaComplete($eleve);
        $avant = $this->compte['tentatives'] + $this->compte['erreurs'] + $this->compte['productions_corrigees_ia'];

        // 1. Les productions deja ecrites, jamais corrigees.
        foreach ($tentatives as $t) {
            $competence = $t->exercise?->exerciseType?->skill_type;
            if (! in_array($competence, ['writing', 'speaking'], true) || ! empty($t->feedback)) {
                continue;
            }
            $texte = collect((array) $t->answers)->first(fn ($r) => is_string($r) && mb_strlen(trim($r)) >= 40 && str_word_count($r) >= 8);
            if (! $texte) {
                continue;
            }
            if (! $this->appliquer || ! $this->avecIa) {
                $this->compte['productions_a_corriger_ia']++;

                continue;
            }

            // Corrigee APRES la transaction (voir corrigerParIa).
            $q = collect((array) $t->exercise->questions)->first() ?? [];
            $this->aCorriger[] = [
                'tentative' => $t->id,
                'eleve' => $eleve->id,
                'texte' => $texte,
                'consigne' => $q['prompt'] ?? $q['text'] ?? 'Write a text',
                // L'examen de l'exercice, comme ExerciseScoringService ; la langue
                // passee ensuite est celle du RETOUR, rendu en francais au centre.
                'examen' => $t->exercise?->exam?->name ?? $examen?->name ?? 'DELF',
                'niveau' => $t->exercise?->difficulty ?: $niveau,
                'question_id' => (string) ($q['id'] ?? array_key_first((array) $t->answers) ?? 'q1'),
                'oral' => $competence === 'speaking',
                'date' => $t->created_at,
            ];
        }

        $this->classeEnCours = $classe?->id;

        // 2. Des productions corrigees, dans la periode deja travaillee.
        if (! $examen || $dejaFait) {
            if ($this->compte['tentatives'] + $this->compte['erreurs'] + $this->compte['productions_corrigees_ia'] > $avant) {
                $this->compte['eleves_delf_enrichis']++;
            }

            return;
        }

        $vivier = $this->vivier($examen, $niveau);
        mt_srand(crc32('enrichir|'.$eleve->email));

        $debut = $tentatives->first()->created_at;
        $fin = $tentatives->last()->created_at;
        $nombre = $tentatives->count() >= 8 ? 2 : 1;
        $moyenne = (float) ($tentatives->avg('accuracy_percent') ?? 60);
        $places = [0.9, 0.55];

        for ($i = 0; $i < $nombre; $i++) {
            $sorte = $i === 1 ? 'oraux' : 'ecrits';
            $exercices = $vivier[$sorte]->isNotEmpty() ? $vivier[$sorte] : $vivier[$sorte === 'oraux' ? 'ecrits' : 'oraux'];
            if ($exercices->isEmpty()) {
                break;
            }
            $exercice = $exercices[mt_rand(0, $exercices->count() - 1)];

            // Jamais apres sa derniere tentative : sa derniere activite reste la meme.
            $secondes = max(0, $debut->diffInSeconds($fin));
            $date = $debut->copy()->addSeconds((int) floor($secondes * $places[$i]))->subMinutes(mt_rand(0, 30));
            if ($date->lt($debut)) {
                $date = $debut->copy();
            }

            $this->tentative($eleve, $exercice, max(20, min(98, $moyenne + mt_rand(-8, 8))), $date, $langue, $niveau, false);
        }

        if ($this->compte['tentatives'] + $this->compte['erreurs'] + $this->compte['productions_corrigees_ia'] > $avant) {
            $this->compte['eleves_delf_enrichis']++;
        }
    }

    /**
     * Les productions deja ecrites et jamais corrigees, envoyees a l'IA une par
     * une, HORS de la transaction : l'appel HTTP ne tient aucun verrou, et
     * chaque correction s'ecrit seule, dans une transaction courte. Le retour
     * n'est pose que si le champ est toujours vide.
     */
    private function corrigerParIa(WritingCorrectorService $correcteur): void
    {
        foreach ($this->aCorriger as $p) {
            $resultat = $correcteur->correct($p['texte'], $p['consigne'], $p['examen'], 'Français', $p['niveau']);
            if (! empty($resultat['evaluation_indisponible']) || ! is_numeric($resultat['score'] ?? null)) {
                $this->compte['productions_a_corriger_ia']++;

                continue;
            }

            $points = (float) $resultat['score'] / 9;
            $corrections = CorrectionsDeProduction::nettoyer($resultat['corrections'] ?? []);
            $retour = [[
                'question_id' => $p['question_id'],
                'correct' => $points >= 0.6,
                'accuracy' => round($points * 100, 1),
                'band_score' => (float) $resultat['score'],
                'sub_scores' => $resultat['band_scores'] ?? [],
                'corrections' => $corrections,
                'diagnostic' => DiagnosticDeProduction::nettoyer($resultat['diagnostic'] ?? []),
                'explanation' => is_string($resultat['feedback'] ?? null) ? $resultat['feedback'] : 'Analyse effectuée.',
                'transcription' => $p['oral'] ? $p['texte'] : null,
                'technical_failure' => false,
                'origine' => self::ORIGINE,
            ]];

            DB::transaction(function () use ($p, $retour, $corrections) {
                // Seul le champ vide est rempli : ni la date ni le score ne bougent.
                $rempli = DB::table('user_exercise_attempts')->where('id', $p['tentative'])
                    ->where(fn ($w) => $w->whereNull('feedback')->orWhere('feedback', '[]')->orWhere('feedback', ''))
                    ->update(['feedback' => json_encode($retour, JSON_UNESCAPED_UNICODE)]);
                $eleve = User::find($p['eleve']);
                if ($rempli === 0 || ! $eleve) {
                    return;
                }
                $this->compte['productions_corrigees_ia']++;
                $this->erreursDeProduction($eleve, $corrections, $p['texte'], $p['oral'], $p['date'], false);
            });
        }
        $this->aCorriger = [];
    }
}
