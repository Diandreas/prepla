<?php

namespace App\Services\Cours;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Le texte riche ecrit par un centre, assaini cote serveur avant d'etre stocke
 * puis affiche aux eleves : une liste blanche de balises et d'attributs.
 *
 * - balises gardees : titres, paragraphes, gras/italique/souligne/barre, listes,
 *   citations, liens, images, separateurs ;
 * - balises dangereuses (script, style, iframe, object, form...) : supprimees
 *   avec leur contenu ; toute autre balise inconnue : remplacee par son texte ;
 * - liens : http(s) ou mailto seulement, ouverts dans un nouvel onglet ;
 * - images : http(s) ou /storage/ seulement ; jamais de style, d'evenement (on*).
 */
class HtmlAssaini
{
    private const BALISES = [
        'p' => [], 'br' => [], 'hr' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'h2' => [], 'h3' => [], 'h4' => [], 'ul' => [], 'ol' => [], 'li' => [], 'blockquote' => [],
        'a' => ['href'], 'img' => ['src', 'alt'], 'div' => [], 'span' => [],
    ];

    private const A_SUPPRIMER = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea',
        'select', 'link', 'meta', 'base', 'svg', 'math', 'template', 'noscript', 'frame', 'frameset', 'applet', 'video', 'audio'];

    public static function nettoyer(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $ancien = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"?><div id="racine">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($ancien);

        $racine = $doc->getElementById('racine');
        if (! $racine) {
            return '';
        }
        self::parcourir($racine);

        $sortie = '';
        foreach (iterator_to_array($racine->childNodes) as $enfant) {
            $sortie .= $doc->saveHTML($enfant);
        }

        return trim($sortie);
    }

    private static function parcourir(DOMNode $noeud): void
    {
        foreach (iterator_to_array($noeud->childNodes) as $enfant) {
            if ($enfant->nodeType === XML_COMMENT_NODE || $enfant->nodeType === XML_PI_NODE || $enfant->nodeType === XML_CDATA_SECTION_NODE) {
                $noeud->removeChild($enfant);

                continue;
            }
            if (! $enfant instanceof DOMElement) {
                continue;
            }
            $nom = strtolower($enfant->nodeName);
            if (in_array($nom, self::A_SUPPRIMER, true)) {
                $noeud->removeChild($enfant);

                continue;
            }
            self::parcourir($enfant);
            if (! array_key_exists($nom, self::BALISES)) {
                // Balise inconnue : on garde son contenu, pas la balise.
                while ($enfant->firstChild) {
                    $noeud->insertBefore($enfant->firstChild, $enfant);
                }
                $noeud->removeChild($enfant);

                continue;
            }
            self::attributs($enfant, self::BALISES[$nom]);
            if ($nom === 'img' && ! $enfant->hasAttribute('src')) {
                $noeud->removeChild($enfant);
            }
        }
    }

    /** @param  list<string>  $permis */
    private static function attributs(DOMElement $el, array $permis): void
    {
        foreach (iterator_to_array($el->attributes) as $attr) {
            if (! in_array(strtolower($attr->nodeName), $permis, true)) {
                $el->removeAttribute($attr->nodeName);
            }
        }
        $nom = strtolower($el->nodeName);
        if ($nom === 'a') {
            $href = trim($el->getAttribute('href'));
            if ($href === '' || ! preg_match('#^(https?://|mailto:)#i', $href)) {
                $el->removeAttribute('href');
            } else {
                $el->setAttribute('target', '_blank');
                $el->setAttribute('rel', 'noopener noreferrer nofollow');
            }
        }
        if ($nom === 'img') {
            $src = trim($el->getAttribute('src'));
            if (! preg_match('#^(https?://|/storage/)#i', $src)) {
                $el->removeAttribute('src');
            }
        }
    }
}
