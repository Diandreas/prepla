import React from 'react';
import { AbsoluteFill, interpolate, useCurrentFrame } from 'remotion';
import { Icon } from '../components/Icons';
import { KineticText } from '../components/KineticText';
import { useTextWidths } from '../components/useTextWidths';
import { BEAT, C, EASE, FONT, SPRING, sp, tw } from '../theme';

// Accroche (0–4 s) : « Ton [IELTS / TCF / DELF / Goethe…] approche ? » — une machine à sous
// d'examens qui ralentit puis se pose sur « examen », pendant qu'un chrono se vide.

const SLOT: Array<{ at: number; word: string }> = [
    { at: 8, word: 'IELTS' },
    { at: 13, word: 'TOEFL' },
    { at: 18, word: 'TCF' },
    { at: 23, word: 'TEF' },
    { at: 28, word: 'DELF' },
    { at: 33, word: 'Goethe' },
    { at: 39, word: 'DALF' },
    { at: 46, word: 'TestDaF' },
    { at: 54, word: 'Cambridge' },
    { at: 64, word: 'TCF' },
    { at: 76, word: 'examen' },
];
const FINAL = SLOT.length - 1;
const SLOT_SIZE = 124;
const FINAL_SIZE = 168;
const RING_R = 432;
const CENTER = { x: 540, y: 930 };

