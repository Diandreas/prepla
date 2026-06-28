import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import * as Flags from 'country-flag-icons/react/3x2';
import { useState } from 'react';
import type { ExamRecord, ExamSection } from '@/types';

function Icon({ name, size = 20, style, className }: { name: string; size?: number; style?: React.CSSProperties; className?: string }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} style={{ objectFit: 'contain', ...style }} className={className} />;
}

function flagEmojiToCode(flag: string): string {
    const points = [...flag].map(c => c.codePointAt(0)! - 0x1F1E6);
    if (points.length === 2 && points[0] >= 0 && points[0] <= 25) {
        return String.fromCharCode(65 + points[0], 65 + points[1]);
    }
    return '';
}

function FlagImg({ flag, size = 28 }: { flag: string; size?: number }) {
    const code = flagEmojiToCode(flag);
    const FlagComponent = code ? (Flags as Record<string, React.ComponentType<{ style?: React.CSSProperties }>>)[code] : null;
    if (FlagComponent) return <FlagComponent style={{ width: size, borderRadius: 3 }} />;
    return <span style={{ fontSize: '1.5rem' }}>{flag}</span>;
}

interface ExerciseTypeItem {
    id: number;
    name: string;
    skill_type: string;
    component_key: string;
}

interface Props {
    exam: ExamRecord;
    section: ExamSection;
    exerciseTypes: ExerciseTypeItem[];
}

const OXFORD = '#1A2B48';
const SKY = '#4A90E2';

const skillIcons: Record<string, string> = {
    reading: 'book',
    listening: 'headphones',
    writing: 'message-square',
    speaking: 'mic',
};

const skillThemes: Record<string, { bg: string; shadow: string }> = {
    reading: { bg: `linear-gradient(135deg, ${SKY}, #3478c8)`, shadow: '#2a6fc0' },
    listening: { bg: `linear-gradient(135deg, #48b77b, #3a9d68)`, shadow: '#2d7d52' },
    writing: { bg: `linear-gradient(135deg, ${OXFORD}, #2a3f6a)`, shadow: '#0e1a2e' },
    speaking: { bg: `linear-gradient(135deg, #F5A623, #e08c10)`, shadow: '#c07a0e' },
};

export default function SectionDrills({ exam, section, exerciseTypes = [] }: Props) {
    const [launching, setLaunching] = useState<number | null>(null);
    const theme = skillThemes[section.skill_type] ?? skillThemes.reading;

    return (
        <AppLayout>
            <Head title={`${section.name} - ${exam.name}`} />
            <div className="mx-auto max-w-2xl px-4 py-8">
                {/* Header */}
                <div className="mb-6">
                    <div className="flex items-center gap-2">
                        {exam.language?.flag && <FlagImg flag={exam.language.flag} size={28} />}
                        <h1 className="text-2xl font-black tracking-tight" style={{ color: OXFORD }}>
                            {section.name}
                        </h1>
                    </div>
                    <p className="mt-1 text-sm font-bold text-muted-foreground">
                        {exam.name} · choisis un type d'exercice — il sera adapté à ton niveau
                    </p>
                </div>

                {exerciseTypes.length === 0 ? (
                    <div className="duo-card flex flex-col items-center p-10 text-center">
                        <Icon name="sparkles" size={40} style={{ color: SKY }} className="mb-3" />
                        <p className="text-lg font-black" style={{ color: OXFORD }}>
                            Aucun type disponible pour cette compétence
                        </p>
                    </div>
                ) : (
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        {exerciseTypes.map((type) => {
                            const iconName = skillIcons[type.skill_type] ?? 'book';
                            const t = skillThemes[type.skill_type] ?? theme;
                            return (
                                <Link
                                    key={type.id}
                                    href={route('practice.drill.type', [exam.id, type.id])}
                                    onClick={() => setLaunching(type.id)}
                                    className="duo-card flex flex-col items-center gap-2 p-4 text-center"
                                >
                                    <div
                                        className="flex h-10 w-10 items-center justify-center rounded-xl"
                                        style={{ background: t.bg }}
                                    >
                                        <Icon name={iconName} size={20} style={{ filter: 'brightness(0) invert(1)' }} />
                                    </div>
                                    <p className="text-xs font-bold leading-tight" style={{ color: OXFORD }}>
                                        {type.name}
                                    </p>
                                    {launching === type.id && (
                                        <span className="text-[10px] font-bold text-muted-foreground">Chargement…</span>
                                    )}
                                </Link>
                            );
                        })}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
