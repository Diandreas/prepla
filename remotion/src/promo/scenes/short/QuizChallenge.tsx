import React from 'react';
import { AbsoluteFill, interpolate, useCurrentFrame } from 'remotion';
import { AppIcon } from '../../components/AppAssets';
import { Icon } from '../../components/Icons';
import { KineticText } from '../../components/KineticText';
import { Move } from '../../components/SceneKit';
import { Stamp } from '../../components/Stamp';
import { Card, Pill, Pointer } from '../../components/UI';
import { C, EASE, FONT, SPRING, sp, tw } from '../../theme';

// Quiz de 15 s (0–10 s) : « Tu as 3 secondes. » sur un QCM de grammaire tel que l'app l'affiche
// (components/exercises/mcq.tsx), le doigt choisit le piège « était », verdict « Incorrect »,
// puis le panneau de correction de l'app (pages/exercises/player.tsx : « Réponse attendue »,
// règle en gras rouge + indice) et le pont « Et ton vrai niveau ? ».
// La question vient d'un examen blanc TEF du dépôt (mock_exams/tef/simulation_1.json, tef1_str_b_q6).

const TAP = 90;
const VERDICT = 120;
const FIX = 135;
const EXPLAIN = 150;
const RULE = 165;
const REVIEW = 210;
const BRIDGE = 228;
export const QUIZ_EXIT = 286;

const CARD = { left: 80, top: 516, width: 920, pad: 40 };
const OPTION = { top: 844, height: 88, gap: 14 };
const OPTIONS = ['soit', 'est', 'était', 'serait'];
const RIGHT = 0;
const PICK = 2;
const RING = { x: 540, y: 1385, r: 62 };

type OptionState = 'idle' | 'picked' | 'wrong' | 'right' | 'dim';

