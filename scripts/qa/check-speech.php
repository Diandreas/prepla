<?php

// Real-provider smoke test: only these public, non-personal sample sentences
// are sent. No learner answer, account, secret or existing audio is modified.
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$samples = ['en' => 'Hello. Welcome to PrepLa.', 'fr' => 'Bonjour. Bienvenue sur PrepLa.', 'de' => 'Hallo. Willkommen bei PrepLa.'];
$results = [];
foreach ($samples as $language => $text) {
    $url = app(App\Services\TTS\DeepgramTtsService::class)->speak($text, $language);
    $file = 'tts/'.md5($text.$language).'.mp3';
    $disk = Illuminate\Support\Facades\Storage::disk('public');
    $bytes = $url && $disk->exists($file) ? $disk->get($file) : '';
    $isMp3 = strlen($bytes) > 100 && (str_starts_with($bytes, 'ID3')
        || (ord($bytes[0]) === 255 && (ord($bytes[1]) & 224) === 224));
    $results[] = ['language' => $language, 'valid_mp3' => $isMp3, 'bytes' => strlen($bytes), 'url' => $url];
}
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
exit(collect($results)->every(fn ($result) => $result['valid_mp3']) ? 0 : 1);
