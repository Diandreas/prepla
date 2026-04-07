<?php

namespace Database\Seeders;

use App\Models\DictionaryWord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class DictionarySeeder extends Seeder
{
    public function run(): void
    {
        $languages = ['en', 'fr', 'de'];
        
        foreach ($languages as $lang) {
            $files = glob(database_path("data/dictionary/{$lang}*.json"));
            
            foreach ($files as $path) {
                if (file_exists($path)) {
                    $json = file_get_contents($path);
                    $words = json_decode($json, true);
                    
                    if (is_array($words)) {
                        $this->command->info("Importing " . count($words) . " words from " . basename($path) . "...");
                        foreach ($words as $word) {
                            try {
                                DictionaryWord::updateOrCreate(
                                    ['word' => $word['word'], 'language' => $lang],
                                    [
                                        'definition' => $word['definition'],
                                        'example' => $word['example'],
                                        'translation' => $word['translation'],
                                        'skill_level' => $word['skill_level'] ?? 'B2'
                                    ]
                                );
                            } catch (\Exception $e) {
                                $this->command->error("Error importing [{$word['word']}]: " . $e->getMessage());
                            }
                        }
                    }
                }
            }
        }
    }
}