export const S1Hook: React.FC = () => {
    const frame = useCurrentFrame();
    const widths = useTextWidths(
        SLOT.map((s, i) =>
            i === FINAL
                ? { text: s.word, fontFamily: FONT.serif, fontSize: FINAL_SIZE, fontWeight: 700, italic: true, letterSpacing: '-0.015em' }
                : { text: s.word, fontFamily: FONT.sans, fontSize: SLOT_SIZE, fontWeight: 800, letterSpacing: '-0.035em' },
        ),
    );

    // --- Chrono circulaire : se vide par à-coups, à chaque temps (tic-tac).
    const beats = frame / BEAT;
    const step = Math.floor(beats);
    const within = EASE.outBack(Math.min(1, (beats - step) * 3.2));
    const remaining = 1 - 0.065 * (step + within);
    const ringIn = tw(frame, 0, 18);
    const ringOut = tw(frame, 98, 20, 0, 1, EASE.in);
    const urgency = interpolate(frame, [0, 100], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
    const arcColor = urgency < 0.5 ? C.gold : '#f2683a';
    const circumference = 2 * Math.PI * RING_R;
    const endAngle = -Math.PI / 2 + remaining * Math.PI * 2;

    // --- Compte à rebours J-30 → J-16, un cran par croche.
    const day = Math.max(16, 30 - Math.max(0, Math.floor((frame - 12) / 7.5)));
    const chipIn = sp(frame, 6, SPRING.pop);
    const chipOut = tw(frame, 96, 10, 0, 1, EASE.in);
    const tickPulse = 1 + 0.06 * Math.max(0, 1 - ((frame - 12) % 7.5) / 4) * (frame > 12 ? 1 : 0);

    // --- Machine à sous.
    let current = 0;
    for (let i = 0; i < SLOT.length; i++) if (frame >= SLOT[i].at) current = i;
    const cur = SLOT[current];
    const next = SLOT[current + 1];
    const span = next ? next.at - cur.at : 8;
    const isFinal = current === FINAL;
    const moveT = isFinal ? sp(frame, cur.at, SPRING.bouncy) : tw(frame, cur.at, Math.max(3, span * 0.7), 0, 1, EASE.out);
    const slotOpen = tw(frame, 6, 10);
    const slotExit = tw(frame, 98, 12, 0, 1, EASE.in);
    const pillFade = tw(frame, SLOT[FINAL].at, 10);

    const wordWidth = (i: number) => (widths ? widths[i] : SLOT[i].word.length * SLOT_SIZE * 0.62);
    const prevW = current > 0 ? wordWidth(current - 1) : wordWidth(0);
    const pillW = interpolate(moveT, [0, 1], [prevW, wordWidth(current)]) + 110;

    const renderWord = (i: number, y: number, blur: number) => {
        const w = SLOT[i];
        const final = i === FINAL;
        return (
            <div
                key={`${i}-${w.at}`}
                style={{
                    position: 'absolute',
                    left: 0,
                    right: 0,
                    top: 0,
                    bottom: 0,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    transform: `translateY(${y}%)`,
                    filter: blur > 0.2 ? `blur(${blur}px)` : undefined,
                    fontFamily: final ? FONT.serif : FONT.sans,
                    fontStyle: final ? 'italic' : 'normal',
                    fontWeight: final ? 700 : 800,
                    fontSize: final ? FINAL_SIZE : SLOT_SIZE,
                    letterSpacing: final ? '-0.015em' : '-0.035em',
                    color: final ? C.gold : C.text,
                    whiteSpace: 'nowrap',
                }}
            >
                {w.word}
            </div>
        );
    };

    return (
        <AbsoluteFill>
            {/* Chrono */}
            <svg
                width={1080}
                height={1920}
                style={{
                    position: 'absolute',
                    inset: 0,
                    opacity: ringIn * (1 - ringOut),
                    transform: `scale(${0.86 + 0.14 * ringIn + ringOut * 0.45})`,
                    transformOrigin: `${CENTER.x}px ${CENTER.y}px`,
                }}
            >
                <circle cx={CENTER.x} cy={CENTER.y} r={RING_R} fill="none" stroke="rgba(255,255,255,0.07)" strokeWidth={10} />
                <circle
                    cx={CENTER.x}
                    cy={CENTER.y}
                    r={RING_R}
                    fill="none"
                    stroke={arcColor}
                    strokeWidth={10}
                    strokeLinecap="round"
                    strokeDasharray={`${circumference * remaining} ${circumference}`}
                    transform={`rotate(-90 ${CENTER.x} ${CENTER.y})`}
                    opacity={0.85}
                    style={{ filter: `drop-shadow(0 0 14px ${arcColor})` }}
                />
                <circle cx={CENTER.x + Math.cos(endAngle) * RING_R} cy={CENTER.y + Math.sin(endAngle) * RING_R} r={16} fill={arcColor} />
                {Array.from({ length: 60 }).map((_, i) => {
                    const a = (i / 60) * Math.PI * 2 - Math.PI / 2;
                    const major = i % 5 === 0;
                    const tIn = tw(frame, (i / 60) * 22, 8);
                    const r1 = RING_R + 34;
                    const r2 = RING_R + (major ? 70 : 52);
                    return (
                        <line
                            key={i}
                            x1={CENTER.x + Math.cos(a) * r1}
                            y1={CENTER.y + Math.sin(a) * r1}
                            x2={CENTER.x + Math.cos(a) * (r1 + (r2 - r1) * tIn)}
                            y2={CENTER.y + Math.sin(a) * (r1 + (r2 - r1) * tIn)}
                            stroke={major ? 'rgba(255,255,255,0.45)' : 'rgba(255,255,255,0.18)'}
                            strokeWidth={major ? 5 : 3}
                            strokeLinecap="round"
                        />
                    );
                })}
            </svg>

            {/* J-30 */}
            <div
                style={{
                    position: 'absolute',
                    top: 330,
                    left: 0,
                    right: 0,
                    display: 'flex',
                    justifyContent: 'center',
                    opacity: Math.min(1, chipIn * 1.5) * (1 - chipOut),
                    transform: `scale(${(0.6 + 0.4 * chipIn) * tickPulse}) translateY(${-chipOut * 20}px)`,
                }}
            >
                <div
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: 18,
                        padding: '18px 34px',
                        borderRadius: 999,
                        background: day <= 20 ? 'rgba(242,104,58,0.16)' : 'rgba(245,166,35,0.14)',
                        border: `2.5px solid ${day <= 20 ? 'rgba(242,104,58,0.65)' : 'rgba(245,166,35,0.55)'}`,
                        color: day <= 20 ? '#ff8a5c' : C.gold,
                        fontFamily: FONT.sans,
                        fontWeight: 800,
                        fontSize: 46,
                        letterSpacing: '0.02em',
                    }}
                >
                    <Icon name="calendar" size={46} color="currentColor" stroke={2.4} />
                    <span style={{ fontVariantNumeric: 'tabular-nums' }}>J-{day}</span>
                </div>
            </div>

            {/* Titre */}
            <AbsoluteFill style={{ justifyContent: 'center', alignItems: 'center', paddingTop: 20 }}>
                <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 0, transform: `translateY(${CENTER.y - 960}px)` }}>
                    <KineticText lines={['Ton']} start={4} size={140} exit={96} />
                    <div style={{ position: 'relative', height: 196, width: 1000, display: 'flex', justifyContent: 'center' }}>
                        {/* Fenêtre de la machine à sous */}
                        <div
                            style={{
                                position: 'absolute',
                                top: 8,
                                height: 180,
                                width: pillW,
                                left: 500 - pillW / 2,
                                borderRadius: 999,
                                border: `4px solid rgba(245,166,35,${0.55 * (1 - pillFade)})`,
                                background: `rgba(245,166,35,${0.10 * (1 - pillFade)})`,
                                boxShadow: `0 0 60px rgba(245,166,35,${0.18 * (1 - pillFade)})`,
                                transform: `scaleX(${slotOpen * (1 - slotExit * 0.3)}) scale(${1 + pillFade * 0.15})`,
                                opacity: slotOpen * (1 - slotExit),
                            }}
                        />
                        <div
                            style={{
                                position: 'absolute',
                                inset: 0,
                                overflow: 'hidden',
                                opacity: slotOpen * (1 - slotExit),
                                transform: `translateY(${-slotExit * 40}px)`,
                            }}
                        >
                            {frame >= SLOT[0].at ? (
                                <>
                                    {current > 0 && (isFinal ? frame - cur.at < 8 : moveT < 1)
                                        ? renderWord(current - 1, -Math.min(1, moveT) * 110, Math.min(1, moveT) * 10)
                                        : null}
                                    {renderWord(current, (1 - moveT) * 110, isFinal ? 0 : (1 - moveT) * 10)}
                                </>
                            ) : null}
                        </div>
                    </div>
                    <KineticText lines={['approche ?']} start={12} size={140} exit={100} />
                </div>
            </AbsoluteFill>
        </AbsoluteFill>
    );
};
