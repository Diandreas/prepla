import { createContext, useContext } from 'react';
import type { AppIconName } from './components/AppAssets';

// Textes et données des scènes partagées, par version de la vidéo. Les scènes les lisent via
// usePromoContent() : la version française et la version allemande partagent ainsi le même
// montage, la même animation et la même grille musicale.

export type Chip = { text: string; x: number; y: number; z: number; icon?: AppIconName };

export interface PromoContent {
    chaos: { lines: string[]; chips: Chip[] };
    logo: { tagline: string[] };
    exam: {
        headline: string[];
        pickLang: number;
        exams: Array<{ label: string; w: number }>;
        /** Niveaux proposés après l'examen (Goethe : A1 → C2). */
        levels?: string[];
        pickLevel?: number;
        goal: [string, string];
        stats: Array<{ value: string; label: string }>;
    };
    placement: {
        sub: string;
        a: { title: string; count: string; instruction: string; before: string; answer: string; after: string; options: string[]; correct: number };
        b: { passage: string; question: string; options: string[]; correct: number };
        c: { prompt: string; essay: string };
        chips: [string, string];
    };
    path: {
        mission: { title: string; subtitle?: string };
        inserted: { label: string; title: string; meta: string };
        goal: { label: string; title: string; meta: string };
        caption: string;
    };
    formats: { skills: [string, string, string, string] };
    progress: {
        pill: string;
        skills: Array<{ label: string; value: number; tone: 'sky' | 'gold' }>;
        ring: { label: string; value: string };
        errors: { strong: string; rest: string; icon: AppIconName };
    };
    cta: {
        first: string[];
        second: string[];
        secondSize: number;
        secondTop: number;
        title: string;
        titleAccent: string;
        description: string;
        exams: string[];
        legal: string;
        foxSays?: string;
    };
}

export const FR: PromoContent = {
    chaos: {
        lines: ['Tu révises', '*au hasard* ?'],
        chips: [
            { text: 'Grammaire ?', x: 250, y: 400, z: 0.95, icon: 'courses' },
            { text: 'Par où commencer ?', x: 690, y: 330, z: 0.72 },
            { text: 'Vocabulaire ?', x: 790, y: 540, z: 1.0, icon: 'vocabulary' },
            { text: 'Quel niveau ?', x: 290, y: 650, z: 1.08, icon: 'statistics' },
            { text: 'Subjonctif ?', x: 560, y: 470, z: 0.6 },
            { text: 'Oral ?', x: 220, y: 1200, z: 1.02, icon: 'speaking' },
            { text: 'Combien de temps ?', x: 650, y: 1250, z: 0.86, icon: 'clock' },
            { text: 'Écoute ?', x: 870, y: 1420, z: 0.74, icon: 'listening' },
            { text: 'Conjugaison ?', x: 330, y: 1430, z: 0.92 },
            { text: 'Écrit ?', x: 640, y: 1580, z: 1.06, icon: 'writing' },
            { text: 'Lecture ?', x: 870, y: 1120, z: 0.64 },
            { text: 'Quel examen ?', x: 190, y: 1600, z: 0.7, icon: 'target' },
        ],
    },
    logo: { tagline: ['Ton *examen*.', 'Ton *niveau*.', 'Ton *parcours*.'] },
    exam: {
        headline: ['Choisis ton', '*examen*'],
        pickLang: 1,
        exams: [
            { label: 'TCF', w: 210 },
            { label: 'TEF', w: 210 },
            { label: 'DELF / DALF', w: 380 },
        ],
        goal: ['Niveau B2', 'dans 8 semaines'],
        stats: [
            { value: '3', label: 'langues' },
            { value: '8', label: 'examens officiels' },
        ],
    },
    placement: {
        sub: 'Grammaire · Lecture · Rédaction',
        a: {
            title: 'Grammaire & Vocabulaire',
            count: '4 / 8',
            instruction: 'Complète la phrase :',
            before: 'Il faut que tu',
            answer: 'viennes',
            after: "à l'heure.",
            options: ['viens', 'viennes', 'venir', 'viendras'],
            correct: 1,
        },
        b: {
            passage: "Le télétravail s'est beaucoup développé. Il offre plus de liberté, mais il peut aussi isoler les salariés.",
            question: 'Selon le texte, quel est un inconvénient du télétravail ?',
            options: ['Le manque de liberté', "L'isolement", 'Le coût des transports'],
            correct: 1,
        },
        c: { prompt: 'Donne ton avis sur les réseaux sociaux.', essay: 'À mon avis, les réseaux sociaux rapprochent les gens, mais…' },
        chips: ['Point fort : la lecture', 'À travailler : l’oral'],
    },
    path: {
        mission: { title: 'Le subjonctif' },
        inserted: { label: 'Ajouté pour toi', title: 'Révision ciblée', meta: "d'après tes erreurs" },
        goal: { label: 'Objectif', title: 'Examen blanc', meta: '' },
        caption: "Ton plan s'adapte à tes erreurs.",
    },
    formats: { skills: ['Lecture', 'Écoute', 'Écrit', 'Oral'] },
    progress: {
        pill: 'TCF · B1',
        skills: [
            { label: 'Compréhension écrite', value: 78, tone: 'sky' },
            { label: 'Compréhension orale', value: 64, tone: 'sky' },
            { label: 'Expression écrite', value: 58, tone: 'gold' },
            { label: 'Expression orale', value: 46, tone: 'gold' },
        ],
        ring: { label: 'Objectif', value: 'Vers le B2' },
        errors: { strong: '6 erreurs', rest: " à revoir aujourd'hui", icon: 'review' },
    },
    cta: {
        first: ['Ne révise plus', '*au hasard*.'],
        second: ['Prépare-toi', 'avec *méthode*.'],
        secondSize: 132,
        secondTop: 760,
        title: 'Fais ton diagnostic',
        titleAccent: 'gratuit',
        description: 'Ton niveau en quelques minutes, puis un parcours fait pour ton examen.',
        exams: ['IELTS · TOEFL · Cambridge · DELF/DALF', 'TCF · TEF · Goethe · TestDaF'],
        legal: 'PrePla est une plateforme indépendante, non affiliée aux organismes certificateurs.',
    },
};

