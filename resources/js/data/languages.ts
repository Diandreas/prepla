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
        slug: 'spanish',
        name: 'Español',
        native_name: 'Español',
        flag: '🇪🇸',
        exams: [
            { slug: 'dele', name: 'DELE', levels: ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'] },
            { slug: 'siele', name: 'SIELE' },
        ],
    },
    {
        slug: 'chinese',
        name: 'Chinois Mandarin',
        native_name: '中文',
        flag: '🇨🇳',
        exams: [
            { slug: 'hsk', name: 'HSK', levels: ['HSK 1', 'HSK 2', 'HSK 3', 'HSK 4', 'HSK 5', 'HSK 6'] },
        ],
    },
    {
        slug: 'arabic',
        name: 'Arabe',
        native_name: 'العربية',
        flag: '🇸🇦',
        exams: [
            { slug: 'alpt', name: 'ALPT' },
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
    {
        slug: 'portuguese',
        name: 'Portugais',
        native_name: 'Português',
        flag: '🇧🇷',
        exams: [
            { slug: 'celpe-bras', name: 'CELPE-Bras' },
        ],
    },
    {
        slug: 'japanese',
        name: 'Japonais',
        native_name: '日本語',
        flag: '🇯🇵',
        exams: [
            { slug: 'jlpt', name: 'JLPT', levels: ['N5', 'N4', 'N3', 'N2', 'N1'] },
        ],
    },
    {
        slug: 'korean',
        name: 'Coréen',
        native_name: '한국어',
        flag: '🇰🇷',
        exams: [
            { slug: 'topik', name: 'TOPIK', levels: ['TOPIK I', 'TOPIK II'] },
        ],
    },
    {
        slug: 'russian',
        name: 'Russe',
        native_name: 'Русский',
        flag: '🇷🇺',
        exams: [
            { slug: 'torfl', name: 'TORFL', levels: ['TEL', 'TBL', 'TRKI-1', 'TRKI-2', 'TRKI-3', 'TRKI-4'] },
        ],
    },
];
