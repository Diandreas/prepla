// Front mirror of app/Services/Content/ExerciseSchemaRegistry.php.
// Drives the dynamic builder form: given a component_key we know its correction
// family and therefore which fields the teacher must fill. Adding a custom type
// = adding an entry here + in the PHP registry.

export type Family = 'exact-match' | 'multi-field' | 'order' | 'ai-writing' | 'ai-speaking';

export interface TypeSchema {
    label: string;
    family: Family;
    media: ('image' | 'audio')[];
}

export const EXERCISE_SCHEMAS: Record<string, TypeSchema> = {
    // exact-match
    mcq: { label: 'QCM', family: 'exact-match', media: ['audio'] },
    'true-false-ng': { label: 'Vrai / Faux / Non mentionné', family: 'exact-match', media: ['audio'] },
    'gap-fill': { label: 'Texte à trous', family: 'exact-match', media: ['audio'] },
    matching: { label: 'Association', family: 'exact-match', media: ['audio'] },
    'sentence-completion': { label: 'Compléter la phrase', family: 'exact-match', media: ['audio'] },
    'short-answer': { label: 'Réponse courte', family: 'exact-match', media: ['audio'] },
    'word-formation': { label: 'Formation de mots', family: 'exact-match', media: [] },
    'key-word-transformation': { label: 'Transformation', family: 'exact-match', media: [] },
    'insert-text': { label: 'Insérer une phrase', family: 'exact-match', media: [] },
    dictation: { label: 'Dictée', family: 'exact-match', media: ['audio'] },
    // multi-field
    'open-cloze': { label: 'Texte à trous numérotés', family: 'multi-field', media: [] },
    'note-completion': { label: 'Compléter des notes', family: 'multi-field', media: ['audio'] },
    'form-completion': { label: 'Compléter un formulaire', family: 'multi-field', media: ['audio'] },
    'table-completion': { label: 'Compléter un tableau', family: 'multi-field', media: ['audio'] },
    'summary-completion': { label: 'Compléter un résumé', family: 'multi-field', media: [] },
    'flow-chart-completion': { label: 'Diagramme de flux', family: 'multi-field', media: [] },
    'multiple-matching': { label: 'Association multiple', family: 'multi-field', media: [] },
    'diagram-labeling': { label: 'Annoter un schéma', family: 'multi-field', media: ['image'] },
    // order
    ordering: { label: "Remettre dans l'ordre", family: 'order', media: [] },
    'gapped-text': { label: 'Texte lacunaire', family: 'order', media: [] },
    // ai-writing
    'short-writing': { label: 'Rédaction courte', family: 'ai-writing', media: ['image'] },
    'essay-editor': { label: 'Rédaction / essai', family: 'ai-writing', media: [] },
    synthesis: { label: 'Synthèse', family: 'ai-writing', media: [] },
    'academic-discussion': { label: 'Discussion académique', family: 'ai-writing', media: [] },
    'graph-description': { label: 'Décrire un graphique', family: 'ai-writing', media: ['image'] },
    'integrated-task': { label: 'Tâche intégrée', family: 'ai-writing', media: ['audio'] },
    // ai-speaking
    'speaking-recorder': { label: 'Expression orale', family: 'ai-speaking', media: ['image'] },
    'role-play': { label: 'Jeu de rôle', family: 'ai-speaking', media: [] },
};

export function familyOf(componentKey: string): Family | null {
    return EXERCISE_SCHEMAS[componentKey]?.family ?? null;
}

/** A fresh empty question for the given family. */
export function emptyQuestion(family: Family): Record<string, unknown> {
    switch (family) {
        case 'exact-match':
            return { id: '', text: '', options: ['', '', '', ''], correct_answer: '', explanation: '' };
        case 'multi-field':
            return { id: '', text: '', correct_answers: {} as Record<string, string> };
        case 'order':
            return { id: '', text: '', items: ['', ''], correct_order: [] };
        case 'ai-writing':
            return { id: '', text: '', min_words: 50, max_words: 150 };
        case 'ai-speaking':
            return { id: '', text: '', expected_points: [''], prep_time: 30, speak_time: 60 };
        default:
            return { id: '', text: '' };
    }
}
