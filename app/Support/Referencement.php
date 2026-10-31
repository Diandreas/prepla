<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * Ce qu'une page publique annonce aux moteurs et aux reseaux.
 *
 * Le gabarit Inertia a besoin du titre AVANT d'inclure les balises, et ne peut
 * pas le relire depuis un partiel : la resolution vit donc ici, une fois, et le
 * gabarit comme le partiel s'en servent.
 */
final class Referencement
{
    /**
     * @return array{titre: string, description: string, image: string, indexable: bool}
     */
    public static function pour(?string $route = null): array
    {
        $route ??= Route::currentRouteName();

        $page = $route !== null ? config("seo.pages.{$route}", []) : [];
        $valeurs = array_merge(config('seo.defaut', []), is_array($page) ? $page : []);

        return [
            'titre' => (string) ($valeurs['titre'] ?? 'PrePla'),
            'description' => (string) ($valeurs['description'] ?? ''),
            'image' => url('/'.ltrim((string) ($valeurs['image'] ?? 'og/prepla.png'), '/')),
            'indexable' => (bool) ($valeurs['indexable'] ?? true),
        ];
    }
}