export const QuizChallenge: React.FC = () => {
    const frame = useCurrentFrame();

    // Hauteur de la carte : options (762) puis panneau de correction (614).
    const fold = sp(frame, EXPLAIN, SPRING.soft);
    const cardH = interpolate(fold, [0, 1], [762, 614]);
    const shake = 14 * Math.sin(frame * 1.9) * (frame >= VERDICT ? 1 - tw(frame, VERDICT, 10) : 0);
    const push =
        1 +
        0.02 * (1 - tw(frame, 0, 30, 0, 1, EASE.out)) +
        0.015 * tw(frame, 100, 20, 0, 1, EASE.inOutSoft) * (1 - tw(frame, VERDICT, 6)) +
        0.02 * tw(frame, EXPLAIN, 75, 0, 1, EASE.inOutSoft);
    const dim = 1 - 0.1 * tw(frame, 236, 18, 0, 1, EASE.inOutSoft);
    const flash = frame >= VERDICT ? 1 - tw(frame, VERDICT, 12) : 0;

    const state = (i: number): OptionState => {
        if (frame >= VERDICT) return i === RIGHT ? 'right' : i === PICK ? 'wrong' : 'dim';
        if (frame >= TAP && i === PICK) return 'picked';
        return 'idle';
    };
    const collapse = tw(frame, EXPLAIN, 12, 0, 1, EASE.inOutSoft);
    const review = sp(frame, REVIEW, SPRING.bouncy);

    return (
        <Move exit="zoom" exitAt={QUIZ_EXIT} exitDur={14} exitEase={EASE.inOutSoft}>
            {/* Lueur rouge du verdict */}
            <AbsoluteFill style={{ background: 'radial-gradient(120% 90% at 50% 50%, rgba(239,68,68,0) 55%, rgba(239,68,68,0.22) 100%)', opacity: flash }} />

            {/* Titres successifs */}
            <div style={{ position: 'absolute', top: 356, left: 0, right: 0 }}>
                <KineticText lines={['Tu as *3 secondes.*']} start={-18} size={104} stagger={3} exit={96} />
            </div>
            <div style={{ position: 'absolute', top: 384, left: 0, right: 0 }}>
                <KineticText lines={['PrePla t’explique *pourquoi.*']} start={EXPLAIN + 4} size={72} stagger={3} exit={BRIDGE - 14} />
            </div>
            <div style={{ position: 'absolute', top: 362, left: 0, right: 0 }}>
                <KineticText lines={['Et ton *vrai niveau*\u00a0?']} start={BRIDGE} size={100} stagger={3} wobbleAccent={3} />
            </div>

            {/* La carte d'exercice */}
            <div
                style={{
                    position: 'absolute',
                    left: CARD.left,
                    top: CARD.top,
                    width: CARD.width,
                    transform: `translateX(${shake}px) scale(${push})`,
                    filter: dim < 1 ? `brightness(${dim})` : undefined,
                }}
            >
                <Card radius={40} padding={0} style={{ height: cardH, overflow: 'hidden' }}>
                    {/* En-tête */}
                    <div style={{ position: 'absolute', left: CARD.pad, right: CARD.pad, top: 40, height: 64, display: 'flex', alignItems: 'center', gap: 18 }}>
                        <AppIcon name="courses" size={64} tone="blue" shadow={false} />
                        <span style={{ fontSize: 30, fontWeight: 800, color: '#1d63c4', letterSpacing: '-0.01em', flex: 1 }}>TCF · TEF · Grammaire</span>
                        <Pill tone="neutral" size={28}>
                            B2
                        </Pill>
                    </div>

                    {/* Phrase à trous */}
                    <Sentence frame={frame} />

                    {/* Options (mcq.tsx) */}
                    {OPTIONS.map((text, i) => (
                        <div
                            key={text}
                            style={{
                                position: 'absolute',
                                left: CARD.pad,
                                right: CARD.pad,
                                top: OPTION.top - CARD.top + i * (OPTION.height + OPTION.gap),
                                opacity: 1 - collapse,
                                transform: `translateY(${-collapse * 20}px)`,
                            }}
                        >
                            <QuizOption letter={String.fromCharCode(65 + i)} text={text} state={state(i)} press={i === PICK ? press(frame, TAP) : 0} />
                        </div>
                    ))}

                    {/* Panneau de correction (player.tsx) */}
                    {frame >= EXPLAIN ? <Correction frame={frame} open={fold} /> : null}
                </Card>
            </div>

            {/* Verdict, par-dessus la carte */}
            <div style={{ position: 'absolute', top: 366, left: 0, right: 0, display: 'flex', justifyContent: 'center' }}>
                <Stamp text="Incorrect" icon="x" tone="red" at={VERDICT} exitAt={EXPLAIN - 6} />
            </div>

            {/* « Ton erreur reviendra en révision » */}
            {frame >= REVIEW ? (
                <div
                    style={{
                        position: 'absolute',
                        top: 1170,
                        left: 0,
                        right: 0,
                        display: 'flex',
                        justifyContent: 'center',
                        transform: `scale(${review})`,
                        opacity: Math.min(1, review * 2),
                        filter: dim < 1 ? `brightness(${dim})` : undefined,
                    }}
                >
                    <Pill tone="sky" size={32}>
                        <span style={{ display: 'inline-flex', transform: `rotate(${tw(frame, REVIEW, 20, 0, 360, EASE.out)}deg)` }}>
                            <Icon name="rotate" size={34} color="#1d63c4" stroke={2.6} />
                        </span>
                        Ton erreur reviendra en révision
                    </Pill>
                </div>
            ) : null}

            <Countdown frame={frame} />

            <Pointer
                keys={[
                    { f: 62, x: 780, y: 1290 },
                    { f: 76, x: 600, y: OPTION.top + OPTION.height / 2 + RIGHT * (OPTION.height + OPTION.gap) },
                    { f: TAP, x: 600, y: OPTION.top + OPTION.height / 2 + PICK * (OPTION.height + OPTION.gap), tap: true },
                ]}
                hideAt={TAP + 14}
            />
        </Move>
    );
};

const press = (frame: number, at: number) => interpolate(frame, [at - 3, at, at + 6], [0, 1, 0], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });

