import React from 'react';
import { useCurrentFrame } from 'remotion';
import { AppGif, AppIcon } from '../components/AppAssets';
import { Icon } from '../components/Icons';
import { Move, SceneHeader } from '../components/SceneKit';
import { Card, Pill, Pointer, ProgressBar } from '../components/UI';
import { C, EASE, FONT, SPRING, sp, tw } from '../theme';

// Étape 2 (18–26 s) : le « Test de placement » de l'app, tel qu'il est construit
// (resources/js/pages/onboarding/placement-test.tsx) : section A grammaire & vocabulaire,
// section B compréhension écrite, section C rédaction, puis « Analyse de ton niveau… »
// et l'écran de résultat (« Parfait point de départ ! », « Progression CECRL »).

const CARD = { left: 80, top: 760, width: 920 };
const TAP1 = 40;
const SWAP1 = 62;
const TAP2 = 94;
const SWAP2 = 106;
const ANALYSE = 126;
const RESULT = 142;
const LAND = 162;
const RING = { x: 540, y: 1030, r: 240 };

type OptionState = 'idle' | 'picked' | 'right';

const Option: React.FC<{ letter: string; text: string; state: OptionState; width: number | string; height: number; size?: number }> = ({
    letter,
    text,
    state,
    width,
    height,
    size = 40,
}) => {
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
                borderRadius: 26,
                border: `3.5px solid ${colors.border}`,
                background: colors.bg,
                display: 'flex',
                alignItems: 'center',
                gap: 20,
                padding: '0 24px',
                boxSizing: 'border-box',
                fontSize: size,
                fontWeight: 700,
                color: C.ink,
            }}
        >
            <span
                style={{
                    width: 52,
                    height: 52,
                    borderRadius: '50%',
                    background: colors.badge,
                    color: colors.badgeFg,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    fontSize: 26,
                    fontWeight: 800,
                    flexShrink: 0,
                }}
            >
                {state === 'right' ? <Icon name="check" size={30} color="#fff" stroke={3.4} /> : letter}
            </span>
            {text}
        </div>
    );
};

const PlacementHeader: React.FC<{ section: string; title: string; progress: number; count?: string }> = ({ section, title, progress, count }) => (
    <>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 16 }}>
                <AppIcon name="sparkles" size={64} tone="blue" shadow={false} />
                <span style={{ fontSize: 30, fontWeight: 800, color: '#1d63c4', letterSpacing: '-0.01em' }}>Test de placement</span>
            </div>
            <Pill tone="neutral" size={26}>
                {section}
            </Pill>
        </div>
        <div style={{ marginTop: 22, display: 'flex', justifyContent: 'space-between', alignItems: 'baseline' }}>
            <span style={{ fontSize: 40, fontWeight: 800, letterSpacing: '-0.02em' }}>{title}</span>
            {count ? <span style={{ fontSize: 28, fontWeight: 700, color: C.inkDim, fontVariantNumeric: 'tabular-nums' }}>{count}</span> : null}
        </div>
        <div style={{ marginTop: 16 }}>
            <ProgressBar value={progress} width={CARD.width - 88} height={12} />
        </div>
    </>
);

