<?php

namespace App\Services\Center;

/**
 * Les deux lectures DNS de la verification d'un domaine propre. Isolees ici
 * pour que les tests les remplacent sans toucher au reseau.
 */
class ResolveurDns
{
    /** @return array<int, string> les valeurs des enregistrements TXT du nom */
    public function txt(string $nom): array
    {
        $enregistrements = @dns_get_record($nom, DNS_TXT) ?: [];

        return array_values(array_map(
            fn (array $e) => isset($e['entries']) ? implode('', (array) $e['entries']) : (string) ($e['txt'] ?? ''),
            $enregistrements,
        ));
    }

    /** @return array<int, string> les cibles des enregistrements CNAME du nom, sans point final */
    public function cname(string $nom): array
    {
        $enregistrements = @dns_get_record($nom, DNS_CNAME) ?: [];

        return array_values(array_map(
            fn (array $e) => strtolower(rtrim((string) ($e['target'] ?? ''), '.')),
            $enregistrements,
        ));
    }
}
