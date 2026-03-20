<?php

namespace Database\Seeders;

use Database\Seeders\Syllabus\EnglishSyllabusSeeder;
use Database\Seeders\Syllabus\FrenchSyllabusSeeder;
use Illuminate\Database\Seeder;

class ExamSyllabusSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            EnglishSyllabusSeeder::class,
            FrenchSyllabusSeeder::class,
        ]);
    }
}