/** « Bien qu’il ___ fatigué, / il a continué à travailler. » — la réponse se pose dans le blanc. */
const Sentence: React.FC<{ frame: number }> = ({ frame }) => {
    const beat = frame < TAP ? 1 + 0.05 * Math.max(0, 1 - (frame % 15) / 6) : 1;
    const picked = tw(frame, TAP, 8, 0, 1, EASE.out);
    const wrong = frame >= VERDICT;
    const strike = tw(frame, VERDICT + 2, 8, 0, 1, EASE.inOutSoft);
    // « était » (barré à VERDICT + 10) libère le blanc juste avant que « soit » s'y pose.
    const away = tw(frame, FIX - 2, 5, 0, 1, EASE.out);
    const fixed = sp(frame, FIX + 2, SPRING.bouncy);
    const isFixed = frame >= FIX + 2;
    const lineColor = isFixed ? C.green : C.sky;
    return (
        <div
            style={{
                position: 'absolute',
                left: CARD.pad,
                right: CARD.pad,
                top: 652 - CARD.top,
                fontSize: 60,
                fontWeight: 800,
                lineHeight: 1.3,
                letterSpacing: '-0.02em',
                color: C.ink,
            }}
        >
            Bien qu’il{' '}
            <span style={{ position: 'relative', display: 'inline-block', width: 220, height: '1em', verticalAlign: 'baseline' }}>
                <span
                    style={{
                        position: 'absolute',
                        left: 0,
                        right: 0,
                        bottom: -10,
                        borderBottom: `5px ${isFixed ? 'solid' : 'dashed'} ${lineColor}`,
                        transform: `scaleX(${beat})`,
                    }}
                />
                {frame >= TAP && away < 1 ? (
                    <span
                        style={{
                            position: 'absolute',
                            left: 0,
                            right: 0,
                            bottom: -2,
                            textAlign: 'center',
                            lineHeight: 1,
                            color: wrong ? '#c42b2b' : C.sky,
                            opacity: picked * (1 - away),
                            transform: `translateY(${(1 - picked) * 40 - away * 70}px)`,
                        }}
                    >
                        <span style={{ position: 'relative', padding: '0 6px', borderRadius: 10, background: wrong ? 'rgba(239,68,68,0.16)' : undefined }}>
                            était
                            <span
                                style={{
                                    position: 'absolute',
                                    left: 0,
                                    top: '52%',
                                    height: 6,
                                    width: `${strike * 100}%`,
                                    borderRadius: 3,
                                    background: C.red,
                                }}
                            />
                        </span>
                    </span>
                ) : null}
                {isFixed ? (
                    <span
                        style={{
                            position: 'absolute',
                            left: 0,
                            right: 0,
                            bottom: -2,
                            textAlign: 'center',
                            lineHeight: 1,
                            color: '#138a3e',
                            transform: `scale(${0.6 + 0.4 * fixed})`,
                            opacity: Math.min(1, fixed * 2) * away,
                        }}
                    >
                        soit
                    </span>
                ) : null}
            </span>{' '}
            fatigué,
            <br />
            il a continué à travailler.
        </div>
    );
};

