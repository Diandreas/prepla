import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useTokens } from './landing-theme';

const EXAM_CARDS = [
    {
        exam: 'IELTS Academic',
        lang: '🇬🇧',
        q: 'The writer implies that climate models are…',
        opts: ['Increasingly accurate', 'Fundamentally flawed', 'Too complex for policy use', 'Still being validated'],
        correct: 0,
    },
    {
        exam: 'DELF B2',
        lang: '🇫🇷',
        q: 'Selon le texte, la principale cause de ce phénomène est…',
        opts: ['La surpopulation urbaine', 'Le manque de financement', 'Les changements climatiques', 'L\'absence de régulation'],
        correct: 2,
    },
    {
        exam: 'Goethe-Zertifikat B2',
        lang: '🇩🇪',
        q: 'Welche Aussage ist laut dem Text richtig?',
        opts: ['Die Stadt plant neue Grünflächen', 'Der Bürgermeister tritt zurück', 'Das Budget wurde gekürzt', 'Die Initiative scheiterte'],
        correct: 0,
    },
];

function useCyclingIndex(length: number, interval = 3800) {
    const [i, setI] = useState(0);
    useEffect(() => {
        const t = setInterval(() => setI(p => (p + 1) % length), interval);
        return () => clearInterval(t);
    }, [length, interval]);
    return i;
}

function MockExerciseCard({ card, visible }: { card: typeof EXAM_CARDS[0]; visible: boolean }) {
    const T = useTokens();
    const { t } = useTranslation();
    const [selected, setSelected] = useState<number | null>(null);
    const [revealed, setRevealed] = useState(false);

    useEffect(() => {
        setSelected(null);
        setRevealed(false);
        const t1 = setTimeout(() => setSelected(card.correct), 1400);
        const t2 = setTimeout(() => setRevealed(true), 2200);
        return () => { clearTimeout(t1); clearTimeout(t2); };
    }, [card]);

    const isDark = T.theme === 'dark';

    return (
        <div className="relative w-full max-w-sm rounded-2xl p-5 shadow-2xl"
            style={{
                border: `1px solid ${isDark ? 'rgba(255,255,255,0.1)' : 'rgba(26,22,18,0.12)'}`,
                background: isDark
                    ? 'linear-gradient(135deg, rgba(255,255,255,0.06) 0%, rgba(255,255,255,0.02) 100%)'
                    : 'linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.88) 100%)',
                backdropFilter: 'blur(20px)',
                opacity: visible ? 1 : 0,
                transform: visible ? 'translateY(0) rotate(1.5deg)' : 'translateY(20px) rotate(1.5deg)',
                transition: 'opacity 0.6s ease, transform 0.6s ease',
                boxShadow: isDark ? '0 20px 60px rgba(0,0,0,0.4)' : '0 20px 60px rgba(26,22,18,0.12)',
            }}>
            <div className="mb-4 flex items-center gap-2">
                <span className="text-lg">{card.lang}</span>
                <span className="text-xs font-semibold tracking-wide" style={{ color: T.gold }}>{card.exam}</span>
            </div>
            <p className="mb-4 text-sm leading-snug" style={{ color: T.text }}>{card.q}</p>
            <div className="space-y-2">
                {card.opts.map((opt, i) => {
                    const isSelected = selected === i;
                    const isCorrect = i === card.correct;
                    return (
                        <div key={i} className="flex items-center gap-2.5 rounded-lg border px-3 py-2 text-xs transition-all duration-300"
                            style={{
                                borderColor: revealed && isCorrect ? '#22c55e55'
                                    : revealed && isSelected && !isCorrect ? '#ef444455'
                                    : isSelected ? `${T.gold}aa`
                                    : T.border,
                                background: revealed && isCorrect ? 'rgba(34,197,94,0.1)'
                                    : revealed && isSelected && !isCorrect ? 'rgba(239,68,68,0.1)'
                                    : isSelected ? `${T.gold}14`
                                    : 'transparent',
                                color: T.textMid,
                            }}>
                            <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold"
                                style={{
                                    background: revealed && isCorrect ? '#22c55e'
                                        : revealed && isSelected && !isCorrect ? '#ef4444'
                                        : isSelected ? T.gold
                                        : isDark ? 'rgba(255,255,255,0.08)' : 'rgba(26,22,18,0.08)',
                                    color: (revealed && (isCorrect || (isSelected && !isCorrect))) || isSelected ? 'white' : T.textDim,
                                }}>
                                {String.fromCharCode(65 + i)}
                            </span>
                            {opt}
                            {revealed && isCorrect && (
                                <svg className="ml-auto h-3.5 w-3.5 shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd"/>
                                </svg>
                            )}
                        </div>
                    );
                })}
            </div>
            <div className="mt-3 rounded-lg px-3 py-2 text-center text-xs font-semibold transition-all duration-500"
                style={{
                    background: revealed ? 'rgba(34,197,94,0.12)' : `${T.gold}0d`,
                    color: revealed ? '#22c55e' : `${T.gold}99`,
                    opacity: selected !== null ? 1 : 0,
                }}>
                {revealed ? t('landing.hero_correct') : t('landing.hero_checking')}
            </div>
            <div className="pointer-events-none absolute -right-6 -top-6 h-20 w-20 rounded-full blur-2xl"
                style={{ background: `${T.gold}18` }} />
        </div>
    );
}

