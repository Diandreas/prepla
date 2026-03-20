import { Link } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const LANGUAGES = ['Allemand', 'Anglais', 'Japonais', 'Chinois', 'Espagnol', 'Coréen', 'Français', 'Russe', 'Arabe', 'Portugais'];
const EXAM_CARDS = [
    { exam: 'Goethe-Zertifikat B2', lang: '🇩🇪', q: 'Welche Aussage ist laut dem Text richtig?', opts: ['Die Stadt plant neue Grünflächen', 'Der Bürgermeister tritt zurück', 'Das Budget wurde gekürzt', 'Die Initiative scheiterte'], correct: 0 },
    { exam: 'IELTS Academic', lang: '🇬🇧', q: 'The writer implies that climate models are…', opts: ['Increasingly accurate', 'Fundamentally flawed', 'Too complex for policy use', 'Still being validated'], correct: 0 },
    { exam: 'JLPT N2', lang: '🇯🇵', q: '次の文の（　）に入る最も適切な言葉は何ですか？', opts: ['について', 'にとって', 'によって', 'において'], correct: 2 },
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
    const [selected, setSelected] = useState<number | null>(null);
    const [revealed, setRevealed] = useState(false);

    useEffect(() => {
        setSelected(null);
        setRevealed(false);
        const t1 = setTimeout(() => setSelected(card.correct), 1400);
        const t2 = setTimeout(() => setRevealed(true), 2200);
        return () => { clearTimeout(t1); clearTimeout(t2); };
    }, [card]);

    return (
        <div
            className="relative w-full max-w-sm rounded-2xl border border-white/10 p-5 shadow-2xl"
            style={{
                background: 'linear-gradient(135deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.02) 100%)',
                backdropFilter: 'blur(20px)',
                opacity: visible ? 1 : 0,
                transform: visible ? 'translateY(0) rotate(1.5deg)' : 'translateY(20px) rotate(1.5deg)',
                transition: 'opacity 0.6s ease, transform 0.6s ease',
            }}
        >
            {/* Card header */}
            <div className="mb-4 flex items-center gap-2">
                <span className="text-lg">{card.lang}</span>
                <span className="text-xs font-semibold tracking-wide" style={{ color: '#F5A623' }}>{card.exam}</span>
            </div>

            {/* Question */}
            <p className="mb-4 text-sm leading-snug text-white/90">{card.q}</p>

            {/* Options */}
            <div className="space-y-2">
                {card.opts.map((opt, i) => {
                    const isSelected = selected === i;
                    const isCorrect = i === card.correct;
                    const showResult = revealed;
                    return (
                        <div
                            key={i}
                            className="flex items-center gap-2.5 rounded-lg border px-3 py-2 text-xs transition-all duration-300"
                            style={{
                                borderColor: showResult && isCorrect ? '#22c55e55' : showResult && isSelected && !isCorrect ? '#ef444455' : isSelected ? '#F5A623aa' : 'rgba(255,255,255,0.1)',
                                background: showResult && isCorrect ? 'rgba(34,197,94,0.12)' : showResult && isSelected && !isCorrect ? 'rgba(239,68,68,0.12)' : isSelected ? 'rgba(245,166,35,0.12)' : 'transparent',
                                color: 'rgba(255,255,255,0.75)',
                            }}
                        >
                            <span
                                className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold"
                                style={{
                                    background: showResult && isCorrect ? '#22c55e' : showResult && isSelected && !isCorrect ? '#ef4444' : isSelected ? '#F5A623' : 'rgba(255,255,255,0.08)',
                                    color: (showResult && (isCorrect || (isSelected && !isCorrect))) || isSelected ? 'white' : 'rgba(255,255,255,0.5)',
                                }}
                            >
                                {String.fromCharCode(65 + i)}
                            </span>
                            {opt}
                            {showResult && isCorrect && <img src="/icons/check-circle.png" alt="" className="ml-auto h-3.5 w-3.5 shrink-0" style={{ filter: 'brightness(0) saturate(100%) invert(69%) sepia(56%) saturate(487%) hue-rotate(92deg) brightness(103%) contrast(93%)' }} />}
                        </div>
                    );
                })}
            </div>

            {/* Result pill */}
            <div
                className="mt-3 rounded-lg px-3 py-2 text-center text-xs font-semibold transition-all duration-500"
                style={{
                    background: revealed ? 'rgba(34,197,94,0.15)' : 'rgba(245,166,35,0.08)',
                    color: revealed ? '#4ade80' : 'rgba(245,166,35,0.6)',
                    opacity: selected !== null ? 1 : 0,
                }}
            >
                {revealed ? '✓ Bonne réponse ! +10 XP' : 'Vérification en cours…'}
            </div>

            {/* Corner glow */}
            <div className="pointer-events-none absolute -right-6 -top-6 h-20 w-20 rounded-full blur-2xl" style={{ background: 'rgba(245,166,35,0.15)' }} />
        </div>
    );
}