/** Une option du QCM, aux couleurs de mcq.tsx (bleu choisi, rouge faux, vert juste, autres estompées). */
const QuizOption: React.FC<{ letter: string; text: string; state: OptionState; press: number }> = ({ letter, text, state, press }) => {
    const colors = {
        idle: { border: '#e5e7eb', bg: '#ffffff', badge: '#eef2f7', badgeFg: C.inkSoft, shadow: '#e5e7eb' },
        picked: { border: C.sky, bg: C.skyPale, badge: C.sky, badgeFg: '#fff', shadow: C.sky },
        wrong: { border: '#f87171', bg: '#fef2f2', badge: C.red, badgeFg: '#fff', shadow: undefined },
        right: { border: '#34d399', bg: '#ecfdf5', badge: '#10b981', badgeFg: '#fff', shadow: undefined },
        dim: { border: '#e5e7eb', bg: '#ffffff', badge: '#eef2f7', badgeFg: C.inkSoft, shadow: undefined },
    }[state];
    return (
        <div
            style={{
                height: OPTION.height,
                borderRadius: 22,
                border: `3.5px solid ${colors.border}`,
                background: colors.bg,
                boxShadow: colors.shadow ? `0 ${5 - press * 4}px 0 0 ${colors.shadow}` : undefined,
                display: 'flex',
                alignItems: 'center',
                gap: 22,
                padding: '0 24px',
                boxSizing: 'border-box',
                fontSize: 44,
                fontWeight: 700,
                color: C.ink,
                opacity: state === 'dim' ? 0.5 : 1,
                transform: `translateY(${press * 4}px) scale(${1 - press * 0.03})`,
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
                {state === 'right' ? (
                    <Icon name="check" size={30} color="#fff" stroke={3.4} />
                ) : state === 'wrong' ? (
                    <Icon name="x" size={28} color="#fff" stroke={3.4} />
                ) : (
                    letter
                )}
            </span>
            {text}
        </div>
    );
};

/** Panneau de correction de l'app : réponse attendue, règle en rouge et indice. */
const Correction: React.FC<{ frame: number; open: number }> = ({ frame, open }) => {
    const row1 = tw(frame, EXPLAIN + 6, 10);
    const rule = tw(frame, RULE, 14, 0, 1, EASE.out);
    const hint = tw(frame, RULE + 7, 14, 0, 1, EASE.out);
    return (
        <div
            style={{
                position: 'absolute',
                left: CARD.pad,
                right: CARD.pad,
                top: OPTION.top - CARD.top,
                height: 246 * open,
                overflow: 'hidden',
                borderRadius: 26,
                border: '3px solid #fecaca',
                background: '#ffffff',
                boxSizing: 'border-box',
            }}
        >
            <div style={{ position: 'absolute', left: 30, right: 30, top: 24 }}>
                <div style={{ fontSize: 36, fontWeight: 600, color: C.ink, opacity: row1 }}>
                    Réponse attendue : <b style={{ fontWeight: 800 }}>soit</b>
                </div>
                <div style={{ position: 'relative', height: 92 }}>
                    <div
                        style={{
                            position: 'absolute',
                            top: 8,
                            fontSize: 64,
                            fontWeight: 800,
                            letterSpacing: '-0.02em',
                            color: '#b91c1c',
                            whiteSpace: 'nowrap',
                            opacity: rule,
                            transform: `translateY(${(1 - rule) * 30}px)`,
                        }}
                    >
                        Bien que + subjonctif
                    </div>
                </div>
                <div style={{ fontSize: 38, fontWeight: 600, color: C.inkSoft, opacity: hint, transform: `translateY(${(1 - hint) * 20}px)` }}>
                    Même dans un récit au passé.
                </div>
            </div>
        </div>
    );
};

/** Anneau 3-2-1 sous la carte (le défi de la vidéo, pas un chrono de l'app). */
const Countdown: React.FC<{ frame: number }> = ({ frame }) => {
    const remaining = 1 - tw(frame, 0, TAP, 0, 1, (t) => t);
    const out = tw(frame, TAP, 10, 0, 1, EASE.in);
    if (out >= 1) return null;
    const n = frame < 30 ? 3 : frame < 60 ? 2 : 1;
    const at = n === 3 ? 0 : n === 2 ? 30 : 60;
    const popIn = n === 3 ? 1 : sp(frame, at, SPRING.pop);
    const pulse = 1 + 0.08 * Math.max(0, 1 - (frame - at) / 8);
    const circ = 2 * Math.PI * RING.r;
    const end = -Math.PI / 2 + remaining * Math.PI * 2;
    const color = n === 1 ? '#f2683a' : C.gold;
    return (
        <div style={{ position: 'absolute', left: RING.x - 90, top: RING.y - 90, width: 180, height: 180, transform: `scale(${1 - out})`, opacity: 1 - out }}>
            <svg width={180} height={180} style={{ position: 'absolute', inset: 0, overflow: 'visible' }}>
                <circle cx={90} cy={90} r={RING.r} fill="rgba(6,12,24,0.35)" stroke="rgba(255,255,255,0.08)" strokeWidth={10} />
                <circle
                    cx={90}
                    cy={90}
                    r={RING.r}
                    fill="none"
                    stroke={color}
                    strokeWidth={10}
                    strokeLinecap="round"
                    strokeDasharray={`${circ * remaining} ${circ}`}
                    transform="rotate(-90 90 90)"
                    style={{ filter: `drop-shadow(0 0 10px ${color})` }}
                />
                {remaining > 0.01 ? <circle cx={90 + Math.cos(end) * RING.r} cy={90 + Math.sin(end) * RING.r} r={9} fill={color} /> : null}
            </svg>
            <div
                style={{
                    position: 'absolute',
                    inset: 0,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    fontFamily: FONT.serif,
                    fontStyle: 'italic',
                    fontWeight: 700,
                    fontSize: 92,
                    lineHeight: 1,
                    fontVariantNumeric: 'lining-nums',
                    color: n === 1 ? '#ff8a5c' : C.text,
                    transform: `scale(${(0.6 + 0.4 * popIn) * pulse})`,
                    opacity: Math.min(1, popIn * 2),
                }}
            >
                {n}
            </div>
        </div>
    );
};
