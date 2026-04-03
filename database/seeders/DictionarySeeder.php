<?php

namespace Database\Seeders;

use App\Models\DictionaryWord;
use Illuminate\Database\Seeder;

class DictionarySeeder extends Seeder
{
    public function run(): void
    {
        $words = [
            // Français
            ['word' => 'Ailleurs', 'language' => 'Français', 'definition' => 'Dans un autre lieu.', 'example' => 'Je voudrais être ailleurs.', 'translation' => 'Elsewhere', 'skill_level' => 'A2'],
            ['word' => 'Néanmoins', 'language' => 'Français', 'definition' => 'Indique une opposition.', 'example' => 'Il pleut, néanmoins il sort.', 'translation' => 'Nevertheless', 'skill_level' => 'B1'],
            ['word' => 'Déception', 'language' => 'Français', 'definition' => 'Fait d\'être déçu.', 'example' => 'Quelle déception !', 'translation' => 'Disappointment', 'skill_level' => 'A2'],
            
            // English
            ['word' => 'Resilient', 'language' => 'English', 'definition' => 'Able to withstand or recover quickly from difficult conditions.', 'example' => 'She is very resilient.', 'translation' => 'Résilient', 'skill_level' => 'B2'],
            ['word' => 'Ubiquitous', 'language' => 'English', 'definition' => 'Present, appearing, or found everywhere.', 'example' => 'Smartphones are ubiquitous.', 'translation' => 'Omniprésent', 'skill_level' => 'C1'],
            ['word' => 'Incentive', 'language' => 'English', 'definition' => 'A thing that motivates or encourages someone to do something.', 'example' => 'There is a tax incentive for new businesses.', 'translation' => 'Incitation', 'skill_level' => 'B2'],
            
            // Deutsch
            ['word' => 'Herausforderung', 'language' => 'Deutsch', 'definition' => 'Eine schwierige Aufgabe.', 'example' => 'Das ist eine große Herausforderung.', 'translation' => 'Challenge', 'skill_level' => 'B1'],
            ['word' => 'Selbstverständlich', 'language' => 'Deutsch', 'definition' => 'Natürlich, ohne Zweifel.', 'example' => 'Das ist selbstverständlich.', 'translation' => 'Of course', 'skill_level' => 'A2'],
            ['word' => 'Entwicklung', 'language' => 'Deutsch', 'definition' => 'Prozess der Veränderung.', 'example' => 'Die Entwicklung ist positiv.', 'translation' => 'Development', 'skill_level' => 'B2'],
            
            // German Academic B2/C1
            ['word' => 'berücksichtigen', 'language' => 'Deutsch', 'definition' => 'Etwas in seine Überlegungen einbeziehen.', 'example' => 'Wir müssen die Kosten berücksichtigen.', 'translation' => 'Take into account', 'skill_level' => 'B2'],
            ['word' => 'hervorheben', 'language' => 'Deutsch', 'definition' => 'Etwas besonders betonen.', 'example' => 'Der Autor möchte dieses Problem hervorheben.', 'translation' => 'Highlight / Emphasize', 'skill_level' => 'C1'],
            ['word' => 'schlussfolgern', 'language' => 'Deutsch', 'definition' => 'Einen logischen Schluss ziehen.', 'example' => 'Daraus lässt sich schlussfolgern, dass...', 'translation' => 'Conclude', 'skill_level' => 'C1'],
            ['word' => 'erörtern', 'language' => 'Deutsch', 'definition' => 'Ein Thema ausführlich diskutieren.', 'example' => 'In diesem Aufsatz werden wir das Thema erörtern.', 'translation' => 'Discuss / Examine', 'skill_level' => 'C1'],
            ['word' => 'infolgedessen', 'language' => 'Deutsch', 'definition' => 'Als Folge davon.', 'example' => 'Es regnete, infolgedessen blieb er zu Hause.', 'translation' => 'Consequently', 'skill_level' => 'B2'],
            ['word' => 'gewährleisten', 'language' => 'Deutsch', 'definition' => 'Etwas sicherstellen.', 'example' => 'Wir müssen die Sicherheit gewährleisten.', 'translation' => 'Ensure / Guarantee', 'skill_level' => 'C1'],
            ['word' => 'überwiegend', 'language' => 'Deutsch', 'definition' => 'Zum größten Teil.', 'example' => 'Die Teilnehmer waren überwiegend Studenten.', 'translation' => 'Predominantly', 'skill_level' => 'B2'],
        ];

        foreach ($words as $word) {
            DictionaryWord::create($word);
        }
    }
}