export const S5Diagnostic: React.FC = () => {
    const frame = useCurrentFrame();

    const c1Pop = sp(frame, 6, SPRING.soft);
    const c1Out = tw(frame, SWAP1, 14, 0, 1, EASE.in);
    const c1State: OptionState = frame >= TAP1 + 6 ? 'right' : frame >= TAP1 ? 'picked' : 'idle';
    const fill1 = sp(frame, TAP1 + 8, SPRING.bouncy);

    const c2In = tw(frame, SWAP1 + 2, 16, 0, 1, EASE.out);
    const c2Out = tw(frame, SWAP2, 12, 0, 1, EASE.in);
    const c2State: OptionState = frame >= TAP2 + 6 ? 'right' : frame >= TAP2 ? 'picked' : 'idle';

    const c3In = tw(frame, SWAP2 + 2, 14, 0, 1, EASE.out);
    const c3Out = tw(frame, RESULT - 10, 10, 0, 1, EASE.in);
    const essay = 'À mon avis, les réseaux sociaux rapprochent les gens, mais…';
    const typed = Math.round(tw(frame, SWAP2 + 6, 16, 0, essay.length, (t) => t));
    const analyse = tw(frame, ANALYSE, 8);

    return (
        <Move enter="right" exit="zoom" exitAt={232} origin="50% 55%">
            <SceneHeader tag="Étape 2 / 3" lines={['Découvre ton', '*vrai niveau*']} start={4} sub="Grammaire · Lecture · Rédaction" exit={226} />

            {/* Section A — Grammaire & Vocabulaire */}
            {frame < SWAP1 + 16 ? (
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
                        <PlacementHeader section="Section A" title="Grammaire & Vocabulaire" progress={0.38 + 0.12 * tw(frame, TAP1 + 6, 14)} count="4 / 8" />
                        <div style={{ marginTop: 30, fontSize: 30, fontWeight: 600, color: C.inkSoft }}>Complète la phrase :</div>
                        <div style={{ marginTop: 12, fontSize: 50, fontWeight: 800, letterSpacing: '-0.02em', lineHeight: 1.35 }}>
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
                        <div style={{ marginTop: 30, display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 20 }}>
                            {['viens', 'viennes', 'venir', 'viendras'].map((t, i) => (
                                <Option key={t} letter={'ABCD'[i]} text={t} state={i === 1 ? c1State : 'idle'} width="100%" height={104} />
                            ))}
                        </div>
                    </Card>
                </div>
            ) : null}

            {/* Section B — Compréhension écrite */}
            {frame >= SWAP1 && frame < SWAP2 + 14 ? (
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
                        <PlacementHeader section="Section B" title="Compréhension écrite" progress={0.66 + 0.1 * tw(frame, TAP2 + 6, 12)} />
                        <div
                            style={{
                                marginTop: 26,
                                padding: '22px 26px',
                                borderRadius: 22,
                                background: '#eef3fa',
                                border: `2px solid ${C.paperLine}`,
                            }}
                        >
                            <div style={{ fontSize: 22, fontWeight: 800, letterSpacing: '0.14em', textTransform: 'uppercase', color: C.inkDim }}>Texte à lire</div>
                            <div style={{ marginTop: 8, fontSize: 32, fontWeight: 500, lineHeight: 1.45, color: C.ink }}>
                                Le télétravail s'est beaucoup développé. Il offre plus de liberté, mais il peut aussi isoler les salariés.
                            </div>
                        </div>
                        <div style={{ marginTop: 22, fontSize: 34, fontWeight: 800, lineHeight: 1.3 }}>Selon le texte, quel est un inconvénient du télétravail ?</div>
                        <div style={{ marginTop: 20, display: 'flex', flexDirection: 'column', gap: 14 }}>
                            {['Le manque de liberté', "L'isolement", 'Le coût des transports'].map((t, i) => (
                                <Option key={t} letter={'ABC'[i]} text={t} state={i === 1 ? c2State : 'idle'} width="100%" height={84} size={34} />
                            ))}
                        </div>
                    </Card>
                </div>
            ) : null}

            {/* Section C — Rédaction, puis « Analyse de ton niveau… » */}
            {frame >= SWAP2 && frame < RESULT ? (
                <div
                    style={{
                        position: 'absolute',
                        left: CARD.left,
                        top: CARD.top,
                        width: CARD.width,
                        transform: `translateX(${(1 - c3In) * 1150}px) scale(${1 - c3Out * 0.6})`,
                        transformOrigin: '50% 60%',
                        opacity: 1 - c3Out,
                        filter: c3Out > 0.05 ? `blur(${c3Out * 10}px)` : undefined,
                    }}
                >
                    <Card padding={44}>
                        <PlacementHeader section="Section C" title="Rédaction" progress={0.92} />
                        <div style={{ position: 'relative', marginTop: 26 }}>
                            <div style={{ fontSize: 30, fontWeight: 600, color: C.inkSoft }}>Donne ton avis sur les réseaux sociaux.</div>
                            <div
                                style={{
                                    marginTop: 16,
                                    minHeight: 200,
                                    padding: '22px 26px',
                                    borderRadius: 22,
                                    border: `2.5px solid ${C.sky}`,
                                    background: '#fff',
                                    fontSize: 36,
                                    fontWeight: 500,
                                    lineHeight: 1.45,
                                    color: C.ink,
                                }}
                            >
                                {essay.slice(0, typed)}
                                <span style={{ display: 'inline-block', width: 4, height: 40, marginLeft: 3, background: C.sky, verticalAlign: 'middle', opacity: Math.floor(frame / 6) % 2 }} />
                            </div>
                            <div style={{ marginTop: 14, fontSize: 24, fontWeight: 600, color: C.inkDim }}>
                                Prends ton temps, la longueur de ta réponse confirme ton niveau
                            </div>
                            {/* Écran d'analyse de l'app */}
                            <div
                                style={{
                                    position: 'absolute',
                                    inset: -10,
                                    borderRadius: 24,
                                    background: '#fbfdff',
                                    display: 'flex',
                                    flexDirection: 'column',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    gap: 14,
                                    opacity: analyse,
                                }}
                            >
                                <AppGif name="loading" width={110} height={110} />
                                <div style={{ fontSize: 40, fontWeight: 800, letterSpacing: '-0.02em' }}>Analyse de ton niveau…</div>
                                <div style={{ fontSize: 26, fontWeight: 600, color: C.inkSoft }}>L'IA analyse tes réponses de grammaire et de lecture</div>
                            </div>
                        </div>
                    </Card>
                </div>
            ) : null}

            <Pointer
                keys={[
                    { f: TAP1, x: CARD.left + 44 + 406 + 20 + 203, y: CARD.top + 502, tap: true },
                    { f: TAP2, x: CARD.left + 380, y: CARD.top + 723, tap: true },
                ]}
                hideAt={TAP2 + 10}
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
    const levelIdx = frame >= LAND ? 2 : frame >= RESULT + 10 ? 1 : 0;
    const land = sp(frame, LAND, SPRING.bouncy);
    const burst = tw(frame, LAND, 26, 0, 1, EASE.out);
    const segW = (920 - 5 * 14) / 6;
    const scaleIn = tw(frame, LAND + 4, 14);
    const goal = sp(frame, LAND + 24, SPRING.bouncy);
    const praise = tw(frame, LAND + 4, 14);

    return (
        <>
            <svg width={1080} height={1920} style={{ position: 'absolute', inset: 0, overflow: 'visible' }}>
                <defs>
                    <linearGradient id="diag-arc" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0" stopColor={C.skyLight} />
                        <stop offset="1" stopColor={C.gold} />
                    </linearGradient>
                </defs>
                <g transform={`translate(${RING.x} ${RING.y}) scale(${0.6 + 0.4 * ringPop}) translate(${-RING.x} ${-RING.y})`} opacity={Math.min(1, ringPop * 1.5)}>
                    <circle cx={RING.x} cy={RING.y} r={RING.r} fill="rgba(255,255,255,0.03)" stroke="rgba(255,255,255,0.09)" strokeWidth={26} />
                    <circle
                        cx={RING.x}
                        cy={RING.y}
                        r={RING.r}
                        fill="none"
                        stroke="url(#diag-arc)"
                        strokeWidth={26}
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
                    left: RING.x - 230,
                    top: RING.y - 170,
                    width: 460,
                    height: 340,
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
                        fontSize: 172,
                        fontWeight: 800,
                        letterSpacing: '-0.04em',
                        lineHeight: 1,
                        color: C.text,
                        transform: `scale(${frame >= LAND ? 0.7 + 0.3 * land : 1})`,
                        marginTop: 6,
                    }}
                >
                    {LEVELS[levelIdx]}
                </span>
                <span
                    style={{
                        marginTop: 4,
                        fontFamily: FONT.serif,
                        fontStyle: 'italic',
                        fontWeight: 700,
                        fontSize: 40,
                        color: C.gold,
                        whiteSpace: 'nowrap',
                        opacity: praise,
                        transform: `translateY(${(1 - praise) * 10}px)`,
                    }}
                >
                    Parfait point de départ !
                </span>
            </div>

            {/* Progression CECRL */}
            <div style={{ position: 'absolute', left: 80, top: 1318, fontFamily: FONT.sans, fontWeight: 700, fontSize: 24, letterSpacing: '0.16em', textTransform: 'uppercase', color: C.textMid, opacity: scaleIn }}>
                Progression CECRL
            </div>
            <div style={{ position: 'absolute', left: 80, top: 1376, width: 920, display: 'flex', gap: 14 }}>
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
                            <span style={{ fontFamily: FONT.sans, fontWeight: 800, fontSize: 32, color: i === 2 ? C.gold : isGoal ? C.goldLight : i < 2 ? C.text : C.textDim }}>{lv}</span>
                        </div>
                    );
                })}
            </div>
            <div
                style={{
                    position: 'absolute',
                    left: 80 + 3 * (segW + 14) + segW / 2,
                    top: 1312,
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
