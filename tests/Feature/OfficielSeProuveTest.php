<?php

/**
 * « Officiel » doit se prouver.
 *
 * Le champ `source => officiel` d'une partie veut dire : lu dans le document de
 * l'organisme, avec ses chiffres. C'est ce qui autorise l'application a annoncer
 * un nombre d'items a l'apprenant, et c'est sur cette foi qu'on retire du
 * contenu qui ne correspond pas. Or c'etait une simple affirmation : n'importe
 * qui pouvait l'ecrire, et trois paliers tres frequentes la portaient sans
 * qu'aucun document ne soit cite.
 *
 * Un palier qui la revendique doit donc nommer le document et la date de
 * consultation, dans la cle `verifications` de son examen.
 */
function configsDExamen(): array
{
    $out = [];
    foreach (glob(base_path('config/exams/*.php')) as $fichier) {
        $out[basename($fichier, '.php')] = require $fichier;
    }

    return $out;
}

/** Les paliers dont au moins une partie se dit officielle. */
function paliersQuiSeDisentOfficiels(array $config): array
{
    $paliers = [];
    $niveaux = $config['levels'] ?? ['—' => ['sections' => $config['sections'] ?? []]];

    foreach ($niveaux as $niveau => $L) {
        foreach ($L['sections'] ?? [] as $section) {
            foreach ($section['parts'] ?? [] as $partie) {
                if (($partie['source'] ?? null) === 'officiel') {
                    $paliers[(string) $niveau] = true;
                    break 2;
                }
            }
        }
    }

    return array_keys($paliers);
}

test('un palier qui se dit officiel nomme son document et sa date', function () {
    $sansPreuve = [];

    foreach (configsDExamen() as $slug => $config) {
        // Les examens dont aucune partie ne se dit officielle n'ont rien a prouver.
        $attestations = $config['verifications'] ?? [];

        foreach (paliersQuiSeDisentOfficiels($config) as $niveau) {
            // Un examen sans paliers — le TestDaF n'en a pas, c'est une epreuve
            // unique notee TDN 3 a 5 — est atteste par son unique attestation,
            // quel que soit le nom qu'elle porte.
            $preuve = empty($config['levels']) && count($attestations) === 1
                ? reset($attestations)
                : ($attestations[$niveau] ?? null);

            if (! is_array($preuve) || trim((string) ($preuve['document'] ?? '')) === ''
                || trim((string) ($preuve['date'] ?? '')) === '') {
                $sansPreuve[] = "{$slug} {$niveau}";
            }
        }
    }

    expect($sansPreuve)->toBe([]);
});

test('une attestation porte une date lisible et pas future', function () {
    $douteuses = [];

    foreach (configsDExamen() as $slug => $config) {
        foreach ($config['verifications'] ?? [] as $niveau => $preuve) {
            $date = $preuve['date'] ?? '';
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date)) {
                $douteuses[] = "{$slug} {$niveau} : date « {$date} » illisible";

                continue;
            }
            if (strtotime($date) > time()) {
                $douteuses[] = "{$slug} {$niveau} : date « {$date} » dans le futur";
            }
        }
    }

    expect($douteuses)->toBe([]);
});

test('une attestation ne vaut que pour un palier qui existe', function () {
    // Une preuve orpheline signale un renommage de niveau : la revendication
    // d'a cote se retrouverait sans rien pour la soutenir.
    $orphelines = [];

    foreach (configsDExamen() as $slug => $config) {
        $niveaux = array_map('strval', array_keys($config['levels'] ?? []));
        if ($niveaux === []) {
            continue;
        }

        foreach (array_keys($config['verifications'] ?? []) as $niveau) {
            if (! in_array((string) $niveau, $niveaux, true)) {
                $orphelines[] = "{$slug} : attestation pour « {$niveau} », qui n'est pas un palier de cet examen";
            }
        }
    }

    expect($orphelines)->toBe([]);
});