export function HeroSection() {
    const T = useTokens();
    const { t, i18n } = useTranslation();
    const [mounted, setMounted] = useState(false);
    const langIndex = useCyclingIndex(3, 2200);
    const cardIndex = useCyclingIndex(EXAM_CARDS.length, 5000);
    const [cardVisible, setCardVisible] = useState(true);

    // Language names cycle based on current i18n locale
    const LANGUAGES_BY_LOCALE: Record<string, string[]> = {
        fr: ['Anglais', 'Français', 'Allemand'],
        en: ['English', 'French', 'German'],
        de: ['Englisch', 'Französisch', 'Deutsch'],
    };
    const languages = LANGUAGES_BY_LOCALE[i18n.language] ?? LANGUAGES_BY_LOCALE.en;

    useEffect(() => { setMounted(true); }, []);
    useEffect(() => {
        setCardVisible(false);
        const t = setTimeout(() => setCardVisible(true), 300);
        return () => clearTimeout(t);
    }, [cardIndex]);

    return (
        <section style={{ background: T.bg, position: 'relative', overflow: 'hidden' }} className="min-h-screen"
            key={T.theme}>
            {/* Grid */}
            <svg aria-hidden className="pointer-events-none absolute inset-0 h-full w-full"
                style={{ opacity: T.gridOpacity }}>
                <defs>
                    <pattern id="hero-grid" width="60" height="60" patternUnits="userSpaceOnUse">
                        <path d="M 60 0 L 0 0 0 60" fill="none" stroke={T.gridColor} strokeWidth="0.5"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#hero-grid)" />
            </svg>

            {/* Glows */}
            <div className="pointer-events-none absolute inset-0">
                <div className="absolute -top-40 left-1/4 h-96 w-96 rounded-full blur-3xl" style={{ background: T.glowSky }} />
                <div className="absolute bottom-0 right-1/4 h-80 w-80 rounded-full blur-3xl" style={{ background: T.glowGold }} />
            </div>

            {/* Decorative logo watermark */}
            <img src="/icons/logo.png?v=3" alt="" aria-hidden
                className="pointer-events-none absolute -left-16 top-10 select-none"
                style={{ width: 380, height: 'auto', opacity: T.dark ? 0.06 : 0.05 }} />

            <div className="relative mx-auto max-w-7xl px-6 py-20 sm:py-28 lg:px-12">
                <div className="grid items-center gap-16 lg:grid-cols-2 lg:gap-20">
                    {/* LEFT */}
                    <div>
                        {/* Badge */}
                        <div className="mb-8 inline-flex items-center gap-2.5 rounded-full border px-4 py-1.5 text-xs font-semibold tracking-widest uppercase"
                            style={{
                                borderColor: `${T.sky}30`, background: `${T.sky}0d`, color: T.sky,
                                opacity: mounted ? 1 : 0,
                                transform: mounted ? 'translateY(0)' : 'translateY(12px)',
                                transition: 'all 0.6s ease 0.1s',
                            }}>
                            <span className="h-1.5 w-1.5 rounded-full animate-pulse" style={{ background: T.gold }} />
                            {t('landing.hero_badge')}
                        </div>

                        {/* Headline */}
                        <h1 style={{
                            fontFamily: '"Plus Jakarta Sans", system-ui, sans-serif',
                            fontSize: 'clamp(2.4rem, 5vw, 4.2rem)',
                            fontWeight: 800, lineHeight: 1.08, letterSpacing: '-0.02em', color: T.text,
                            opacity: mounted ? 1 : 0,
                            transform: mounted ? 'translateY(0)' : 'translateY(20px)',
                            transition: 'all 0.7s ease 0.2s',
                        }}>
                            {t('landing.hero_headline').split(' ').slice(0, -2).join(' ')}{' '}
                            <span style={{ color: T.sky }}>
                                {t('landing.hero_headline').split(' ').slice(-2).join(' ')}
                            </span>
                        </h1>

                        {/* Language ticker */}
                        <div className="mt-4 flex items-baseline gap-3"
                            style={{ opacity: mounted ? 1 : 0, transition: 'opacity 0.6s ease 0.4s' }}>
                            <span className="text-sm" style={{ color: T.textMid }}>{t('landing.hero_prepare_in')}</span>
                            <span key={langIndex} className="text-base font-semibold"
                                style={{ color: T.text, fontFamily: '"Plus Jakarta Sans", sans-serif', animation: 'langTick 0.4s cubic-bezier(0.22,1,0.36,1) both' }}>
                                {languages[langIndex]}
                            </span>
                        </div>

                        {/* Body */}
                        <p className="mt-6 text-base leading-relaxed"
                            style={{
                                fontFamily: '"Plus Jakarta Sans", sans-serif', color: T.textMid,
                                maxWidth: '32rem',
                                opacity: mounted ? 1 : 0,
                                transform: mounted ? 'translateY(0)' : 'translateY(16px)',
                                transition: 'all 0.7s ease 0.35s',
                            }}>
                            {t('landing.hero_body')}
                        </p>

                        {/* CTAs */}
                        <div className="mt-10 flex flex-wrap items-center gap-4"
                            style={{ opacity: mounted ? 1 : 0, transform: mounted ? 'translateY(0)' : 'translateY(16px)', transition: 'all 0.7s ease 0.5s' }}>
                            <Link href="/register"
                                className="group inline-flex items-center gap-2.5 rounded-full px-7 py-3.5 text-sm font-semibold transition-all duration-300 hover:-translate-y-0.5"
                                style={{ background: `linear-gradient(135deg, ${T.sky} 0%, #3478c8 100%)`, color: '#fff', boxShadow: `0 4px 20px ${T.sky}44` }}>
                                {t('landing.hero_cta_primary')}
                                <svg className="h-4 w-4 transition-transform duration-200 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </Link>
                            <a href="#pricing"
                                className="inline-flex items-center gap-2 rounded-full border px-7 py-3.5 text-sm font-medium transition-all duration-200 hover:-translate-y-0.5"
                                style={{ borderColor: T.border, color: T.textMid }}
                                onMouseEnter={(e) => { (e.currentTarget as HTMLElement).style.borderColor = `${T.sky}55`; (e.currentTarget as HTMLElement).style.color = T.text; }}
                                onMouseLeave={(e) => { (e.currentTarget as HTMLElement).style.borderColor = T.border; (e.currentTarget as HTMLElement).style.color = T.textMid; }}>
                                {t('landing.hero_cta_secondary')}
                            </a>
                        </div>

                        <p className="mt-4 text-xs" style={{ color: T.textDim, opacity: mounted ? 1 : 0, transition: 'opacity 0.6s ease 0.7s' }}>
                            {t('landing.hero_disclaimer')}
                        </p>

                        {/* Stats */}
                        <div className="mt-12 flex gap-8 border-t pt-8"
                            style={{ borderColor: T.border, opacity: mounted ? 1 : 0, transition: 'opacity 0.6s ease 0.8s' }}>
                            {[
                                { value: '3', label: t('landing.hero_stat_languages') },
                                { value: '8', label: t('landing.hero_stat_exams') },
                                { value: '∞', label: t('landing.hero_stat_exercises') },
                            ].map((s) => (
                                <div key={s.label}>
                                    <div className="text-3xl font-black"
                                        style={{ fontFamily: '"Cormorant Garamond", Georgia, serif', color: T.gold }}>
                                        {s.value}
                                    </div>
                                    <div className="mt-0.5 text-xs" style={{ color: T.textMid }}>{s.label}</div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* RIGHT — Mock card */}
                    <div className="flex flex-col items-center justify-center gap-6 lg:items-end"
                        style={{ opacity: mounted ? 1 : 0, transform: mounted ? 'translateY(0)' : 'translateY(30px)', transition: 'all 0.8s ease 0.4s' }}>
                        <div className="flex items-center gap-2 text-xs font-medium tracking-wide" style={{ color: `${T.gold}88` }}>
                            <span className="h-px w-8 bg-current" />
                            {t('landing.hero_live_label')}
                            <span className="h-1.5 w-1.5 rounded-full bg-green-400 animate-pulse" />
                        </div>

                        <MockExerciseCard card={EXAM_CARDS[cardIndex]} visible={cardVisible} />

                        <div className="flex gap-1.5">
                            {EXAM_CARDS.map((_, i) => (
                                <div key={i} className="rounded-full transition-all duration-300"
                                    style={{ width: i === cardIndex ? '16px' : '6px', height: '6px', background: i === cardIndex ? T.gold : `${T.gold}30` }} />
                            ))}
                        </div>

                        {/* Level widget */}
                        <div className="w-full max-w-sm rounded-xl border p-4"
                            style={{ borderColor: T.border, background: T.bgCard }}>
                            <p className="mb-3 text-xs font-semibold uppercase tracking-widest" style={{ color: T.textDim }}>
                                {t('landing.hero_level_title')}
                            </p>
                            <div className="space-y-2">
                                {[
                                    { skillKey: 'landing.hero_skill_reading', pct: 78, color: T.sky },
                                    { skillKey: 'landing.hero_skill_grammar', pct: 61, color: T.gold },
                                    { skillKey: 'landing.hero_skill_vocab', pct: 85, color: '#22c55e' },
                                ].map((item) => (
                                    <div key={item.skillKey} className="flex items-center gap-3">
                                        <span className="w-20 text-xs" style={{ color: T.textMid }}>{t(item.skillKey)}</span>
                                        <div className="flex-1 overflow-hidden rounded-full" style={{ height: '4px', background: T.border }}>
                                            <div className="h-full rounded-full"
                                                style={{ width: mounted ? `${item.pct}%` : '0%', background: item.color, transition: 'width 1.2s cubic-bezier(0.4,0,0.2,1) 0.9s' }} />
                                        </div>
                                        <span className="w-8 text-right text-xs font-semibold" style={{ color: item.color }}>{item.pct}%</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Bottom diagonal cut */}
            <div className="absolute bottom-0 left-0 right-0 h-20"
                style={{ background: T.bg, clipPath: 'polygon(0 100%, 100% 100%, 100% 100%, 0 60%)' }} />

            <style>{`
                @keyframes langTick {
                    from { opacity: 0; transform: translateY(8px); }
                    to   { opacity: 1; transform: translateY(0); }
                }
            `}</style>
        </section>
    );
}
