<?php

namespace App\Console\Commands;

use App\Models\CenterUser;
use App\Models\Exercise;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserError;
use App\Models\UserExerciseAttempt;
use App\Support\Demo\RemplisseurDeDemo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Repare EN PLACE le centre de demonstration « Institut Linguae ».
 *
 * La demo avait ete generee avec des exercices factices — un QCM a une question,
 * toute reponse valant « A », aucune correction, meme pour une redaction. Un
 * prospect qui ouvrait une copie dans le suivi du centre voyait une redaction
 * reduite a « A ».
 *
 * Le generateur de la demo, lui, commence par SUPPRIMER le centre existant : on
 * ne le relance pas. Cette commande ne supprime RIEN. Elle remplace le contenu
 * des exercices du centre de demo, les reponses et la correction de leurs
 * tentatives, et reecrit les erreurs factices — et seulement pour les comptes
 * dont l'adresse finit par @demo.prepla.local.
 */
class ReparerLaDemo extends Command
{
    protected $signature = 'prepla:demo-reparer {--sec : Dire ce qui serait change, sans rien ecrire}';

    protected $description = 'Donne au centre de demonstration de vrais exercices et des copies coherentes, sans rien supprimer';

    private const SLUG = 'institut-linguae-demo';

    private const DOMAINE = '@demo.prepla.local';

    /** L'explication passe-partout des erreurs fabriquees par l'ancienne demo. */
    private const EXPLICATION_FACTICE = 'Révisez la règle correspondante et réessayez.';

    public function handle(RemplisseurDeDemo $remplisseur): int
    {
        $centre = LanguageCenter::where('slug', self::SLUG)->first();
        if (! $centre) {
            $this->info('Aucun centre de démonstration : rien à réparer.');

            return self::SUCCESS;
        }

        // Les seuls comptes touches : membres du centre de demo ET adresse de demo.
        $comptes = User::whereIn('id', CenterUser::where('center_id', $centre->id)->pluck('user_id'))
            ->where('email', 'like', '%'.self::DOMAINE)
            ->pluck('id');

        $exercices = Exercise::where('center_id', $centre->id)->with('exerciseType')->get();
        $tentatives = UserExerciseAttempt::whereIn('exercise_id', $exercices->pluck('id'))
            ->whereIn('user_id', $comptes)
            ->orderBy('id')
            ->get();
        $factices = UserError::whereIn('user_id', $comptes)
            ->where('explanation', self::EXPLICATION_FACTICE)
            ->whereIn('exercise_id', $exercices->pluck('id'))
            ->get();

        $this->line(sprintf('Centre « %s » (#%d) : %d comptes de démo, %d exercices, %d tentatives, %d erreurs factices.',
            $centre->name, $centre->id, $comptes->count(), $exercices->count(), $tentatives->count(), $factices->count()));

        if ($this->option('sec')) {
            $this->warn('À sec : rien n’a été écrit.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($remplisseur, $exercices, $tentatives, $factices) {
            foreach ($exercices as $exercice) {
                $remplisseur->exercice($exercice);
            }

            $parExercice = $exercices->keyBy('id');
            foreach ($tentatives as $tentative) {
                $remplisseur->tentative($tentative, $parExercice[$tentative->exercise_id]->fresh('exerciseType'));
            }

            // Une erreur factice deja reecrite par une tentative n'a plus son
            // explication passe-partout : seules les autres restent a reprendre.
            foreach ($factices as $erreur) {
                $erreur->refresh();
                if ($erreur->explanation === self::EXPLICATION_FACTICE) {
                    $remplisseur->erreurFactice($erreur, $parExercice[$erreur->exercise_id]->fresh('exerciseType'));
                }
            }
        });

        $this->info('Démo réparée. Rien n’a été supprimé.');

        return self::SUCCESS;
    }
}
