import { noise2D } from '@remotion/noise';
import React from 'react';
import { interpolate, useCurrentFrame } from 'remotion';
import { Icon } from '../components/Icons';
import { Move, SceneHeader } from '../components/SceneKit';
import { Card, Pill, Pointer, ProgressBar } from '../components/UI';
import { C, EASE, FONT, SPRING, sp, tw } from '../theme';

// Étape 2 (18–26 s) : le test de niveau (10 questions dans l'app), puis le résultat CECR.

const CARD = { left: 80, top: 740, width: 920 };
const TAP1 = 40;
const SWAP = 62;
const TAP2 = 96;
const RAMP = 108;
const RESULT = 136;
const LAND = 160;
const RING = { x: 540, y: 1060, r: 232 };

type OptionState = 'idle' | 'picked' | 'right';

const Option: React.FC<{ letter: string; text: string; state: OptionState; width: number; height: number }> = ({ letter, text, state, width, height }) => {
    const colors = {
        idle: { border: C.paperLine, bg: '#ffffff', badge: '#eef2f7', badgeFg: C.inkSoft },
        picked: { border: C.sky, bg: C.skyPale, badge: C.sky, badgeFg: '#fff' },
        right: { border: C.green, bg: C.greenPale, badge: C.green, badgeFg: '#fff' },
    }[state];
    return (
        <div
            style={{
                width,
                height,
                borderRadius: 28,
                border: `3.5px solid ${colors.border}`,
                background: colors.bg,
                display: 'flex',
                alignItems: 'center',
                gap: 22,
                padding: '0 26px',
                boxSizing: 'border-box',
                fontSize: 40,
                fontWeight: 700,
                color: C.ink,
            }}
        >
            <span
                style={{
                    width: 56,
                    height: 56,
                    borderRadius: '50%',
                    background: colors.badge,
                    color: colors.badgeFg,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    fontSize: 28,
                    fontWeight: 800,
                    flexShrink: 0,
                }}
            >
                {state === 'right' ? <Icon name="check" size={32} color="#fff" stroke={3.4} /> : letter}
            </span>
            {text}
        </div>
    );
};

const QuizHeader: React.FC<{ n: number; progress: number }> = ({ n, progress }) => (
    <>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <Pill tone="sky" icon="sparkles" size={28}>
                Test de niveau
            </Pill>
            <span style={{ fontSize: 32, fontWeight: 800, color: C.inkSoft, fontVariantNumeric: 'tabular-nums' }}>
                {n}
                <span style={{ color: C.inkDim }}> / 10</span>
            </span>
        </div>
        <div style={{ marginTop: 26 }}>
            <ProgressBar value={progress} width={CARD.width - 88} />
        </div>
    </>
);

