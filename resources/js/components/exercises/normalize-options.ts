import { optionText } from '@/lib/scoring';

// Le générateur IA renvoie parfois les options d'un exercice à choix sous une
// forme inattendue. Si on rend un objet directement comme enfant React, on
// déclenche "Minified React error #31" qui blanchit toute la page d'exercice.
// On normalise donc systématiquement en string[] avant tout rendu.
//
// Formes gérées :
//  - array de strings (cas normal)              ["...", "..."]
//  - objet associatif lettré                     { A: "...", B: "...", ... }
//  - array contenant un seul objet lettré        [{ A: "...", B: "...", ... }]
//  - array d'objets { text | label | value }     [{ text: "..." }, ...]
export function normalizeOptions(raw: unknown): string[] {
    if (Array.isArray(raw)) {
        // Cas [{ A, B, C, D }] : un seul objet lettré dans un array → on l'aplatit
        if (raw.length === 1 && raw[0] && typeof raw[0] === 'object' && !Array.isArray(raw[0])) {
            return normalizeOptions(raw[0]);
        }
        return raw.map(coerceOption);
    }
    if (raw && typeof raw === 'object') {
        // Objet { A: "...", B: "..." } → valeurs dans l'ordre des clés
        return Object.values(raw as Record<string, unknown>).map(coerceOption);
    }
    return [];
}

// Une seule regle de lecture, partagee avec les deux correcteurs : l'ecran
// affichait un choix que la correction ne savait pas lire, et inversement.
export function coerceOption(o: unknown): string {
    return optionText(o);
}
