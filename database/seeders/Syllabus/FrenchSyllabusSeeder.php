<?php

namespace Database\Seeders\Syllabus;

use App\Models\Exam;
use App\Models\LearningPathNode;
use Illuminate\Database\Seeder;

class FrenchSyllabusSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedDelfDalf();
        $this->seedTcf();
        $this->seedTef();
    }

    // ─────────────────────────────────────────────────────────────
    //  DELF / DALF  –  60 nodes, 10 chapters
    // ─────────────────────────────────────────────────────────────
    private function seedDelfDalf(): void
    {
        $exam = Exam::where('slug', 'delf-dalf')->first();
        if (!$exam) {
            $this->command?->warn('Exam delf-dalf not found – skipping.');
            return;
        }

        LearningPathNode::where('exam_id', $exam->id)->delete();

        $chapters = [
            // ── Chapitre 1 ──────────────────────────────────────
            [
                'name'  => 'Compréhension orale - Bases',
                'nodes' => [
                    ['title' => 'Comprendre des mots familiers et expressions courantes',     'desc' => 'Reconnaître et comprendre les mots et expressions les plus fréquents de la vie quotidienne.',          'icon' => 'headphones', 'skill' => 'listening',  'level' => 'A1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Identifier le thème d\'un message court',                    'desc' => 'Repérer le sujet principal dans des messages simples et courts.',                                       'icon' => 'headphones', 'skill' => 'listening',  'level' => 'A1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Comprendre des annonces et instructions simples',             'desc' => 'Saisir le sens d\'annonces publiques et d\'instructions orales courtes.',                               'icon' => 'headphones', 'skill' => 'listening',  'level' => 'A2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Suivre une conversation de la vie quotidienne',               'desc' => 'Comprendre l\'essentiel d\'échanges courants entre locuteurs natifs.',                                  'icon' => 'headphones', 'skill' => 'listening',  'level' => 'A2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Comprendre une émission de radio simple',                     'desc' => 'Extraire les informations clés d\'une émission radio à débit modéré.',                                  'icon' => 'headphones', 'skill' => 'listening',  'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Checkpoint compréhension orale',                              'desc' => 'Évaluez vos acquis en compréhension orale niveaux A1 à B1.',                                           'icon' => 'trophy',     'skill' => 'listening',  'level' => 'B1', 'type' => 'practice', 'xp' => 75],
                ],
            ],
            // ── Chapitre 2 ──────────────────────────────────────
            [
                'name'  => 'Compréhension écrite - Bases',
                'nodes' => [
                    ['title' => 'Lire des panneaux, menus et formulaires',                     'desc' => 'Décoder des documents visuels courts de la vie quotidienne.',                                          'icon' => 'book',       'skill' => 'reading',    'level' => 'A1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Comprendre des messages courts et emails',                    'desc' => 'Lire et comprendre des courriels, cartes postales et SMS.',                                             'icon' => 'book',       'skill' => 'reading',    'level' => 'A2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Lire des articles de presse simples',                         'desc' => 'Comprendre le sens général d\'articles de journaux accessibles.',                                       'icon' => 'book',       'skill' => 'reading',    'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Identifier les opinions dans un texte',                       'desc' => 'Distinguer faits et opinions dans un texte argumentatif simple.',                                       'icon' => 'book',       'skill' => 'reading',    'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Analyser des textes argumentatifs',                           'desc' => 'Repérer la thèse, les arguments et la structure d\'un texte argumentatif.',                              'icon' => 'book',       'skill' => 'reading',    'level' => 'B2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Checkpoint compréhension écrite',                             'desc' => 'Évaluez vos acquis en compréhension écrite niveaux A1 à B2.',                                           'icon' => 'trophy',     'skill' => 'reading',    'level' => 'B2', 'type' => 'practice', 'xp' => 75],
                ],
            ],
            // ── Chapitre 3 ──────────────────────────────────────
            [
                'name'  => 'Production écrite - Fondamentaux',
                'nodes' => [
                    ['title' => 'Remplir un formulaire et écrire une carte postale',           'desc' => 'Compléter des documents simples et rédiger un texte bref.',                                             'icon' => 'pen',        'skill' => 'writing',    'level' => 'A1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Écrire un message court (invitation, remerciement)',          'desc' => 'Rédiger des messages personnels courts avec un objectif précis.',                                        'icon' => 'pen',        'skill' => 'writing',    'level' => 'A2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Raconter un événement ou une expérience',                     'desc' => 'Produire un récit simple au passé avec un enchaînement logique.',                                        'icon' => 'pen',        'skill' => 'writing',    'level' => 'A2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Exprimer son opinion dans un courrier',                       'desc' => 'Écrire une lettre exprimant clairement son point de vue.',                                               'icon' => 'pen',        'skill' => 'writing',    'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Rédiger une lettre formelle',                                 'desc' => 'Maîtriser les codes de la correspondance formelle française.',                                           'icon' => 'pen',        'skill' => 'writing',    'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Checkpoint production écrite',                                'desc' => 'Évaluez vos acquis en production écrite niveaux A1 à B1.',                                              'icon' => 'trophy',     'skill' => 'writing',    'level' => 'B1', 'type' => 'practice', 'xp' => 75],
                ],
            ],
            // ── Chapitre 4 ──────────────────────────────────────
            [
                'name'  => 'Production orale - Fondamentaux',
                'nodes' => [
                    ['title' => 'Se présenter et parler de soi',                               'desc' => 'Savoir se présenter, parler de sa famille, ses goûts et activités.',                                    'icon' => 'mic',        'skill' => 'speaking',   'level' => 'A1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Poser des questions et obtenir des informations',             'desc' => 'Formuler des questions simples pour obtenir des renseignements.',                                        'icon' => 'mic',        'skill' => 'speaking',   'level' => 'A1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Jeu de rôle: situations quotidiennes',                        'desc' => 'Interagir dans des situations courantes (achats, réservations, etc.).',                                  'icon' => 'mic',        'skill' => 'speaking',   'level' => 'A2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Présenter un thème à partir d\'un document',                  'desc' => 'Dégager le sujet d\'un document déclencheur et en parler.',                                              'icon' => 'mic',        'skill' => 'speaking',   'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Exprimer son point de vue et argumenter',                     'desc' => 'Développer un avis personnel avec des arguments organisés.',                                             'icon' => 'mic',        'skill' => 'speaking',   'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Checkpoint production orale',                                 'desc' => 'Évaluez vos acquis en production orale niveaux A1 à B1.',                                               'icon' => 'trophy',     'skill' => 'speaking',   'level' => 'B1', 'type' => 'practice', 'xp' => 75],
                ],
            ],
            // ── Chapitre 5 ──────────────────────────────────────
            [
                'name'  => 'Compréhension avancée',
                'nodes' => [
                    ['title' => 'Comprendre des interviews et débats',                         'desc' => 'Saisir les arguments échangés dans des interviews et débats.',                                          'icon' => 'headphones', 'skill' => 'listening',  'level' => 'B2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Suivre des conférences et exposés',                           'desc' => 'Comprendre des présentations longues sur des sujets complexes.',                                         'icon' => 'headphones', 'skill' => 'listening',  'level' => 'B2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Analyser des textes littéraires et journalistiques',          'desc' => 'Lire et interpréter des textes exigeants de la presse et de la littérature.',                             'icon' => 'book',       'skill' => 'reading',    'level' => 'B2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Comprendre l\'implicite et les nuances',                      'desc' => 'Détecter l\'ironie, les sous-entendus et les nuances de sens.',                                          'icon' => 'brain',      'skill' => 'reading',    'level' => 'C1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Comprendre des documents longs et complexes',                 'desc' => 'Suivre un discours long et dense même dans un domaine peu familier.',                                    'icon' => 'headphones', 'skill' => 'listening',  'level' => 'C1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Checkpoint compréhension avancée',                            'desc' => 'Évaluez vos acquis en compréhension avancée niveaux B2 à C1.',                                          'icon' => 'trophy',     'skill' => 'listening',  'level' => 'C1', 'type' => 'practice', 'xp' => 75],
                ],
            ],
            // ── Chapitre 6 ──────────────────────────────────────
            [
                'name'  => 'Production écrite avancée',
                'nodes' => [
                    ['title' => 'Essai argumenté: structure et connecteurs',                   'desc' => 'Construire un essai avec introduction, développement et conclusion.',                                    'icon' => 'pen',        'skill' => 'writing',    'level' => 'B2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Lettre formelle de réclamation ou proposition',               'desc' => 'Rédiger une lettre officielle pour formuler une plainte ou suggestion.',                                  'icon' => 'pen',        'skill' => 'writing',    'level' => 'B2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Article critique pour un journal',                            'desc' => 'Écrire un article d\'opinion clair et structuré pour une publication.',                                   'icon' => 'pen',        'skill' => 'writing',    'level' => 'B2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Synthèse de documents (DALF C1)',                             'desc' => 'Synthétiser plusieurs documents en un texte cohérent et neutre.',                                        'icon' => 'pen',        'skill' => 'writing',    'level' => 'C1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Essai argumenté niveau C1',                                   'desc' => 'Rédiger un essai complexe avec nuances et références.',                                                 'icon' => 'pen',        'skill' => 'writing',    'level' => 'C1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Checkpoint production écrite avancée',                        'desc' => 'Évaluez vos acquis en production écrite avancée niveaux B2 à C1.',                                       'icon' => 'trophy',     'skill' => 'writing',    'level' => 'C1', 'type' => 'practice', 'xp' => 75],
                ],
            ],
            // ── Chapitre 7 ──────────────────────────────────────
            [
                'name'  => 'Production orale avancée',
                'nodes' => [
                    ['title' => 'Présenter et défendre un point de vue',                       'desc' => 'Exposer et soutenir une thèse face à un interlocuteur.',                                                'icon' => 'mic',        'skill' => 'speaking',   'level' => 'B2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Débattre avec un examinateur',                                'desc' => 'Participer à un échange argumenté avec un examinateur.',                                                 'icon' => 'mic',        'skill' => 'speaking',   'level' => 'B2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Exposé structuré à partir de documents',                      'desc' => 'Présenter un exposé organisé en s\'appuyant sur des documents fournis.',                                  'icon' => 'mic',        'skill' => 'speaking',   'level' => 'C1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Argumentation et contre-argumentation',                       'desc' => 'Anticiper les objections et défendre sa position avec subtilité.',                                        'icon' => 'mic',        'skill' => 'speaking',   'level' => 'C1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Monologue suivi et interaction C2',                           'desc' => 'Produire un discours fluide, nuancé et spontané au niveau C2.',                                          'icon' => 'mic',        'skill' => 'speaking',   'level' => 'C2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Checkpoint production orale avancée',                         'desc' => 'Évaluez vos acquis en production orale avancée niveaux B2 à C1.',                                        'icon' => 'trophy',     'skill' => 'speaking',   'level' => 'C1', 'type' => 'practice', 'xp' => 75],
                ],
            ],
            // ── Chapitre 8 ──────────────────────────────────────
            [
                'name'  => 'Vocabulaire et Grammaire',
                'nodes' => [
                    ['title' => 'Vocabulaire de la vie quotidienne A1-A2',                     'desc' => 'Maîtriser le vocabulaire essentiel de la vie de tous les jours.',                                        'icon' => 'globe',      'skill' => 'reading',    'level' => 'A2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Conjugaisons essentielles et temps',                          'desc' => 'Réviser les conjugaisons clés: présent, passé composé, imparfait, futur.',                               'icon' => 'brain',      'skill' => 'grammar',    'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Connecteurs logiques et articulation du discours',            'desc' => 'Utiliser les connecteurs pour structurer un texte de manière cohérente.',                                  'icon' => 'pen',        'skill' => 'writing',    'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Vocabulaire académique et soutenu',                           'desc' => 'Enrichir son lexique avec du vocabulaire de registre soutenu.',                                           'icon' => 'book',       'skill' => 'reading',    'level' => 'B2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Subjonctif, conditionnel et nuances',                         'desc' => 'Maîtriser les modes verbaux avancés et leurs emplois nuancés.',                                          'icon' => 'brain',      'skill' => 'grammar',    'level' => 'C1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Checkpoint vocabulaire et grammaire',                         'desc' => 'Évaluez vos acquis en vocabulaire et grammaire tous niveaux.',                                            'icon' => 'trophy',     'skill' => 'grammar',    'level' => 'C1', 'type' => 'practice', 'xp' => 75],
                ],
            ],
            // ── Chapitre 9 ──────────────────────────────────────
            [
                'name'  => 'Stratégie d\'examen',
                'nodes' => [
                    ['title' => 'Gestion du temps par épreuve',                                'desc' => 'Apprendre à répartir son temps efficacement entre les épreuves.',                                        'icon' => 'target',     'skill' => 'mixed',      'level' => 'B2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Erreurs fréquentes à éviter',                                 'desc' => 'Identifier et corriger les pièges les plus courants du DELF/DALF.',                                      'icon' => 'zap',        'skill' => 'mixed',      'level' => 'B2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Techniques pour maximiser son score',                         'desc' => 'Stratégies éprouvées pour gagner des points supplémentaires.',                                            'icon' => 'star',       'skill' => 'mixed',      'level' => 'C1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Préparation mentale jour J',                                  'desc' => 'Gérer le stress et se préparer mentalement pour le jour de l\'examen.',                                   'icon' => 'brain',      'skill' => 'mixed',      'level' => 'C1', 'type' => 'lesson',   'xp' => 50],
                ],
            ],
            // ── Chapitre 10 ─────────────────────────────────────
            [
                'name'  => 'Examens blancs',
                'nodes' => [
                    ['title' => 'Examen blanc: Compréhension orale B1-B2',                     'desc' => 'Simulation complète de l\'épreuve de compréhension orale.',                                              'icon' => 'headphones', 'skill' => 'listening',  'level' => 'B2', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'Examen blanc: Compréhension écrite B1-B2',                    'desc' => 'Simulation complète de l\'épreuve de compréhension écrite.',                                              'icon' => 'book',       'skill' => 'reading',    'level' => 'B2', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'Examen blanc: Production écrite B1-B2',                       'desc' => 'Simulation complète de l\'épreuve de production écrite.',                                                 'icon' => 'pen',        'skill' => 'writing',    'level' => 'B2', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'Examen blanc: Production orale B1-B2',                        'desc' => 'Simulation complète de l\'épreuve de production orale.',                                                  'icon' => 'mic',        'skill' => 'speaking',   'level' => 'B2', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'Examen blanc: DALF C1 Synthèse',                              'desc' => 'Simulation de l\'épreuve de synthèse du DALF C1.',                                                       'icon' => 'pen',        'skill' => 'writing',    'level' => 'C1', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'Examen blanc: DALF C1 Oral',                                  'desc' => 'Simulation de l\'épreuve orale du DALF C1.',                                                             'icon' => 'mic',        'skill' => 'speaking',   'level' => 'C1', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'Examen blanc: Simulation complète DELF B2',                   'desc' => 'Simulation intégrale des quatre épreuves du DELF B2.',                                                    'icon' => 'star',       'skill' => 'mixed',      'level' => 'B2', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'DELF/DALF Examen Final Complet',                              'desc' => 'Épreuve finale complète couvrant toutes les compétences DELF/DALF.',                                      'icon' => 'trophy',     'skill' => 'mixed',      'level' => 'C1', 'type' => 'boss',     'xp' => 200],
                ],
            ],
        ];

        $this->createNodes($exam, $chapters);
    }

    // ─────────────────────────────────────────────────────────────
    //  TCF  –  50 nodes, 9 chapters
    // ─────────────────────────────────────────────────────────────
    private function seedTcf(): void
    {
        $exam = Exam::where('slug', 'tcf')->first();
        if (!$exam) {
            $this->command?->warn('Exam tcf not found – skipping.');
            return;
        }

        LearningPathNode::where('exam_id', $exam->id)->delete();

        $chapters = [
            // ── Chapitre 1 ──────────────────────────────────────
            [
                'name'  => 'Compréhension orale',
                'nodes' => [
                    ['title' => 'Comprendre des messages courts A1-A2',                        'desc' => 'Comprendre des messages oraux simples de la vie courante.',                                              'icon' => 'headphones', 'skill' => 'listening',  'level' => 'A2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Identifier des informations dans un dialogue',                'desc' => 'Repérer les informations essentielles dans un échange entre locuteurs.',                                  'icon' => 'headphones', 'skill' => 'listening',  'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Comprendre des émissions de radio',                           'desc' => 'Saisir le contenu principal d\'émissions radiophoniques.',                                                'icon' => 'headphones', 'skill' => 'listening',  'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Suivre des discussions et débats',                            'desc' => 'Comprendre les arguments dans des discussions animées.',                                                  'icon' => 'headphones', 'skill' => 'listening',  'level' => 'B2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Comprendre des conférences et cours',                         'desc' => 'Suivre des exposés longs et complexes dans un cadre académique.',                                         'icon' => 'headphones', 'skill' => 'listening',  'level' => 'C1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Checkpoint compréhension orale',                              'desc' => 'Évaluez vos acquis en compréhension orale pour le TCF.',                                                 'icon' => 'trophy',     'skill' => 'listening',  'level' => 'B2', 'type' => 'practice', 'xp' => 75],
                ],
            ],
            // ── Chapitre 2 ──────────────────────────────────────
            [
                'name'  => 'Maîtrise des structures',
                'nodes' => [
                    ['title' => 'Grammaire de base: articles, prépositions',                   'desc' => 'Maîtriser l\'emploi des articles et prépositions fondamentaux.',                                         'icon' => 'brain',      'skill' => 'grammar',    'level' => 'A1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Conjugaisons: présent, passé composé, imparfait',             'desc' => 'Réviser les trois temps essentiels de la conjugaison française.',                                         'icon' => 'brain',      'skill' => 'grammar',    'level' => 'A2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Pronoms relatifs et compléments',                             'desc' => 'Utiliser correctement les pronoms relatifs et compléments.',                                              'icon' => 'brain',      'skill' => 'grammar',    'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Subjonctif et conditionnel',                                  'desc' => 'Comprendre et employer le subjonctif et le conditionnel.',                                                'icon' => 'brain',      'skill' => 'grammar',    'level' => 'B2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Structures complexes et registre soutenu',                    'desc' => 'Maîtriser les tournures avancées du français soutenu.',                                                   'icon' => 'brain',      'skill' => 'grammar',    'level' => 'C1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Checkpoint structures de la langue',                          'desc' => 'Évaluez votre maîtrise des structures grammaticales pour le TCF.',                                        'icon' => 'trophy',     'skill' => 'grammar',    'level' => 'B2', 'type' => 'practice', 'xp' => 75],
                ],
            ],
            // ── Chapitre 3 ──────────────────────────────────────
            [
                'name'  => 'Compréhension écrite',
                'nodes' => [
                    ['title' => 'Lire des documents courts de la vie quotidienne',             'desc' => 'Comprendre des affiches, formulaires et menus.',                                                         'icon' => 'book',       'skill' => 'reading',    'level' => 'A2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Comprendre des textes informatifs',                           'desc' => 'Lire et analyser des textes informatifs de longueur moyenne.',                                            'icon' => 'book',       'skill' => 'reading',    'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Analyser des textes argumentatifs',                           'desc' => 'Identifier la thèse et les arguments dans un texte d\'opinion.',                                          'icon' => 'book',       'skill' => 'reading',    'level' => 'B2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Comprendre des textes littéraires et académiques',            'desc' => 'Aborder des textes complexes de la littérature et du monde universitaire.',                                'icon' => 'book',       'skill' => 'reading',    'level' => 'C1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Lecture rapide et stratégies QCM',                            'desc' => 'Développer des techniques de lecture efficace pour les QCM.',                                              'icon' => 'zap',        'skill' => 'reading',    'level' => 'C1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Checkpoint compréhension écrite',                             'desc' => 'Évaluez vos acquis en compréhension écrite pour le TCF.',                                                 'icon' => 'trophy',     'skill' => 'reading',    'level' => 'B2', 'type' => 'practice', 'xp' => 75],
                ],
            ],
            // ── Chapitre 4 ──────────────────────────────────────
            [
                'name'  => 'Expression écrite (complémentaire)',
                'nodes' => [
                    ['title' => 'Rédiger un message court A1-A2',                              'desc' => 'Écrire des messages simples pour des situations quotidiennes.',                                           'icon' => 'pen',        'skill' => 'writing',    'level' => 'A2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Écrire un article ou courrier B1-B2',                         'desc' => 'Produire un texte structuré de type article ou lettre.',                                                  'icon' => 'pen',        'skill' => 'writing',    'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Texte argumentatif C1-C2',                                    'desc' => 'Rédiger un texte argumentatif complexe et nuancé.',                                                       'icon' => 'pen',        'skill' => 'writing',    'level' => 'C1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Stratégies pour les 3 tâches',                                'desc' => 'Maîtriser les attentes spécifiques de chaque tâche d\'expression écrite.',                                'icon' => 'target',     'skill' => 'writing',    'level' => 'C1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Checkpoint expression écrite',                                'desc' => 'Évaluez vos acquis en expression écrite pour le TCF.',                                                    'icon' => 'trophy',     'skill' => 'writing',    'level' => 'C1', 'type' => 'practice', 'xp' => 75],
                ],
            ],
            // ── Chapitre 5 ──────────────────────────────────────
            [
                'name'  => 'Expression orale (complémentaire)',
                'nodes' => [
                    ['title' => 'Entretien dirigé: se présenter',                              'desc' => 'Répondre à des questions sur soi dans un entretien structuré.',                                           'icon' => 'mic',        'skill' => 'speaking',   'level' => 'A2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Jeu de rôle: interagir',                                      'desc' => 'Simuler des interactions dans des contextes variés.',                                                     'icon' => 'mic',        'skill' => 'speaking',   'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Exprimer un point de vue',                                    'desc' => 'Développer un avis argumenté sur un sujet donné.',                                                        'icon' => 'mic',        'skill' => 'speaking',   'level' => 'C1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Stratégies de l\'oral',                                       'desc' => 'Techniques pour structurer sa parole et gérer le temps.',                                                 'icon' => 'target',     'skill' => 'speaking',   'level' => 'C1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Checkpoint expression orale',                                 'desc' => 'Évaluez vos acquis en expression orale pour le TCF.',                                                     'icon' => 'trophy',     'skill' => 'speaking',   'level' => 'C1', 'type' => 'practice', 'xp' => 75],
                ],
            ],
            // ── Chapitre 6 ──────────────────────────────────────
            [
                'name'  => 'Vocabulaire thématique',
                'nodes' => [
                    ['title' => 'Vocabulaire quotidien (famille, maison, travail)',             'desc' => 'Apprendre le vocabulaire fondamental de la sphère personnelle.',                                          'icon' => 'globe',      'skill' => 'reading',    'level' => 'A2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Vocabulaire médias et société',                               'desc' => 'Maîtriser le lexique des médias, de l\'actualité et de la société.',                                      'icon' => 'globe',      'skill' => 'reading',    'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Vocabulaire académique et professionnel',                     'desc' => 'Acquérir le vocabulaire spécialisé du monde universitaire et du travail.',                                 'icon' => 'globe',      'skill' => 'reading',    'level' => 'B2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Expressions idiomatiques et registres',                       'desc' => 'Connaître les expressions courantes et savoir adapter son registre.',                                      'icon' => 'star',       'skill' => 'reading',    'level' => 'C1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Checkpoint vocabulaire',                                      'desc' => 'Évaluez votre maîtrise du vocabulaire thématique pour le TCF.',                                            'icon' => 'trophy',     'skill' => 'reading',    'level' => 'C1', 'type' => 'practice', 'xp' => 75],
                ],
            ],
            // ── Chapitre 7 ──────────────────────────────────────
            [
                'name'  => 'Stratégie TCF',
                'nodes' => [
                    ['title' => 'Comprendre le format adaptatif du TCF',                       'desc' => 'Découvrir le fonctionnement du format adaptatif du TCF.',                                                 'icon' => 'target',     'skill' => 'mixed',      'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Stratégies QCM et élimination',                               'desc' => 'Maîtriser les techniques d\'élimination pour les questions à choix multiples.',                           'icon' => 'zap',        'skill' => 'mixed',      'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Gestion du temps par section',                                'desc' => 'Optimiser la répartition du temps entre les sections du TCF.',                                             'icon' => 'target',     'skill' => 'mixed',      'level' => 'B2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Optimiser son score global',                                  'desc' => 'Stratégies pour maximiser le score toutes sections confondues.',                                           'icon' => 'star',       'skill' => 'mixed',      'level' => 'C1', 'type' => 'lesson',   'xp' => 50],
                ],
            ],
            // ── Chapitre 8 ──────────────────────────────────────
            [
                'name'  => 'Entraînement intensif',
                'nodes' => [
                    ['title' => 'Série CO: 29 questions chronométrées',                        'desc' => 'Entraînement chronométré reproduisant les conditions du TCF CO.',                                         'icon' => 'headphones', 'skill' => 'listening',  'level' => 'B2', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'Série Structures: 18 questions chronométrées',                'desc' => 'Entraînement chronométré sur les structures de la langue.',                                               'icon' => 'brain',      'skill' => 'grammar',    'level' => 'B2', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'Série CE: 29 questions chronométrées',                        'desc' => 'Entraînement chronométré reproduisant les conditions du TCF CE.',                                          'icon' => 'book',       'skill' => 'reading',    'level' => 'B2', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'Série EE: 3 tâches chronométrées',                            'desc' => 'Entraînement chronométré sur les trois tâches d\'expression écrite.',                                      'icon' => 'pen',        'skill' => 'writing',    'level' => 'C1', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'Série EO: 3 tâches',                                          'desc' => 'Entraînement sur les trois tâches d\'expression orale du TCF.',                                           'icon' => 'mic',        'skill' => 'speaking',   'level' => 'C1', 'type' => 'practice', 'xp' => 75],
                ],
            ],
            // ── Chapitre 9 ──────────────────────────────────────
            [
                'name'  => 'Examens blancs',
                'nodes' => [
                    ['title' => 'Examen blanc: CO complète',                                   'desc' => 'Simulation complète de l\'épreuve de compréhension orale du TCF.',                                        'icon' => 'headphones', 'skill' => 'listening',  'level' => 'B2', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'Examen blanc: Structures complète',                           'desc' => 'Simulation complète de l\'épreuve de structures du TCF.',                                                 'icon' => 'brain',      'skill' => 'grammar',    'level' => 'B2', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'Examen blanc: CE complète',                                   'desc' => 'Simulation complète de l\'épreuve de compréhension écrite du TCF.',                                        'icon' => 'book',       'skill' => 'reading',    'level' => 'B2', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'Examen blanc: EE complète',                                   'desc' => 'Simulation complète de l\'épreuve d\'expression écrite du TCF.',                                           'icon' => 'pen',        'skill' => 'writing',    'level' => 'C1', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'Examen blanc: EO complète',                                   'desc' => 'Simulation complète de l\'épreuve d\'expression orale du TCF.',                                            'icon' => 'mic',        'skill' => 'speaking',   'level' => 'C1', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'Simulation: Épreuves obligatoires',                           'desc' => 'Simulation des trois épreuves obligatoires du TCF en conditions réelles.',                                 'icon' => 'star',       'skill' => 'mixed',      'level' => 'B2', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'Simulation: Toutes épreuves',                                 'desc' => 'Simulation intégrale de toutes les épreuves du TCF.',                                                      'icon' => 'star',       'skill' => 'mixed',      'level' => 'C1', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'TCF Examen Final Complet',                                    'desc' => 'Épreuve finale complète couvrant toutes les compétences TCF.',                                             'icon' => 'trophy',     'skill' => 'mixed',      'level' => 'C1', 'type' => 'boss',     'xp' => 200],
                ],
            ],
        ];

        $this->createNodes($exam, $chapters);
    }

    // ─────────────────────────────────────────────────────────────
    //  TEF  –  50 nodes, 9 chapters
    // ─────────────────────────────────────────────────────────────
    private function seedTef(): void
    {
        $exam = Exam::where('slug', 'tef')->first();
        if (!$exam) {
            $this->command?->warn('Exam tef not found – skipping.');
            return;
        }

        LearningPathNode::where('exam_id', $exam->id)->delete();

        $chapters = [
            // ── Chapitre 1 ──────────────────────────────────────
            [
                'name'  => 'Compréhension écrite',
                'nodes' => [
                    ['title' => 'Documents courts: panneaux et formulaires',                   'desc' => 'Lire et comprendre des panneaux, affiches et formulaires simples.',                                       'icon' => 'book',       'skill' => 'reading',    'level' => 'A1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Textes informatifs de complexité moyenne',                    'desc' => 'Comprendre des textes informatifs courants de longueur moyenne.',                                          'icon' => 'book',       'skill' => 'reading',    'level' => 'A2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Articles de presse et notices',                               'desc' => 'Lire des articles de presse et des notices d\'utilisation.',                                               'icon' => 'book',       'skill' => 'reading',    'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Textes argumentatifs et éditoriaux',                          'desc' => 'Analyser la structure et les arguments de textes d\'opinion.',                                              'icon' => 'book',       'skill' => 'reading',    'level' => 'B2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Lecture rapide et repérage d\'information',                    'desc' => 'Développer sa vitesse de lecture et ses compétences de repérage.',                                          'icon' => 'zap',        'skill' => 'reading',    'level' => 'C1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Checkpoint compréhension écrite',                             'desc' => 'Évaluez vos acquis en compréhension écrite pour le TEF.',                                                  'icon' => 'trophy',     'skill' => 'reading',    'level' => 'B2', 'type' => 'practice', 'xp' => 75],
                ],
            ],
            // ── Chapitre 2 ──────────────────────────────────────
            [
                'name'  => 'Compréhension orale',
                'nodes' => [
                    ['title' => 'Messages courts et annonces',                                 'desc' => 'Comprendre de brefs messages et annonces publiques.',                                                     'icon' => 'headphones', 'skill' => 'listening',  'level' => 'A1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Dialogues et conversations simples',                          'desc' => 'Suivre des dialogues simples entre locuteurs natifs.',                                                     'icon' => 'headphones', 'skill' => 'listening',  'level' => 'A2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Comprendre des échanges complexes',                           'desc' => 'Saisir le sens d\'échanges plus longs et nuancés.',                                                       'icon' => 'headphones', 'skill' => 'listening',  'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Documents radiophoniques et reportages',                      'desc' => 'Comprendre des reportages et documents radio authentiques.',                                                'icon' => 'headphones', 'skill' => 'listening',  'level' => 'B2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Conférences et exposés académiques',                          'desc' => 'Suivre des discours longs et complexes dans un cadre formel.',                                              'icon' => 'headphones', 'skill' => 'listening',  'level' => 'C1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Checkpoint compréhension orale',                              'desc' => 'Évaluez vos acquis en compréhension orale pour le TEF.',                                                   'icon' => 'trophy',     'skill' => 'listening',  'level' => 'B2', 'type' => 'practice', 'xp' => 75],
                ],
            ],
            // ── Chapitre 3 ──────────────────────────────────────
            [
                'name'  => 'Lexique et structure',
                'nodes' => [
                    ['title' => 'Vocabulaire de base et synonymes',                            'desc' => 'Maîtriser le vocabulaire fondamental et les synonymes courants.',                                          'icon' => 'brain',      'skill' => 'grammar',    'level' => 'A2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Antonymes, définitions et contexte',                          'desc' => 'Comprendre les contraires et utiliser le contexte pour déduire le sens.',                                   'icon' => 'brain',      'skill' => 'grammar',    'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Conjugaisons et accords',                                     'desc' => 'Réviser les règles de conjugaison et d\'accord grammatical.',                                               'icon' => 'brain',      'skill' => 'grammar',    'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Syntaxe et prépositions avancées',                            'desc' => 'Maîtriser la syntaxe complexe et les prépositions délicates.',                                               'icon' => 'brain',      'skill' => 'grammar',    'level' => 'B2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Registres de langue et nuances lexicales',                    'desc' => 'Distinguer les registres et comprendre les nuances de vocabulaire.',                                         'icon' => 'brain',      'skill' => 'grammar',    'level' => 'C1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Checkpoint lexique et structure',                             'desc' => 'Évaluez vos acquis en lexique et structure pour le TEF.',                                                    'icon' => 'trophy',     'skill' => 'grammar',    'level' => 'B2', 'type' => 'practice', 'xp' => 75],
                ],
            ],
            // ── Chapitre 4 ──────────────────────────────────────
            [
                'name'  => 'Expression écrite',
                'nodes' => [
                    ['title' => 'Rédiger un récit ou une description',                         'desc' => 'Écrire un texte narratif ou descriptif clair et structuré.',                                                'icon' => 'pen',        'skill' => 'writing',    'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Écrire une lettre formelle',                                  'desc' => 'Rédiger une lettre formelle respectant les conventions.',                                                    'icon' => 'pen',        'skill' => 'writing',    'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Argumentation: prendre position',                             'desc' => 'Défendre un point de vue avec des arguments structurés.',                                                    'icon' => 'pen',        'skill' => 'writing',    'level' => 'B2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Développer un raisonnement complexe',                         'desc' => 'Construire une argumentation nuancée et approfondie.',                                                       'icon' => 'pen',        'skill' => 'writing',    'level' => 'C1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Maîtriser l\'orthographe et la ponctuation',                  'desc' => 'Perfectionner son orthographe et l\'usage de la ponctuation.',                                               'icon' => 'pen',        'skill' => 'writing',    'level' => 'B2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Checkpoint expression écrite',                                'desc' => 'Évaluez vos acquis en expression écrite pour le TEF.',                                                       'icon' => 'trophy',     'skill' => 'writing',    'level' => 'C1', 'type' => 'practice', 'xp' => 75],
                ],
            ],
            // ── Chapitre 5 ──────────────────────────────────────
            [
                'name'  => 'Expression orale',
                'nodes' => [
                    ['title' => 'Recueillir des informations: jeu de rôle',                    'desc' => 'Obtenir des renseignements dans un jeu de rôle interactif.',                                                'icon' => 'mic',        'skill' => 'speaking',   'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Convaincre et argumenter',                                    'desc' => 'Utiliser des arguments pour convaincre un interlocuteur.',                                                   'icon' => 'mic',        'skill' => 'speaking',   'level' => 'B2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Débattre sur des sujets complexes',                           'desc' => 'Participer à un débat sur des thèmes de société exigeants.',                                                 'icon' => 'mic',        'skill' => 'speaking',   'level' => 'C1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Fluidité et prononciation',                                   'desc' => 'Améliorer sa fluidité, son intonation et sa prononciation.',                                                 'icon' => 'mic',        'skill' => 'speaking',   'level' => 'B2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Checkpoint expression orale',                                 'desc' => 'Évaluez vos acquis en expression orale pour le TEF.',                                                        'icon' => 'trophy',     'skill' => 'speaking',   'level' => 'C1', 'type' => 'practice', 'xp' => 75],
                ],
            ],
            // ── Chapitre 6 ──────────────────────────────────────
            [
                'name'  => 'Vocabulaire thématique',
                'nodes' => [
                    ['title' => 'Vie quotidienne et famille',                                  'desc' => 'Vocabulaire de la vie personnelle, familiale et domestique.',                                                'icon' => 'globe',      'skill' => 'reading',    'level' => 'A2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Travail, éducation et société',                               'desc' => 'Vocabulaire du monde professionnel, scolaire et social.',                                                   'icon' => 'globe',      'skill' => 'reading',    'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Politique, économie et environnement',                        'desc' => 'Vocabulaire des domaines politique, économique et écologique.',                                               'icon' => 'globe',      'skill' => 'reading',    'level' => 'B2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Sciences, culture et philosophie',                            'desc' => 'Vocabulaire avancé des sciences, arts et réflexion philosophique.',                                           'icon' => 'globe',      'skill' => 'reading',    'level' => 'C1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Checkpoint vocabulaire thématique',                           'desc' => 'Évaluez votre maîtrise du vocabulaire thématique pour le TEF.',                                               'icon' => 'trophy',     'skill' => 'reading',    'level' => 'C1', 'type' => 'practice', 'xp' => 75],
                ],
            ],
            // ── Chapitre 7 ──────────────────────────────────────
            [
                'name'  => 'Stratégie TEF',
                'nodes' => [
                    ['title' => 'Format du TEF et types de questions',                         'desc' => 'Découvrir la structure du TEF et les types de questions.',                                                   'icon' => 'target',     'skill' => 'mixed',      'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Stratégies QCM pour CE et CO',                                'desc' => 'Techniques d\'élimination et de déduction pour les épreuves QCM.',                                           'icon' => 'zap',        'skill' => 'mixed',      'level' => 'B1', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Gestion du temps optimal',                                    'desc' => 'Répartir son temps de manière optimale entre les sections.',                                                  'icon' => 'target',     'skill' => 'mixed',      'level' => 'B2', 'type' => 'lesson',   'xp' => 50],
                    ['title' => 'Maximiser le score par section',                              'desc' => 'Stratégies ciblées pour maximiser le score dans chaque épreuve.',                                              'icon' => 'star',       'skill' => 'mixed',      'level' => 'C1', 'type' => 'lesson',   'xp' => 50],
                ],
            ],
            // ── Chapitre 8 ──────────────────────────────────────
            [
                'name'  => 'Entraînement intensif',
                'nodes' => [
                    ['title' => 'Série CE: 50 questions chronométrées',                        'desc' => 'Entraînement chronométré reproduisant les conditions du TEF CE.',                                            'icon' => 'book',       'skill' => 'reading',    'level' => 'B2', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'Série CO: 60 questions chronométrées',                        'desc' => 'Entraînement chronométré reproduisant les conditions du TEF CO.',                                             'icon' => 'headphones', 'skill' => 'listening',  'level' => 'B2', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'Série Lexique: 40 questions chronométrées',                   'desc' => 'Entraînement chronométré sur le lexique et la structure.',                                                    'icon' => 'brain',      'skill' => 'grammar',    'level' => 'B2', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'Série EE: 2 tâches chronométrées',                            'desc' => 'Entraînement chronométré sur les deux tâches d\'expression écrite.',                                          'icon' => 'pen',        'skill' => 'writing',    'level' => 'C1', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'Série EO: 2 tâches',                                          'desc' => 'Entraînement sur les deux tâches d\'expression orale du TEF.',                                                'icon' => 'mic',        'skill' => 'speaking',   'level' => 'C1', 'type' => 'practice', 'xp' => 75],
                ],
            ],
            // ── Chapitre 9 ──────────────────────────────────────
            [
                'name'  => 'Examens blancs',
                'nodes' => [
                    ['title' => 'Examen blanc: CE complète',                                   'desc' => 'Simulation complète de l\'épreuve de compréhension écrite du TEF.',                                           'icon' => 'book',       'skill' => 'reading',    'level' => 'B2', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'Examen blanc: CO complète',                                   'desc' => 'Simulation complète de l\'épreuve de compréhension orale du TEF.',                                            'icon' => 'headphones', 'skill' => 'listening',  'level' => 'B2', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'Examen blanc: Lexique complet',                               'desc' => 'Simulation complète de l\'épreuve de lexique et structure du TEF.',                                            'icon' => 'brain',      'skill' => 'grammar',    'level' => 'B2', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'Examen blanc: EE complète',                                   'desc' => 'Simulation complète de l\'épreuve d\'expression écrite du TEF.',                                               'icon' => 'pen',        'skill' => 'writing',    'level' => 'C1', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'Examen blanc: EO complète',                                   'desc' => 'Simulation complète de l\'épreuve d\'expression orale du TEF.',                                                'icon' => 'mic',        'skill' => 'speaking',   'level' => 'C1', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'Simulation: Toutes épreuves',                                 'desc' => 'Simulation intégrale de toutes les épreuves du TEF.',                                                          'icon' => 'star',       'skill' => 'mixed',      'level' => 'C1', 'type' => 'practice', 'xp' => 75],
                    ['title' => 'TEF Examen Final Complet',                                    'desc' => 'Épreuve finale complète couvrant toutes les compétences TEF.',                                                 'icon' => 'trophy',     'skill' => 'mixed',      'level' => 'C1', 'type' => 'boss',     'xp' => 200],
                ],
            ],
        ];

        $this->createNodes($exam, $chapters);
    }

    // ─────────────────────────────────────────────────────────────
    //  Helper – bulk-create nodes from a chapters array
    // ─────────────────────────────────────────────────────────────
    private function createNodes(Exam $exam, array $chapters): void
    {
        foreach ($chapters as $chapterIndex => $chapter) {
            $chapterOrder = $chapterIndex + 1;

            foreach ($chapter['nodes'] as $nodeIndex => $node) {
                $sortOrder = $nodeIndex + 1;

                LearningPathNode::create([
                    'exam_id'         => $exam->id,
                    'chapter_name'    => $chapter['name'],
                    'chapter_order'   => $chapterOrder,
                    'sort_order'      => $sortOrder,
                    'title'           => $node['title'],
                    'description'     => $node['desc'],
                    'icon'            => $node['icon'],
                    'skill_type'      => $node['skill'],
                    'level'           => $node['level'],
                    'node_type'       => $node['type'],
                    'xp_reward'       => $node['xp'],
                    'mastery_score'   => 70,
                    'exercises_count' => 3,
                ]);
            }
        }
    }
}
