import React from 'react';
import { Img, interpolate, staticFile, useCurrentFrame } from 'remotion';
import { AppIcon, Phone } from '../../components/AppAssets';
import { Icon } from '../../components/Icons';
import { Move, SceneHeader } from '../../components/SceneKit';
import { Pointer } from '../../components/UI';
import { BEAT, C, EASE, FONT, SPRING, sp, tw } from '../../theme';

// Simulation allemande (42–46 s) : l'écran « mode examen » de l'app (practice/exam-simulator.tsx)
// recomposé avec le vrai sujet blanc Goethe-Zertifikat B1 (database/data/content/mock_exams/goethe/
// b1_sim1.json, Lesen Teil 1). On lit le texte, on répond, puis le chrono Lesen (65 min dans
// config/exams/goethe.php) sort du téléphone et défile au format de l'app (mm:ss).

const PHONE = { width: 620, left: 230, top: 772 };
const SCREEN_SCALE = (PHONE.width - PHONE.width * 0.064) / 1080;
const SCREEN_LEFT = PHONE.left + PHONE.width * 0.032;
const SCREEN_TOP = PHONE.top + PHONE.width * 0.032 + PHONE.width * 0.05;
const READ = 14;
const TAP = 34;
const POP = 52;
const LESEN_SECONDS = 65 * 60;

// Couleurs de l'écran d'examen de l'app.
const OXFORD = '#1A2B48';
const ACCENT = '#6366f1';
const PRIMARY = '#3B82E0';
const INK = '#16213a';
const MUTED = '#64748b';
const BORDER = '#e5e7eb';

const PASSAGE_TITLE = 'Mein nachhaltiger Alltag — Blog von Lena';
const PASSAGE_HIT = 'Seit einem Jahr';
const PASSAGE_REST =
    ' versuche ich, weniger Plastik zu benutzen. Ich bringe meine eigenen Taschen zum Einkaufen mit und kaufe Obst und Gemüse auf dem Wochenmarkt. Shampoo und Seife kaufe ich jetzt in fester Form, ohne Verpackung. Am schwierigsten finde ich den Verzicht auf To-Go-Becher — ich trinke so gern Kaffee unterwegs! Aber ich habe mir einen Thermobecher gekauft, und die meisten Cafés füllen ihn gerne.';
const QUESTION = 'Seit wann lebt Lena nachhaltig?';
const OPTIONS = ['Seit einem Jahr.', 'Seit fünf Jahren.', 'Seit einem Monat.', 'Seit ihrer Kindheit.'];
const TOTAL_QUESTIONS = 8;

// Géométrie de l'écran recomposé (coordonnées d'une capture 1080 × 1920, comme les vrais écrans).
const PILL = { cx: 540, cy: 88 };
const OPTION_TOP = 1112;
const OPTION_H = 116;
const OPTION_GAP = 18;

const clock = (s: number) => `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`;

