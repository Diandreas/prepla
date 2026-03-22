export interface Exam {
    slug: string;
    name: string;
    levels?: string[] | null;
}

export interface Language {
    slug: string;
    name: string;
    native_name: string;
    flag: string;
    exams: Exam[];
}

export interface PricingPlan {
    name: string;
    slug: string;
    price: number;
    period: string | null;
    features: string[];
    cta: string;
    highlighted: boolean;
}

export const languages: Language[] = [
    {
        slug: 'english',
        name: 'English',
        native_name: 'English',
        flag: '🇬🇧',
        exams: [
            { slug: 'ielts', name: 'IELTS' },
            { slug: 'toefl', name: 'TOEFL' },
            { slug: 'cambridge', name: 'Cambridge', levels: ['A2 Key', 'B1 Preliminary', 'B2 First', 'C1 Advanced', 'C2 Proficiency'] },
        ],
    },
    {
        slug: 'french',
        name: 'Français',
        native_name: 'Français',
        flag: '🇫🇷',
        exams: [
            { slug: 'tcf', name: 'TCF' },
            { slug: 'tef', name: 'TEF' },
            { slug: 'delf-dalf', name: 'DELF/DALF', levels: ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'] },
        ],
    },
    {
        slug: 'german',
        name: 'Allemand',
        native_name: 'Deutsch',
        flag: '🇩🇪',
        exams: [
            { slug: 'goethe', name: 'Goethe-Zertifikat', levels: ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'] },
            { slug: 'testdaf', name: 'TestDaF' },
        ],
    },
];