export function HeroSection() {
    const [mounted, setMounted] = useState(false);
    const langIndex = useCyclingIndex(LANGUAGES.length, 2200);
    const cardIndex = useCyclingIndex(EXAM_CARDS.length, 5000);
    const [cardVisible, setCardVisible] = useState(true);

    useEffect(() => { setMounted(true); }, []);

    // Fade out + in on card change
    useEffect(() => {
        setCardVisible(false);
        const t = setTimeout(() => setCardVisible(true), 300);
        return () => clearTimeout(t);
    }, [cardIndex]);

    const ink = '#1A2B48';       // Oxford Blue
    const sky = '#4A90E2';        // Sky Blue (CTAs)
    const gold = '#F5A623';       // Gold (achievements only)
    const cream = '#F4F7F6';      // Pearl Gray / light text
    const dimCream = 'rgba(244,247,246,0.6)';

    return (
        <section
            style={{ background: ink, position: 'relative', overflow: 'hidden' }}
            className="min-h-screen"
        >
            {/* Subtle grid background */}
            <svg
                aria-hidden
                className="pointer-events-none absolute inset-0 h-full w-full"
                style={{ opacity: 0.06 }}
                xmlns="http://www.w3.org/2000/svg"
            >
                <defs>
                    <pattern id="grid" width="60" height="60" patternUnits="userSpaceOnUse">
                        <path d="M 60 0 L 0 0 0 60" fill="none" stroke="#4A90E2" strokeWidth="0.5"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid)" />
            </svg>

            {/* Ambient glows */}
            <div className="pointer-events-none absolute inset-0">
                <div className="absolute -top-40 left-1/4 h-96 w-96 rounded-full blur-3xl" style={{ background: 'rgba(74,144,226,0.12)' }} />
                <div className="absolute bottom-0 right-1/4 h-80 w-80 rounded-full blur-3xl" style={{ background: 'rgba(245,166,35,0.08)' }} />
                <div className="absolute top-1/2 left-0 h-64 w-64 rounded-full blur-3xl" style={{ background: 'rgba(74,144,226,0.06)' }} />
            </div>

            {/* Large decorative letter */}
            <div
                className="pointer-events-none absolute -left-8 top-16 select-none text-[20rem] font-black leading-none"
                style={{
                    fontFamily: '"Cormorant Garamond", Georgia, serif',
                    color: 'rgba(245,166,35,0.025)',
                    lineHeight: 1,
                }}
            >
                P
            </div>

            <div className="relative mx-auto max-w-7xl px-6 py-20 sm:py-28 lg:px-12">
                <div className="grid items-center gap-16 lg:grid-cols-2 lg:gap-20">

                    {/* LEFT — Typography column */}
                    <div>
                        {/* Overline badge */}
                        <div
                            className="mb-8 inline-flex items-center gap-2.5 rounded-full border px-4 py-1.5 text-xs font-semibold tracking-widest uppercase"
                            style={{
                                borderColor: 'rgba(245,166,35,0.3)',
                                background: 'rgba(245,166,35,0.07)',
                                color: gold,
                                opacity: mounted ? 1 : 0,
                                transform: mounted ? 'translateY(0)' : 'translateY(12px)',
                                transition: 'all 0.6s ease 0.1s',
                            }}
                        >
                            <span className="h-1.5 w-1.5 rounded-full animate-pulse" style={{ background: gold }} />
                            Alimenté par Mistral IA · 10 langues
                        </div>

                        {/* Main headline */}
                        <h1
                            style={{
                                fontFamily: '"Cormorant Garamond", Georgia, serif',
                                fontSize: 'clamp(2.6rem, 5vw, 4.5rem)',
                                fontWeight: 700,
                                lineHeight: 1.08,
                                color: cream,
                                opacity: mounted ? 1 : 0,
                                transform: mounted ? 'translateY(0)' : 'translateY(20px)',
                                transition: 'all 0.7s ease 0.2s',
                            }}
                        >
                            Maîtrisez votre{' '}
                            <span style={{ fontStyle: 'italic', color: gold }}>examen</span>
                            <br />
                            de langue.
                        </h1>

                        {/* Animated language ticker */}
                        <div
                            className="mt-4 flex items-baseline gap-3"
                            style={{
                                opacity: mounted ? 1 : 0,
                                transition: 'opacity 0.6s ease 0.4s',
                            }}
                        >
                            <span className="text-sm" style={{ color: dimCream }}>Préparez-vous en</span>
                            <span
                                key={langIndex}
                                className="text-base font-semibold"
                                style={{
                                    color: cream,
                                    fontFamily: '"Plus Jakarta Sans", sans-serif',
                                    animation: 'langTick 0.4s cubic-bezier(0.22,1,0.36,1) both',
                                }}
                            >
                                {LANGUAGES[langIndex]}
                            </span>
                        </div>

                        {/* Body */}
                        <p
                            className="mt-6 text-base leading-relaxed"
                            style={{
                                fontFamily: '"Plus Jakarta Sans", sans-serif',
                                color: dimCream,
                                maxWidth: '32rem',
                                opacity: mounted ? 1 : 0,
                                transform: mounted ? 'translateY(0)' : 'translateY(16px)',
                                transition: 'all 0.7s ease 0.35s',
                            }}
                        >
                            IELTS, TOEFL, DELF, HSK, Goethe, JLPT… Exercices générés par IA,
                            corrections instantanées, parcours entièrement personnalisé selon votre niveau CECR.
                        </p>

                        {/* CTAs */}
                        <div
                            className="mt-10 flex flex-wrap items-center gap-4"
                            style={{
                                opacity: mounted ? 1 : 0,
                                transform: mounted ? 'translateY(0)' : 'translateY(16px)',
                                transition: 'all 0.7s ease 0.5s',
                            }}
                        >
                            <Link
                                href="/register"
                                className="group inline-flex items-center gap-2.5 rounded-full px-7 py-3.5 text-sm font-semibold transition-all duration-300 hover:-translate-y-0.5"
                                style={{
                                    background: `linear-gradient(135deg, ${sky} 0%, #3478c8 100%)`,
                                    color: '#ffffff',
                                    boxShadow: `0 0 0 0 rgba(74,144,226,0.4)`,
                                }}
                                onMouseEnter={(e) => (e.currentTarget.style.boxShadow = '0 8px 32px rgba(74,144,226,0.45)')}
                                onMouseLeave={(e) => (e.currentTarget.style.boxShadow = '0 0 0 0 rgba(74,144,226,0.4)')}
                            >
                                Commencer gratuitement
                                <img src="/icons/arrow-right.png" alt="" className="h-4 w-4 transition-transform duration-200 group-hover:translate-x-1" style={{ filter: 'brightness(0) saturate(100%) invert(100%)' }} />
                            </Link>
                            <a
                                href="#pricing"
                                className="inline-flex items-center gap-2 rounded-full border px-7 py-3.5 text-sm font-medium transition-all duration-200 hover:-translate-y-0.5"
                                style={{ borderColor: 'rgba(244,247,246,0.2)', color: dimCream }}
                                onMouseEnter={(e) => { (e.currentTarget as HTMLElement).style.borderColor = `rgba(74,144,226,0.5)`; (e.currentTarget as HTMLElement).style.color = cream; }}
                                onMouseLeave={(e) => { (e.currentTarget as HTMLElement).style.borderColor = 'rgba(244,247,246,0.2)'; (e.currentTarget as HTMLElement).style.color = dimCream; }}
                            >
                                Voir les tarifs
                            </a>
                        </div>

                        <p
                            className="mt-4 text-xs"
                            style={{
                                color: 'rgba(240,234,216,0.28)',
                                opacity: mounted ? 1 : 0,
                                transition: 'opacity 0.6s ease 0.7s',
                            }}
                        >
                            Gratuit pour commencer · Aucune carte de crédit requise
                        </p>

                        {/* Stats row */}
                        <div
                            className="mt-12 flex gap-8 border-t pt-8"
                            style={{
                                borderColor: 'rgba(240,234,216,0.08)',
                                opacity: mounted ? 1 : 0,
                                transition: 'opacity 0.6s ease 0.8s',
                            }}
                        >
                            {[
                                { value: '10', label: 'Langues' },
                                { value: '20+', label: 'Examens officiels' },
                                { value: '∞', label: 'Exercices IA' },
                            ].map((s) => (
                                <div key={s.label}>
                                    <div
                                        className="text-3xl font-black"
                                        style={{ fontFamily: '"Cormorant Garamond", Georgia, serif', color: gold }}
                                    >
                                        {s.value}
                                    </div>
                                    <div className="mt-0.5 text-xs" style={{ color: dimCream }}>{s.label}</div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* RIGHT — Mock exercise card */}
                    <div
                        className="flex flex-col items-center justify-center gap-6 lg:items-end"
                        style={{
                            opacity: mounted ? 1 : 0,
                            transform: mounted ? 'translateY(0)' : 'translateY(30px)',
                            transition: 'all 0.8s ease 0.4s',
                        }}
                    >
                        {/* Label above card */}
                        <div
                            className="flex items-center gap-2 text-xs font-medium tracking-wide"
                            style={{ color: 'rgba(245,166,35,0.6)' }}
                        >
                            <span className="h-px w-8 bg-current" />
                            EXERCICE EN DIRECT
                            <span className="h-1.5 w-1.5 rounded-full bg-green-400 animate-pulse" />
                        </div>

                        <MockExerciseCard card={EXAM_CARDS[cardIndex]} visible={cardVisible} />

                        {/* Navigation dots */}
                        <div className="flex gap-1.5">
                            {EXAM_CARDS.map((_, i) => (
                                <div
                                    key={i}
                                    className="rounded-full transition-all duration-300"
                                    style={{
                                        width: i === cardIndex ? '16px' : '6px',
                                        height: '6px',
                                        background: i === cardIndex ? gold : 'rgba(245,166,35,0.25)',
                                    }}
                                />
                            ))}
                        </div>

                        {/* Confidence widget */}
                        <div
                            className="w-full max-w-sm rounded-xl border p-4"
                            style={{
                                borderColor: 'rgba(255,255,255,0.07)',
                                background: 'rgba(255,255,255,0.02)',
                            }}
                        >
                            <p className="mb-3 text-xs font-semibold uppercase tracking-widest" style={{ color: 'rgba(240,234,216,0.35)' }}>
                                Votre niveau estimé
                            </p>
                            <div className="space-y-2">
                                {[
                                    { skill: 'Lecture', pct: 78, color: '#3b82f6' },
                                    { skill: 'Grammaire', pct: 61, color: gold },
                                    { skill: 'Vocabulaire', pct: 85, color: '#22c55e' },
                                ].map((item) => (
                                    <div key={item.skill} className="flex items-center gap-3">
                                        <span className="w-20 text-xs" style={{ color: dimCream }}>{item.skill}</span>
                                        <div className="flex-1 overflow-hidden rounded-full" style={{ height: '4px', background: 'rgba(255,255,255,0.07)' }}>
                                            <div
                                                className="h-full rounded-full"
                                                style={{
                                                    width: mounted ? `${item.pct}%` : '0%',
                                                    background: item.color,
                                                    transition: `width 1.2s cubic-bezier(0.4,0,0.2,1) ${0.9 + EXAM_CARDS.indexOf(EXAM_CARDS[cardIndex]) * 0.1}s`,
                                                }}
                                            />
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
            <div
                className="absolute bottom-0 left-0 right-0 h-20"
                style={{
                    background: 'var(--background)',
                    clipPath: 'polygon(0 100%, 100% 100%, 100% 100%, 0 60%)',
                }}
            />

            <style>{`
                @keyframes langTick {
                    from { opacity: 0; transform: translateY(8px); }
                    to   { opacity: 1; transform: translateY(0); }
                }
            `}</style>
        </section>
    );
}