// Version allemande : examens Goethe-Zertifikat et TestDaF (config/exams/goethe.php, testdaf.php),
// thèmes du parcours allemand de l'app (database/data/curriculums/german.json).
export const DE: PromoContent = {
    chaos: {
        lines: ['Tu révises', '*au hasard* ?'],
        chips: [
            { text: 'Akkusativ?', x: 250, y: 400, z: 0.95, icon: 'courses' },
            { text: 'Trennbare Verben?', x: 690, y: 330, z: 0.72 },
            { text: 'Wortschatz?', x: 790, y: 540, z: 1.0, icon: 'vocabulary' },
            { text: 'Welches Niveau?', x: 300, y: 650, z: 1.08, icon: 'statistics' },
            { text: 'Dativ?', x: 560, y: 470, z: 0.6 },
            { text: 'Sprechen?', x: 230, y: 1200, z: 1.02, icon: 'speaking' },
            { text: 'Wo steht das Verb?', x: 660, y: 1250, z: 0.86, icon: 'clock' },
            { text: 'Hören?', x: 880, y: 1420, z: 0.74, icon: 'listening' },
            { text: 'Perfekt?', x: 330, y: 1430, z: 0.92 },
            { text: 'Schreiben?', x: 640, y: 1580, z: 1.06, icon: 'writing' },
            { text: 'Konjunktiv II?', x: 850, y: 1120, z: 0.64 },
            { text: 'Goethe ou TestDaF ?', x: 230, y: 1600, z: 0.7, icon: 'target' },
        ],
    },
    logo: { tagline: ['Deine *Prüfung*.', 'Dein *Niveau*.', 'Dein *Weg*.'] },
    exam: {
        headline: ['Choisis ton', '*certificat*'],
        pickLang: 2,
        exams: [
            { label: 'Goethe-Zertifikat', w: 470 },
            { label: 'TestDaF', w: 290 },
        ],
        levels: ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'],
        pickLevel: 2,
        goal: ['Goethe B1', 'dans 8 semaines'],
        stats: [
            { value: '6', label: 'niveaux Goethe' },
            { value: '', label: 'TestDaF' },
        ],
    },
    placement: {
        sub: 'Grammaire · Lecture · Rédaction',
        a: {
            title: 'Grammaire & Vocabulaire',
            count: '4 / 8',
            instruction: 'Choisis le bon article :',
            before: '',
            answer: 'Das',
            after: 'Mädchen spielt im Garten.',
            options: ['Der', 'Die', 'Das', 'Den'],
            correct: 2,
        },
        b: {
            passage: 'Lena wohnt seit einem Jahr in Hamburg. Sie arbeitet in einem Café und besucht abends einen Deutschkurs.',
            question: 'Que fait Lena le soir ?',
            options: ['Elle travaille au café', "Elle suit un cours d'allemand", 'Elle rentre à Hambourg'],
            correct: 1,
        },
        c: { prompt: "Explique en allemand pourquoi tu apprends l'allemand.", essay: 'Ich lerne Deutsch, weil ich in Deutschland studieren möchte.' },
        chips: ['Point fort : Lesen', 'À travailler : Sprechen'],
    },
    path: {
        mission: { title: 'Nebensätze', subtitle: 'weil · dass · wenn' },
        inserted: { label: 'Révision ciblée', title: 'der, die, das', meta: "d'après tes erreurs" },
        goal: { label: 'Objectif', title: 'Goethe B1', meta: 'Modellprüfung' },
        caption: "Ton plan s'adapte à tes erreurs.",
    },
    formats: { skills: ['Lesen', 'Hören', 'Schreiben', 'Sprechen'] },
    progress: {
        pill: 'Goethe · B1',
        skills: [
            { label: 'Lesen', value: 78, tone: 'sky' },
            { label: 'Hören', value: 64, tone: 'sky' },
            { label: 'Schreiben', value: 58, tone: 'gold' },
            { label: 'Sprechen', value: 46, tone: 'gold' },
        ],
        ring: { label: 'Objectif', value: 'Goethe B1' },
        errors: { strong: '6 mots', rest: ' à revoir : der, die, das', icon: 'vocabulary' },
    },
    cta: {
        first: ['Ne révise plus', '*au hasard*.'],
        second: ['Prépare', 'ton *Goethe*', 'avec méthode.'],
        secondSize: 112,
        secondTop: 720,
        title: "Fais ton diagnostic d'allemand",
        titleAccent: 'gratuit',
        description: 'Ton niveau, puis un parcours pour ton Goethe ou ton TestDaF.',
        exams: ['Goethe-Zertifikat A1 – C2 · TestDaF'],
        legal: 'PrePla est une plateforme indépendante, non affiliée au Goethe-Institut ni au TestDaF-Institut.',
        foxSays: "Los geht's!",
    },
};

export const PromoContentContext = createContext<PromoContent>(FR);

export const usePromoContent = () => useContext(PromoContentContext);