export const DeGoethe: React.FC = () => {
    const frame = useCurrentFrame();
    const rise = sp(frame, 2, SPRING.soft);
    const pop = sp(frame, POP, SPRING.bouncy);
    const popped = frame >= POP;

    // Chrono au format de l'app (« 65:00 »), une seconde par temps.
    const seconds = LESEN_SECONDS - Math.max(0, Math.floor((frame - 10) / BEAT));
    const time = clock(seconds);
    const tick = popped ? 1 + 0.03 * Math.max(0, 1 - ((frame - POP) % BEAT) / 5) : 1;

    const fromX = SCREEN_LEFT + PILL.cx * SCREEN_SCALE;
    const fromY = SCREEN_TOP + PILL.cy * SCREEN_SCALE;
    const pillX = interpolate(pop, [0, 1], [fromX, 540]);
    const pillY = interpolate(pop, [0, 1], [fromY, 846]);
    const pillScale = interpolate(pop, [0, 1], [SCREEN_SCALE * 1.05, 1]);

    return (
        <Move enter="bottom" exit="top" exitAt={112}>
            <SceneHeader tag="Mode examen" lines={['Simule ton', '*Goethe*']} start={2} sub="Modellprüfung B1 · Lesen 65 min" exit={108} />

            <div
                style={{
                    position: 'absolute',
                    left: PHONE.left,
                    top: PHONE.top,
                    transform: `translateY(${(1 - rise) * 700}px) perspective(1800px) rotateX(${(1 - rise) * 18}deg) scale(${1 - pop * 0.03})`,
                    transformOrigin: '50% 0%',
                    filter: popped ? `brightness(${1 - pop * 0.18})` : undefined,
                }}
            >
                <Phone width={PHONE.width}>
                    <div style={{ position: 'absolute', left: 0, top: 0, width: 1080, height: 1920, transform: `scale(${SCREEN_SCALE})`, transformOrigin: '0 0' }}>
                        <ExamScreen frame={frame} time={time} pillHidden={popped} />
                    </div>
                </Phone>
            </div>

            <Pointer
                keys={[{ f: TAP, x: SCREEN_LEFT + 330 * SCREEN_SCALE, y: SCREEN_TOP + (OPTION_TOP + OPTION_H / 2 + 8) * SCREEN_SCALE, tap: true }]}
                hideAt={TAP + 10}
            />

            {/* Le chrono sort du téléphone */}
            {popped ? (
                <div
                    style={{
                        position: 'absolute',
                        left: pillX,
                        top: pillY,
                        transform: `translate(-50%, -50%) scale(${pillScale * tick})`,
                        display: 'flex',
                        alignItems: 'center',
                        gap: 26,
                        padding: '22px 46px 22px 24px',
                        borderRadius: 999,
                        background: 'linear-gradient(180deg, #1d2f50 0%, #13233f 100%)',
                        border: '2.5px solid rgba(255,255,255,0.16)',
                        boxShadow: `0 ${30 * pop}px ${80 * pop}px -20px rgba(0,0,0,0.85), 0 0 ${70 * pop}px rgba(59,130,224,0.35)`,
                        fontFamily: FONT.sans,
                        whiteSpace: 'nowrap',
                    }}
                >
                    <AppIcon name="clock" size={96} tone="blue" shadow={false} />
                    <span style={{ fontSize: 108, fontWeight: 800, letterSpacing: '-0.02em', color: C.text, fontVariantNumeric: 'tabular-nums' }}>{time}</span>
                    <span style={{ fontSize: 30, fontWeight: 800, letterSpacing: '0.14em', color: C.skyLight, lineHeight: 1.15 }}>
                        MODE
                        <br />
                        EXAMEN
                    </span>
                </div>
            ) : null}
        </Move>
    );
};

