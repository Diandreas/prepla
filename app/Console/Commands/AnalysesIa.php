<?php

namespace App\Console\Commands;

use App\Models\LanguageCenter;
use App\Services\Center\AnalyseIa;
use Illuminate\Console\Command;

/**
 * Pre-genere les analyses IA d'un centre : chaque eleve, chaque classe active,
 * et le centre entier. Utile avant une demonstration, pour que chaque fiche
 * s'ouvre sans attendre l'IA.
 *
 * Sans --appliquer, rien n'est ecrit ni envoye a l'IA : la commande compte
 * seulement les analyses deja a jour, celles qui demanderaient un appel, et
 * les eleves qui n'ont pas assez d'exercices pour etre analyses.
 *
 * Avec --appliquer, une analyse IA deja a jour (memes donnees, moins de 24 h)
 * est reutilisee : relancer la commande ne repaie pas l'IA. Une analyse faite
 * par regles (IA en panne) n'est jamais comptee « a jour » : la commande la
 * retente. --forcer refait tout, meme les analyses IA a jour.
 *
 * L'empreinte porte sur les donnees, pas sur l'heure : une pre-generation de
 * la veille reste valable le lendemain tant que personne n'a travaille et
 * qu'elle a moins de 24 h. Si l'IA echoue, une analyse IA recente est gardee.
 */
class AnalysesIa extends Command
{
    protected $signature = 'prepla:analyses-ia
        {--centre= : identifiant du centre}
        {--appliquer : genere vraiment ; sans cette option, simple rapport}
        {--forcer : refait aussi les analyses IA deja a jour}';

    protected $description = 'Pre-genere les analyses IA des eleves, des classes et du centre (rapport seulement sans --appliquer)';

    /** Ordre de grandeur pour mistral-small : ~2 500 jetons envoyes, ~500 recus par analyse. */
    private const JETONS_ENTREE = 2500;

    private const JETONS_SORTIE = 500;

    public function handle(AnalyseIa $analyse): int
    {
        $centre = LanguageCenter::find((int) $this->option('centre'));
        if (! $centre) {
            $this->error('Centre introuvable : précisez --centre=ID.');

            return self::FAILURE;
        }

        $appliquer = (bool) $this->option('appliquer');
        $forcer = (bool) $this->option('forcer');
        $this->info(($appliquer ? 'Génération' : 'Rapport (rien n’est écrit)')." — {$centre->name} (#{$centre->id})");

        $sujets = [];
        foreach ($centre->students()->orderBy('users.id')->get() as $eleve) {
            $faits = $analyse->faitsEleve($centre, $eleve);
            $sujets[] = [
                'type' => 'eleve', 'nom' => $eleve->name, 'id' => $eleve->id, 'objet' => $eleve,
                'faits' => $faits, 'assez' => $faits['activite']['exercices_total'] >= AnalyseIa::MIN_TENTATIVES,
            ];
        }
        foreach ($centre->classrooms()->whereNull('archived_at')->orderBy('id')->get() as $classe) {
            $faits = $analyse->faitsClasse($centre, $classe)['faits'];
            $sujets[] = [
                'type' => 'classe', 'nom' => $classe->name, 'id' => $classe->id, 'objet' => $classe,
                'faits' => $faits, 'assez' => $faits['activite']['exercices_total'] >= AnalyseIa::MIN_TENTATIVES,
            ];
        }
        $faits = $analyse->faitsCentre($centre)['faits'];
        $sujets[] = [
            'type' => 'centre', 'nom' => $centre->name, 'id' => $centre->id, 'objet' => $centre,
            'faits' => $faits, 'assez' => $faits['activite']['exercices_28_jours'] >= AnalyseIa::MIN_TENTATIVES,
        ];

        $lignes = [];
        $compte = ['a_jour' => 0, 'appels' => 0, 'insuffisant' => 0];
        $sources = ['ia' => 0, 'regles' => 0, 'conservees' => 0];

        foreach ($sujets as $s) {
            if (! $s['assez']) {
                $etat = 'pas assez de données';
                $compte['insuffisant']++;
            } elseif (! $forcer && $analyse->analyseAJour($centre, $s['type'], $s['objet'])?->source === 'ia') {
                $etat = 'déjà à jour';
                $compte['a_jour']++;
            } else {
                $etat = 'appel IA';
                $compte['appels']++;
            }

            if ($appliquer && $etat === 'appel IA') {
                // « forcer » : la commande sait deja qu'il faut un appel ; pas de
                // plafond par centre, son cout est annonce et lance a la main.
                $resultat = match ($s['type']) {
                    'eleve' => $analyse->eleve($centre, $s['objet'], 'forcer', false),
                    'classe' => $analyse->classe($centre, $s['objet'], 'forcer', false),
                    'centre' => $analyse->centre($centre, 'forcer', false),
                };
                $source = $resultat['source'] ?? 'regles';
                $conservee = $source === 'ia' && ! ($resultat['a_jour'] ?? false);
                $source = $conservee ? 'conservees' : $source;
                $sources[$source] = ($sources[$source] ?? 0) + 1;
                $etat = match ($source) {
                    'ia' => 'généré par l’IA',
                    'conservees' => 'IA indisponible : analyse IA précédente conservée',
                    default => 'généré par règles (IA indisponible)',
                };
            }

            $lignes[] = [$s['type'], $s['id'], $s['nom'], $etat];
        }

        $this->table(['Sujet', 'ID', 'Nom', 'État'], $lignes);

        $this->line("Déjà à jour : {$compte['a_jour']} · pas assez de données : {$compte['insuffisant']} · appels IA ".($appliquer ? 'faits' : 'nécessaires').' : '.$compte['appels']);
        $this->line(sprintf(
            'Estimation : ~%s jetons envoyés et ~%s reçus (mistral-small : environ %s $ au total).',
            number_format($compte['appels'] * self::JETONS_ENTREE, 0, ',', ' '),
            number_format($compte['appels'] * self::JETONS_SORTIE, 0, ',', ' '),
            number_format($compte['appels'] * (self::JETONS_ENTREE * 0.1 + self::JETONS_SORTIE * 0.3) / 1_000_000, 4, ',', ' '),
        ));

        if ($appliquer) {
            $this->info("Générées : {$sources['ia']} par l’IA, {$sources['regles']} par règles ; {$sources['conservees']} analyse(s) IA précédente(s) conservée(s).");
        } else {
            $this->comment('Relancez avec --appliquer pour générer.');
        }

        return self::SUCCESS;
    }
}
