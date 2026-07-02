import { router } from '@inertiajs/react';
import OnboardingLayout from '@/layouts/onboarding-layout';
import * as Flags from 'country-flag-icons/react/3x2';
import { useEffect, useState } from 'react';

interface Props {
    currentNativeLanguage: string | null;
}

const OXFORD = '#1A2B48';
const SKY = '#4A90E2';

// Restreint aux 3 langues actuellement gérées par la plateforme (anglais,
// français, allemand). Ajouter une entrée ici quand une nouvelle langue est
// réellement prise en charge.
const NATIVE_LANGUAGES = [
    { code: 'fr', name: 'Français',   nativeName: 'Français',   flag: 'FR', value: 'Français' },
    { code: 'en', name: 'English',    nativeName: 'English',    flag: 'GB', value: 'English' },
    { code: 'de', name: 'Allemand',   nativeName: 'Deutsch',    flag: 'DE', value: 'Allemand' },
];

function FlagComponent({ code }: { code: string }) {
    const Flag = (Flags as Record<string, React.ComponentType<{ style?: React.CSSProperties }>>)[code];
    if (!Flag) return null;
    return <Flag style={{ width: 28, borderRadius: 3, display: 'block' }} />;
}

export default function NativeLanguage({ currentNativeLanguage }: Props) {
    const [selected, setSelected] = useState<string | null>(currentNativeLanguage ?? null);
    const [submitting, setSubmitting] = useState(false);
    const [mounted, setMounted] = useState(false);

    useEffect(() => setMounted(true), []);

    function handleSubmit() {
        if (!selected || submitting) return;
        setSubmitting(true);
        router.post(route('onboarding.native-language.store'), { native_language: selected });
    }

    return (
        <OnboardingLayout title="Ta langue maternelle" step={1}>
            <div className="space-y-8">
                {/* Header */}
                <div className="text-center">
                    <h1 className="text-2xl font-black tracking-tight sm:text-3xl" style={{ color: OXFORD }}>
                        Quelle est ta langue maternelle ?
                    </h1>
                    <p className="mt-2 text-sm font-medium text-muted-foreground">
                        Cela nous aide à personnaliser ton test de niveau et ton parcours
                    </p>
                </div>

                {/* Language grid */}
                <div className="grid grid-cols-3 gap-3 sm:grid-cols-4">
                    {NATIVE_LANGUAGES.map((lang, i) => {
                        const isSelected = selected === lang.value;
                        return (
                            <button
                                key={lang.code}
                                onClick={() => setSelected(lang.value)}
                                className="flex flex-col items-center gap-2 rounded-2xl border-2 p-3 transition-all duration-150"
                                style={{
                                    borderColor: isSelected ? SKY : '#e5e7eb',
                                    background: isSelected ? `${SKY}12` : '#fff',
                                    boxShadow: isSelected
                                        ? `0 0 0 3px ${SKY}30, 0 4px 0 0 #2a6fc0`
                                        : '0 3px 0 0 #d1d5db',
                                    opacity: mounted ? 1 : 0,
                                    transform: mounted ? 'translateY(0) scale(1)' : 'translateY(10px) scale(0.97)',
                                    transition: `all 0.35s ease ${i * 40}ms`,
                                }}
                            >
                                <FlagComponent code={lang.flag} />
                                <div className="text-center">
                                    <p
                                        className="text-xs font-black leading-tight"
                                        style={{ color: isSelected ? SKY : OXFORD }}
                                    >
                                        {lang.nativeName}
                                    </p>
                                    {lang.nativeName !== lang.name && (
                                        <p className="text-[10px] font-medium text-muted-foreground leading-tight">
                                            {lang.name}
                                        </p>
                                    )}
                                </div>
                                {isSelected && (
                                    <div
                                        className="flex h-5 w-5 items-center justify-center rounded-full"
                                        style={{ background: SKY }}
                                    >
                                        <img
                                            src="/icons/check.png"
                                            alt=""
                                            width={10}
                                            height={10}
                                            style={{ filter: 'brightness(0) invert(1)', objectFit: 'contain' }}
                                        />
                                    </div>
                                )}
                            </button>
                        );
                    })}
                </div>

                {/* CTA */}
                <div className="flex justify-end pt-2">
                    <button
                        onClick={handleSubmit}
                        disabled={!selected || submitting}
                        className="flex items-center gap-2 rounded-2xl px-7 py-3.5 text-sm font-black text-white transition-all disabled:opacity-40"
                        style={{
                            background: selected ? `linear-gradient(135deg, ${SKY}, #3478c8)` : '#94a3b8',
                            boxShadow: selected ? '0 4px 0 0 #2a6fc0' : '0 3px 0 0 #64748b',
                            transform: submitting ? 'translateY(2px)' : 'translateY(0)',
                        }}
                    >
                        {submitting ? (
                            <img src="/icons/loader.png" alt="" width={16} height={16} className="animate-spin" style={{ filter: 'brightness(0) invert(1)' }} />
                        ) : (
                            <>
                                Continuer
                                <img src="/icons/arrow-right.png" alt="" width={14} height={14} style={{ filter: 'brightness(0) invert(1)', objectFit: 'contain' }} />
                            </>
                        )}
                    </button>
                </div>
            </div>
        </OnboardingLayout>
    );
}
