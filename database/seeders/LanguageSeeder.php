<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = config('languages.languages');

        foreach ($languages as $lang) {
            Language::updateOrCreate(
                ['slug' => $lang['slug']],
                [
                    'name' => $lang['name'],
                    'native_name' => $lang['native_name'],
                    'flag' => $lang['flag'],
                    'is_active' => true,
                ]
            );
        }
    }
}