export const S5Diagnostic: React.FC = () => {
    const frame = useCurrentFrame();

    // --- Carte 1 : grammaire
    const c1Pop = sp(frame, 6, SPRING.soft);
    const c1Out = tw(frame, SWAP, 14, 0, 1, EASE.in);
    const c1State: OptionState = frame >= TAP1 + 6 ? 'right' : frame >= TAP1 ? 'picked' : 'idle';
    const fill1 = sp(frame, TAP1 + 8, SPRING.bouncy);
    const prog1 = 0.3 + 0.1 * tw(frame, TAP1 + 6, 16);

    // --- Carte 2 : compréhension orale
    const c2In = tw(frame, SWAP + 2, 16, 0, 1, EASE.out);
    const c2Out = tw(frame, RAMP, 10, 0, 1, EASE.in);
    const c2State: OptionState = frame >= TAP2 + 6 ? 'right' : frame >= TAP2 ? 'picked' : 'idle';
    const prog2 = 0.4 + 0.1 * tw(frame, TAP2 + 6, 14);

    // --- Accélération : 6 → 10
    const rampIn = tw(frame, RAMP + 2, 8, 0, 1, EASE.out);
    const rampCount = Math.min(10, 6 + Math.max(0, Math.floor((frame - (RAMP + 4)) / 4)));
    const rampOut = tw(frame, RESULT - 4, 10, 0, 1, EASE.in);
    const rampPulse = 1 + 0.035 * Math.max(0, 1 - ((frame - (RAMP + 4)) % 4) / 3);

    return (
        <Move enter="right" exit="zoom" exitAt={224} origin="50% 55%">
            <SceneHeader tag="Étape 2 / 3" lines={['Découvre ton', '*vrai niveau*']} start={4} sub="10 questions pour situer ton niveau" exit={222} />

            {/* Carte 1 */}
            {frame < SWAP + 16 ? (
                <div
                    style={{
                        position: 'absolute',
                        left: CARD.left,
                        top: CARD.top,
                        width: CARD.width,
                        transform: `translateX(${-c1Out * 1150}px) rotate(${-c1Out * 12}deg) scale(${0.9 + 0.1 * c1Pop})`,
                        opacity: Math.min(1, c1Pop * 1.5),
                    }}
                >
                    <Card padding={44}>
                        <QuizHeader n={4} progress={prog1} />
                        <div style={{ marginTop: 34, fontSize: 30, fontWeight: 600, color: C.inkSoft }}>Complète la phrase</div>
                        <div style={{ marginTop: 14, fontSize: 52, fontWeight: 800, letterSpacing: '-0.02em', lineHeight: 1.35 }}>
                            Il faut que tu{' '}
                            <span
                                style={{
                                    display: 'inline-block',
                                    minWidth: 220,
                                    textAlign: 'center',
                                    borderBottom: `5px ${frame >= TAP1 + 6 ? 'solid' : 'dashed'} ${frame >= TAP1 + 6 ? C.green : C.sky}`,
                                    color: C.green,
                                    lineHeight: 1.1,
                                }}
                            >
                                <span style={{ display: 'inline-block', transform: `scale(${frame >= TAP1 + 8 ? fill1 : 0})` }}>viennes</span>
                            </span>{' '}
                            à l'heure.
                        </div>
                        <div style={{ marginTop: 34, display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 22 }}>
                            {['viens', 'viennes', 'venir', 'viendras'].map((t, i) => (
                                <Option key={t} letter={'ABCD'[i]} text={t} state={i === 1 ? c1State : 'idle'} width={405} height={110} />
                            ))}
                        </div>
                    </Card>
                </div>
            ) : null}

            {/* Carte 2 */}
            {frame >= SWAP && frame < RAMP + 12 ? (
                <div
                    style={{
                        position: 'absolute',
                        left: CARD.left,
                        top: CARD.top,
                        width: CARD.width,
                        transform: `translateX(${(1 - c2In) * 1150 - c2Out * 1150}px) rotate(${(1 - c2In) * 8 - c2Out * 10}deg)`,
                    }}
                >
                    <Card padding={44}>
                        <QuizHeader n={5} progress={prog2} />
                        <div style={{ marginTop: 34, fontSize: 30, fontWeight: 600, color: C.inkSoft }}>Écoute et choisis la bonne réponse</div>
                        <div style={{ marginTop: 22, display: 'flex', alignItems: 'center', gap: 28 }}>
                            <div
                                style={{
                                    width: 96,
                                    height: 96,
                                    borderRadius: '50%',
                                    background: `linear-gradient(135deg, ${C.skyLight}, ${C.sky})`,
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    boxShadow: `0 0 0 ${8 + 6 * Math.sin(frame * 0.5)}px rgba(59,130,224,0.15)`,
                                    flexShrink: 0,
                                }}
                            >
                                <Icon name="headphones" size={46} color="#fff" stroke={2.4} />
                            </div>
                            <div style={{ display: 'flex', alignItems: 'center', gap: 7, height: 96 }}>
                                {Array.from({ length: 30 }).map((_, i) => {
                                    const playing = frame > SWAP + 12 && frame < TAP2 + 4;
                                    const base = 0.25 + 0.35 * Math.abs(Math.sin(i * 1.7));
                                    const live = playing ? Math.abs(noise2D(`wv${i}`, frame * 0.18, i * 0.3)) * 0.9 : 0;
                                    return (
                                        <div
                                            key={i}
                                            style={{
                                                width: 11,
                                                height: 96 * Math.min(1, base + live),
                                                borderRadius: 6,
                                                background: i / 30 < interpolate(frame, [SWAP + 12, TAP2 + 4], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' }) ? C.sky : '#d5e0ef',
                                            }}
                                        />
                                    );
                                })}
                            </div>
                        </div>
                        <div style={{ marginTop: 30, display: 'flex', flexDirection: 'column', gap: 18 }}>
                            {['À la gare', 'Au marché', 'À la banque'].map((t, i) => (
                                <Option key={t} letter={'ABC'[i]} text={t} state={i === 2 ? c2State : 'idle'} width={CARD.width - 88} height={98} />
                            ))}
                        </div>
                    </Card>
                </div>
            ) : null}

            {/* Questions 6 à 10, en accéléré */}
            {frame >= RAMP && frame < RESULT + 8 ? (
                <div
                    style={{
                        position: 'absolute',
                        left: CARD.left,
                        top: CARD.top,
                        width: CARD.width,
                        transform: `translateX(${(1 - rampIn) * 1150}px) scale(${rampPulse * (1 - rampOut * 0.7)})`,
                        transformOrigin: '50% 60%',
                        opacity: 1 - rampOut,
                        filter: rampOut > 0.05 ? `blur(${rampOut * 10}px)` : undefined,
                    }}
                >
                    <Card padding={44}>
                        <QuizHeader n={rampCount} progress={rampCount / 10} />
                        {[0.8, 0.55, 1, 1, 1].map((w, i) => (
                            <div
                                key={i}
                                style={{
                                    marginTop: i === 0 ? 40 : i === 2 ? 40 : 20,
                                    height: i < 2 ? 34 : 96,
                                    width: `${w * 100}%`,
                                    borderRadius: i < 2 ? 12 : 28,
                                    background: i < 2 ? '#e3e9f2' : '#f0f4f9',
                                    border: i < 2 ? undefined : `3.5px solid ${C.paperLine}`,
                                    transform: `translateX(${noise2D(`rs${i}`, frame * 0.6, 0) * 6}px)`,
                                }}
                            />
                        ))}
                    </Card>
                </div>
            ) : null}

            <Pointer
                keys={[
                    { f: TAP1, x: CARD.left + 44 + 405 + 22 + 202, y: 1220, tap: true },
                    { f: TAP2, x: CARD.left + 460, y: 1350, tap: true },
                ]}
                hideAt={TAP2 + 12}
            />

            {frame >= RESULT - 2 ? <Result frame={frame} /> : null}
        </Move>
    );
};

const LEVELS = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

const Result: React.FC<{ frame: number }> = ({ frame }) => {
    const ringPop = sp(frame, RESULT, SPRING.pop);
    const arc = tw(frame, RESULT + 4, 34, 0, 1, EASE.out) * 0.5;
    const circumference = 2 * Math.PI * RING.r;
    const levelIdx = frame >= LAND ? 2 : frame >= RESULT + 14 ? 1 : 0;
    const land = sp(frame, LAND, SPRING.bouncy);
    const burst = tw(frame, LAND, 26, 0, 1, EASE.out);

    const segW = (920 - 5 * 14) / 6;
    const scaleIn = tw(frame, LAND + 4, 14);
    const goal = sp(frame, LAND + 26, SPRING.bouncy);

    return (
        <>
            {/* Jauge */}
            <svg width={1080} height={1920} style={{ position: 'absolute', inset: 0, overflow: 'visible' }}>
                <defs>
                    <linearGradient id="diag-arc" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0" stopColor={C.skyLight} />
                        <stop offset="1" stopColor={C.gold} />
                    </linearGradient>
                </defs>
                <g transform={`translate(${RING.x} ${RING.y}) scale(${0.6 + 0.4 * ringPop}) translate(${-RING.x} ${-RING.y})`} opacity={Math.min(1, ringPop * 1.5)}>
                    <circle cx={RING.x} cy={RING.y} r={RING.r} fill="rgba(255,255,255,0.03)" stroke="rgba(255,255,255,0.09)" strokeWidth={28} />
                    <circle
                        cx={RING.x}
                        cy={RING.y}
                        r={RING.r}
                        fill="none"
                        stroke="url(#diag-arc)"
                        strokeWidth={28}
                        strokeLinecap="round"
                        strokeDasharray={`${circumference * arc} ${circumference}`}
                        transform={`rotate(-90 ${RING.x} ${RING.y})`}
                        style={{ filter: 'drop-shadow(0 0 18px rgba(59,130,224,0.6))' }}
                    />
                    <circle cx={RING.x} cy={RING.y} r={RING.r + burst * 160} fill="none" stroke={C.gold} strokeWidth={10 * (1 - burst)} opacity={frame >= LAND ? 1 - burst : 0} />
                </g>
            </svg>
            <div
                style={{
                    position: 'absolute',
                    left: RING.x - 220,
                    top: RING.y - 150,
                    width: 440,
                    height: 300,
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    justifyContent: 'center',
                    opacity: Math.min(1, ringPop * 1.5),
                    fontFamily: FONT.sans,
                }}
            >
                <span style={{ fontSize: 26, fontWeight: 700, letterSpacing: '0.24em', color: C.textMid, textTransform: 'uppercase' }}>Ton niveau</span>
                <span
                    style={{
                        fontSize: 178,
                        fontWeight: 800,
                        letterSpacing: '-0.04em',
                        lineHeight: 1,
                        color: C.text,
                        transform: `scale(${frame >= LAND ? 0.7 + 0.3 * land : 1})`,
                        marginTop: 8,
                    }}
                >
                    {LEVELS[levelIdx]}
                </span>
                <span
                    style={{
                        fontFamily: FONT.serif,
                        fontStyle: 'italic',
                        fontWeight: 700,
                        fontSize: 46,
                        color: C.gold,
                        opacity: tw(frame, LAND + 2, 12),
                    }}
                >
                    intermédiaire
                </span>
            </div>

            {/* Échelle CECR */}
            <div style={{ position: 'absolute', left: 80, top: 1370, width: 920, display: 'flex', gap: 14 }}>
                {LEVELS.map((lv, i) => {
                    const filled = i <= 2 ? tw(frame, LAND + 2 + i * 4, 10) : 0;
                    const isGoal = i === 3;
                    return (
                        <div key={lv} style={{ width: segW, display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 14, opacity: scaleIn }}>
                            <div
                                style={{
                                    position: 'relative',
                                    width: '100%',
                                    height: 22,
                                    borderRadius: 11,
                                    background: 'rgba(255,255,255,0.09)',
                                    border: isGoal ? `3px dashed rgba(245,166,35,${0.9 * goal})` : undefined,
                                    boxSizing: 'border-box',
                                    overflow: 'hidden',
                                }}
                            >
                                <div style={{ width: `${filled * 100}%`, height: '100%', background: i === 2 ? C.gold : C.sky, borderRadius: 11 }} />
                            </div>
                            <span
                                style={{
                                    fontFamily: FONT.sans,
                                    fontWeight: 800,
                                    fontSize: 32,
                                    color: i === 2 ? C.gold : isGoal ? C.goldLight : i < 2 ? C.text : C.textDim,
                                }}
                            >
                                {lv}
                            </span>
                        </div>
                    );
                })}
            </div>
            <div
                style={{
                    position: 'absolute',
                    left: 80 + 3 * (segW + 14) + segW / 2,
                    top: 1306,
                    transform: `translateX(-50%) scale(${goal}) translateY(${(1 - goal) * 20}px)`,
                    display: 'flex',
                    alignItems: 'center',
                    gap: 10,
                    padding: '8px 18px',
                    borderRadius: 999,
                    background: C.gold,
                    color: '#1b1204',
                    fontFamily: FONT.sans,
                    fontWeight: 800,
                    fontSize: 26,
                    whiteSpace: 'nowrap',
                }}
            >
                <Icon name="target" size={28} color="#1b1204" stroke={2.6} />
                Objectif
            </div>

            {/* Forces / faiblesses */}
            <div style={{ position: 'absolute', top: 1500, left: 0, right: 0, display: 'flex', justifyContent: 'center', gap: 20 }}>
                {[
                    { tone: 'green' as const, icon: 'check' as const, text: 'Point fort : la lecture', d: 34 },
                    { tone: 'gold' as const, icon: 'target' as const, text: 'À travailler : l’oral', d: 40 },
                ].map((p) => {
                    const pop = sp(frame, LAND + p.d, SPRING.pop);
                    return (
                        <div key={p.text} style={{ transform: `scale(${pop})`, opacity: Math.min(1, pop * 1.5) }}>
                            <Pill tone={p.tone} icon={p.icon} size={30}>
                                {p.text}
                            </Pill>
                        </div>
                    );
                })}
            </div>
        </>
    );
};
