import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
function Icon({ name, size = 20, style }: { name: string; size?: number; style?: React.CSSProperties }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} style={{ objectFit: 'contain', ...style }} />;
}
import { useEffect, useState } from 'react';

const OXFORD = '#1A2B48';
const SKY = '#4A90E2';
const GOLD = '#F5A623';

// Custom icon component using icons from /public/icons
function CustomIcon({ name, className, style }: { name: string; className?: string; style?: React.CSSProperties }) {
    return (
        <img
            src={`/icons/${name}.png`}
            alt={name}
            className={className || 'h-5 w-5'}
            style={{ objectFit: 'contain', ...style }}
        />
    );
}

// Outils IA volontairement réduits à 2 : le Générateur d'exercices et les
// Recommandations faisaient doublon avec le parcours et le dashboard. On garde
// les deux outils réellement uniques.
const tools = [
    {
        title: 'Correcteur de rédaction',
        description: 'Soumettez vos essais et obtenez des corrections détaillées avec scores',
        icon: 'writing',
        href: '/ai-tools/writing-corrector',
        bg: `linear-gradient(135deg, ${OXFORD}, #2a3f6a)`,
        shadow: '#0e1a2e',
    },
    {
        title: 'Explicateur IA',
        description: 'Posez des questions sur la grammaire, le vocabulaire ou les stratégies',
        icon: 'help',
        href: '/ai-tools/explainer',
        bg: `linear-gradient(135deg, #48b77b, #3a9d68)`,
        shadow: '#2d7d52',
    },
];

export default function AiToolsIndex() {
    const [mounted, setMounted] = useState(false);
    useEffect(() => setMounted(true), []);

    return (
        <AppLayout>
            <Head title="Outils IA" />
            <div className="mx-auto max-w-2xl px-4 py-8">
                {/* Header */}
                <div className="mb-8">
                    <h1 className="text-2xl font-black tracking-tight" style={{ color: OXFORD }}>
                        Outils IA
                    </h1>
                    <p className="mt-1 text-sm font-bold text-muted-foreground">
                        Boostez votre préparation avec des outils alimentés par l'IA
                    </p>
                </div>

                {/* Tool Cards */}
                <div className="grid gap-4 sm:grid-cols-2">
                    {tools.map((tool, i) => (
                        <Link
                            key={tool.href}
                            href={tool.href}
                            className="duo-card flex flex-col overflow-hidden p-0"
                            style={{
                                opacity: mounted ? 1 : 0,
                                transform: mounted ? 'translateY(0)' : 'translateY(16px)',
                                transition: `all 0.4s cubic-bezier(0.22, 1, 0.36, 1) ${i * 100}ms`,
                            }}
                        >
                            {/* Icon header */}
                            <div className="flex items-center gap-3 p-5 pb-3">
                                <div
                                    className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl"
                                    style={{
                                        background: tool.bg,
                                        boxShadow: `0 4px 0 0 ${tool.shadow}`,
                                    }}
                                >
                                    <CustomIcon name={tool.icon} className="h-6 w-6" style={{ filter: 'brightness(0) saturate(100%) invert(100%)' }} />
                                </div>
                                <h3 className="text-sm font-black" style={{ color: OXFORD }}>
                                    {tool.title}
                                </h3>
                            </div>
                            {/* Description */}
                            <p className="flex-1 px-5 text-xs font-medium text-muted-foreground leading-relaxed">
                                {tool.description}
                            </p>
                            {/* Footer */}
                            <div
                                className="mt-3 flex items-center justify-between border-t-2 border-gray-100 px-5 py-3"
                            >
                                <span
                                    className="text-[10px] font-black uppercase tracking-widest"
                                    style={{ color: SKY }}
                                >
                                    Commencer
                                </span>
                                <Icon name="chevron-right" size={14} style={{ filter: 'brightness(0) saturate(100%) invert(47%) sepia(85%) saturate(562%) hue-rotate(183deg) brightness(101%) contrast(96%)' }} />
                            </div>
                        </Link>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
