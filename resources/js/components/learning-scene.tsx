import { cn } from '@/lib/utils';

export type LearningSceneVariant = 'lesson' | 'practice' | 'reading' | 'listening' | 'writing' | 'speaking' | 'vocabulary';

const scenes: Record<LearningSceneVariant, { icon: string; accent: string; soft: string; label: string; tip: string }> = {
    lesson: {
        icon: 'lightbulb',
        accent: '#4A90E2',
        soft: '#EAF4FF',
        label: 'Capsule apprentissage',
        tip: 'Avance section par section : une idée claire vaut mieux que dix idées survolées.',
    },
    practice: {
        icon: 'rocket',
        accent: '#6366F1',
        soft: '#EEF2FF',
        label: 'Entraînement actif',
        tip: 'Choisis un objectif court, réponds sans te presser et apprends de chaque correction.',
    },
    reading: {
        icon: 'book',
        accent: '#4A90E2',
        soft: '#EAF4FF',
        label: 'Lecture stratégique',
        tip: 'Repère d’abord les mots-clés, puis retourne dans le texte pour confirmer ta réponse.',
    },
    listening: {
        icon: 'headphones',
        accent: '#8B5CF6',
        soft: '#F3E8FF',
        label: 'Écoute active',
        tip: 'Anticipe le type d’information attendu avant de lancer l’enregistrement.',
    },
    writing: {
        icon: 'writing',
        accent: '#F59E0B',
        soft: '#FFF7E6',
        label: 'Atelier rédaction',
        tip: 'Structure ton idée, donne un exemple précis, puis relis la clarté de chaque phrase.',
    },
    speaking: {
        icon: 'mic',
        accent: '#10B981',
        soft: '#E9FBF3',
        label: 'Studio oral',
        tip: 'Parle naturellement : une réponse claire et développée compte plus qu’un accent parfait.',
    },
    vocabulary: {
        icon: 'vocabulary',
        accent: '#EC4899',
        soft: '#FDECF5',
        label: 'Mémoire vocabulaire',
        tip: 'Associe chaque mot à une image ou une situation pour le retenir durablement.',
    },
};

interface LearningSceneProps {
    variant?: LearningSceneVariant;
    title?: string;
    subtitle?: string;
    compact?: boolean;
    className?: string;
}

export function LearningScene({ variant = 'lesson', title, subtitle, compact = false, className }: LearningSceneProps) {
    const scene = scenes[variant];

    return (
        <section
            className={cn('learning-scene', compact && 'learning-scene--compact', className)}
            style={
                {
                    '--scene-accent': scene.accent,
                    '--scene-soft': scene.soft,
                } as React.CSSProperties
            }
        >
            <div className="learning-scene__copy">
                <span className="learning-scene__eyebrow">
                    <span className="learning-scene__pulse" />
                    {scene.label}
                </span>
                {title && <h2>{title}</h2>}
                <p>{subtitle || scene.tip}</p>
            </div>

            <div className="learning-scene__art" aria-hidden="true">
                <span className="learning-scene__orbit learning-scene__orbit--one" />
                <span className="learning-scene__orbit learning-scene__orbit--two" />
                <span className="learning-scene__spark learning-scene__spark--one">✦</span>
                <span className="learning-scene__spark learning-scene__spark--two">✦</span>
                <span className="learning-scene__bubble learning-scene__bubble--one" />
                <span className="learning-scene__bubble learning-scene__bubble--two" />
                <div className="learning-scene__icon-shell">
                    <img src={`/icons/${scene.icon}.png`} alt="" />
                </div>
            </div>
        </section>
    );
}

export function sceneVariantForSkill(skill?: string | null, componentKey?: string | null): LearningSceneVariant {
    const value = `${skill ?? ''} ${componentKey ?? ''}`.toLowerCase();
    if (value.includes('listen') || value.includes('dictation')) return 'listening';
    if (value.includes('speak') || value.includes('role-play') || value.includes('repeat')) return 'speaking';
    if (value.includes('writ') || value.includes('essay') || value.includes('discussion') || value.includes('synthesis')) return 'writing';
    if (value.includes('vocab') || value.includes('word-formation') || value.includes('cloze')) return 'vocabulary';
    if (value.includes('read') || value.includes('matching') || value.includes('text')) return 'reading';
    return 'practice';
}
