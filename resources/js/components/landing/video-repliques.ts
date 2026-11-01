/**
 * Ce que dit la video « PrePla, par ou commencer ? », replique par replique,
 * calee sur ses sous-titres incrustes (releve image par image le 2026-11-01).
 *
 * Le francais est le texte du film ; l'anglais et l'allemand en sont la
 * traduction : le film n'existe qu'en francais. Ils servent a la transcription
 * sous le lecteur, et pendant la lecture a une bande traduite HORS de l'image,
 * qui ne recouvre jamais les sous-titres francais.
 */
export interface Replique {
    debut: number;
    fin: number;
    texte: string;
}

export const REPLIQUES: Record<'fr' | 'en' | 'de', Replique[]> = {
    fr: [
        { debut: 0, fin: 5, texte: 'Tu prépares un examen de langue et tu ne sais pas exactement par où commencer ?' },
        { debut: 5, fin: 11, texte: 'Avec PrePla, tu choisis ton examen, tu évalues ton niveau et tu identifies ce que tu dois travailler.' },
        { debut: 11, fin: 16, texte: 'Ensuite, tu avances avec un plan de révision et des exercices adaptés à ta préparation.' },
        { debut: 16, fin: 20.5, texte: 'Vous préparez la rédaction, l’écoute, le parler, tout à la maison.' },
        { debut: 20.5, fin: 29.5, texte: 'Et oui, PrePla, c’est l’application qui vous manquait pour être reçu et maximiser tous vos points à vos examens de langue.' },
        { debut: 29.5, fin: 31.2, texte: 'Qu’est-ce que vous attendez ?' },
    ],
    en: [
        { debut: 0, fin: 5, texte: 'Preparing for a language exam and not sure exactly where to start?' },
        { debut: 5, fin: 11, texte: 'With PrePla, you choose your exam, assess your level and find out what you need to work on.' },
        { debut: 11, fin: 16, texte: 'Then you move forward with a revision plan and exercises suited to your preparation.' },
        { debut: 16, fin: 20.5, texte: 'You prepare writing, listening and speaking, all from home.' },
        { debut: 20.5, fin: 29.5, texte: 'Yes, PrePla is the app you were missing to pass and get every point you can in your language exams.' },
        { debut: 29.5, fin: 31.2, texte: 'What are you waiting for?' },
    ],
    de: [
        { debut: 0, fin: 5, texte: 'Du bereitest dich auf eine Sprachprüfung vor und weißt nicht genau, wo du anfangen sollst?' },
        { debut: 5, fin: 11, texte: 'Mit PrePla wählst du deine Prüfung, schätzt dein Niveau ein und findest heraus, woran du arbeiten musst.' },
        { debut: 11, fin: 16, texte: 'Dann kommst du mit einem Lernplan und Übungen voran, die zu deiner Vorbereitung passen.' },
        { debut: 16, fin: 20.5, texte: 'Ihr bereitet Schreiben, Hören und Sprechen vor, alles von zu Hause aus.' },
        { debut: 20.5, fin: 29.5, texte: 'Ja, PrePla ist die App, die euch gefehlt hat, um zu bestehen und bei euren Sprachprüfungen jeden Punkt zu holen.' },
        { debut: 29.5, fin: 31.2, texte: 'Worauf wartet ihr noch?' },
    ],
};
