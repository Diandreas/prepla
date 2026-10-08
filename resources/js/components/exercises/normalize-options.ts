import { optionList, optionText } from '@/lib/scoring';

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
// Une seule regle de mise a plat, partagee avec les deux correcteurs : l'ecran
// montrait quatre fois le meme choix la ou la correction en attendait quatre
// differents.
export function normalizeOptions(raw: unknown): string[] {
    return optionList(raw);
}

// Une seule regle de lecture, partagee avec les deux correcteurs : l'ecran
// affichait un choix que la correction ne savait pas lire, et inversement.
export function coerceOption(o: unknown): string {
    return optionText(o);
}
