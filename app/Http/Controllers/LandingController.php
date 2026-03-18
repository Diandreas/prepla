<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function __invoke(): Response
    {
        $config = config('languages');

        return Inertia::render('welcome', [
            'languages' => $config['languages'],
            'pricing' => $config['pricing'],
        ]);
    }
}