/** L'écran d'exercice en mode examen, tel que l'app le dessine (bandeau chrono, en-tête, points, texte, QCM). */
const ExamScreen: React.FC<{ frame: number; time: string; pillHidden: boolean }> = ({ frame, time, pillHidden }) => {
    const read = tw(frame, READ, 14, 0, 1, EASE.inOutSoft);
    const chosen = sp(frame, TAP, SPRING.pop);
    const answered = frame >= TAP + 6;
    const dotT = tw(frame, TAP + 6, 10, 0, 1, EASE.out);

    return (
        <div style={{ position: 'absolute', inset: 0, background: '#f1f4f8', fontFamily: FONT.sans, color: INK }}>
            {/* Barre du haut */}
            <div style={{ position: 'absolute', left: 0, right: 0, top: 0, height: 150, background: '#fff', borderBottom: '2px solid #e8ecf2' }}>
                <div style={{ position: 'absolute', left: 36, top: 52, fontSize: 40, fontWeight: 800, letterSpacing: '-0.02em' }}>Pratiquer</div>
                <div style={{ position: 'absolute', right: 132, top: 46, display: 'flex', alignItems: 'center', gap: 10, padding: '12px 22px', borderRadius: 999, background: '#fff4dc', color: '#d98a00', fontSize: 34, fontWeight: 800 }}>
                    <Icon name="trophy" size={36} color="#e5a100" stroke={2.6} />0
                </div>
                <div style={{ position: 'absolute', right: 30, top: 38, width: 80, height: 80, borderRadius: '50%', border: '3px solid #e8ecf2', display: 'grid', placeItems: 'center' }}>
                    <div style={{ width: 34, height: 34, borderRadius: '50%', background: OXFORD }} />
                </div>
            </div>

            {/* Bandeau chrono « MODE EXAMEN » (ExamGlobalBanner) */}
            <div
                style={{
                    position: 'absolute',
                    left: PILL.cx,
                    top: PILL.cy,
                    transform: 'translate(-50%, -50%)',
                    display: 'flex',
                    alignItems: 'center',
                    gap: 30,
                    padding: '26px 52px',
                    borderRadius: 44,
                    background: OXFORD,
                    boxShadow: '0 10px 0 0 #0e1a2e, 0 24px 50px rgba(14,26,46,0.35)',
                    whiteSpace: 'nowrap',
                    opacity: pillHidden ? 0 : 1,
                }}
            >
                <Img src={staticFile('promo/app/icons/clock.png')} style={{ width: 46, height: 46, filter: 'brightness(0) invert(1)' }} />
                <span style={{ fontSize: 40, fontWeight: 800, color: '#fff', fontVariantNumeric: 'tabular-nums' }}>{time}</span>
                <span style={{ fontSize: 32, fontWeight: 800, color: 'rgba(255,255,255,0.6)', letterSpacing: '0.01em' }}>MODE EXAMEN</span>
            </div>

            {/* En-tête de l'exercice */}
            <div style={{ position: 'absolute', left: 36, right: 40, top: 214, display: 'flex', alignItems: 'center', gap: 26 }}>
                <div style={{ width: 92, height: 92, borderRadius: 26, background: '#e9ecfb', display: 'grid', placeItems: 'center' }}>
                    <Icon name="book" size={50} color={ACCENT} stroke={2.4} />
                </div>
                <div style={{ flex: 1 }}>
                    <div style={{ fontSize: 40, fontWeight: 800, letterSpacing: '-0.01em' }}>Examen: Goethe-Zertifikat</div>
                    <div style={{ fontSize: 30, fontWeight: 600, color: MUTED, marginTop: 4 }}>Lesen · Teil 1 — Multiple Choice</div>
                </div>
                <div style={{ fontSize: 34, fontWeight: 700, color: MUTED, fontVariantNumeric: 'tabular-nums' }}>1 / {TOTAL_QUESTIONS}</div>
            </div>

            {/* Points de progression (ProgressDots) */}
            <div style={{ position: 'absolute', left: 44, top: 346, display: 'flex', alignItems: 'center', gap: 16 }}>
                {Array.from({ length: TOTAL_QUESTIONS }).map((_, i) => {
                    const filled = answered && i === 0;
                    const active = answered ? i === 1 : i === 0;
                    const grow = i === 1 ? dotT : i === 0 ? 1 - dotT : 0;
                    return (
                        <span
                            key={i}
                            style={{
                                width: 24,
                                height: 24,
                                borderRadius: '50%',
                                background: filled ? ACCENT : active ? '#c7cbfb' : '#dfe3ec',
                                transform: `scale(${1 + 0.4 * (answered ? grow : i === 0 ? 1 : 0)})`,
                                boxShadow: active ? '0 0 0 6px rgba(199,203,251,0.7)' : 'none',
                            }}
                        />
                    );
                })}
            </div>

            {/* Texte du Lesen Teil 1 */}
            <div
                style={{
                    position: 'absolute',
                    left: 28,
                    right: 28,
                    top: 404,
                    padding: '40px 52px 44px',
                    borderRadius: 40,
                    background: '#e9eef6',
                    border: '2px solid #dbe3f0',
                    boxSizing: 'border-box',
                }}
            >
                <div style={{ fontSize: 33, fontWeight: 800, marginBottom: 14, letterSpacing: '-0.01em' }}>{PASSAGE_TITLE}</div>
                <div style={{ fontSize: 30, fontWeight: 600, lineHeight: 1.62, color: '#28324a' }}>
                    <span
                        style={{
                            backgroundImage: 'linear-gradient(rgba(245,166,35,0.45), rgba(245,166,35,0.45))',
                            backgroundRepeat: 'no-repeat',
                            backgroundSize: `${read * 100}% 100%`,
                            borderRadius: 8,
                            padding: '2px 4px',
                            margin: '0 -4px',
                            boxDecorationBreak: 'clone',
                            WebkitBoxDecorationBreak: 'clone',
                            color: read > 0.5 ? INK : undefined,
                        }}
                    >
                        {PASSAGE_HIT}
                    </span>
                    {PASSAGE_REST}
                </div>
            </div>

            {/* Question (mcq.tsx) */}
            <div
                style={{
                    position: 'absolute',
                    left: 28,
                    right: 28,
                    top: 980,
                    height: 740,
                    padding: '40px 52px',
                    borderRadius: 40,
                    background: '#fff',
                    border: '2px solid #e8ecf2',
                    boxSizing: 'border-box',
                }}
            >
                <div style={{ fontSize: 44, fontWeight: 700, letterSpacing: '-0.01em' }}>{QUESTION}</div>
            </div>
            {OPTIONS.map((option, i) => {
                const selected = i === 0 && frame >= TAP;
                const press = i === 0 ? interpolate(frame, [TAP - 3, TAP, TAP + 6], [0, 1, 0], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' }) : 0;
                return (
                    <div
                        key={option}
                        style={{
                            position: 'absolute',
                            left: 80,
                            right: 80,
                            top: OPTION_TOP + i * (OPTION_H + OPTION_GAP) + press * 6,
                            height: OPTION_H,
                            display: 'flex',
                            alignItems: 'center',
                            gap: 28,
                            padding: '0 34px',
                            borderRadius: 24,
                            border: `5px solid ${selected ? PRIMARY : BORDER}`,
                            background: selected ? `rgba(59,130,224,${0.08 * chosen})` : '#fff',
                            boxShadow: `0 ${(selected ? 10 : 8) - press * 6}px 0 0 ${selected ? PRIMARY : BORDER}`,
                            boxSizing: 'border-box',
                            fontSize: 38,
                            fontWeight: 600,
                        }}
                    >
                        <span
                            style={{
                                width: 62,
                                height: 62,
                                borderRadius: '50%',
                                display: 'grid',
                                placeItems: 'center',
                                fontSize: 30,
                                fontWeight: 700,
                                background: selected ? PRIMARY : '#f1f5f9',
                                color: selected ? '#fff' : MUTED,
                                transform: selected ? `scale(${0.8 + 0.2 * chosen})` : undefined,
                            }}
                        >
                            {String.fromCharCode(65 + i)}
                        </span>
                        {option}
                    </div>
                );
            })}

            <TabBar />
        </div>
    );
};

/** Barre d'onglets de l'app, onglet « Pratiquer » actif. */
const TabBar: React.FC = () => {
    const tabs: Array<{ icon: 'home' | 'puzzle' | 'statistics' | 'profile'; label: string; x: number; active?: boolean }> = [
        { icon: 'home', label: 'Accueil', x: 108 },
        { icon: 'puzzle', label: 'Pratiquer', x: 338, active: true },
        { icon: 'statistics', label: 'Résultats', x: 742 },
        { icon: 'profile', label: 'Profil', x: 972 },
    ];
    return (
        <>
            <div style={{ position: 'absolute', left: 0, right: 0, top: 1756, bottom: 0, background: '#fff', borderTop: '2px solid #e8ecf2' }} />
            {tabs.map((tab) => (
                <div key={tab.icon} style={{ position: 'absolute', left: tab.x - 110, width: 220, top: 1772, display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 8 }}>
                    {tab.active ? <div style={{ position: 'absolute', top: -18, width: 56, height: 8, borderRadius: 4, background: PRIMARY }} /> : null}
                    <Img src={staticFile(`promo/app/icons/${tab.icon}.png`)} style={{ width: 76, height: 76, objectFit: 'contain', opacity: tab.active ? 1 : 0.55 }} />
                    <span style={{ fontSize: 28, fontWeight: tab.active ? 800 : 600, color: tab.active ? INK : '#8a96ab' }}>{tab.label}</span>
                </div>
            ))}
            <div
                style={{
                    position: 'absolute',
                    left: 540 - 72,
                    top: 1682,
                    width: 144,
                    height: 144,
                    borderRadius: '50%',
                    background: OXFORD,
                    boxShadow: '0 12px 30px rgba(14,26,46,0.35)',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    gap: 4,
                    color: '#fff',
                    fontSize: 36,
                    fontWeight: 800,
                }}
            >
                <Icon name="sparkles" size={44} color="#fff" stroke={2.4} />
                IA
            </div>
        </>
    );
};
