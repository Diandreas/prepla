import React from 'react';
import { interpolate, useCurrentFrame } from 'remotion';
import { AppGif, AppIcon } from '../../components/AppAssets';
import { Move, SceneHeader } from '../../components/SceneKit';
import { Card, Pill } from '../../components/UI';
import { textWidth } from '../../components/textWidth';
import { C, EASE, FONT, SPRING, sp, tw } from '../../theme';
import { PHASE_B, PhaseB } from '../S7AI';

// Correction IA, version allemande (34–42 s) : « …, weil ich will in Deutschland studieren. »
// L'IA repère le verbe mal placé et le fait glisser en fin de phrase : après « weil », le
// verbe conjugué va à la fin. Puis la phase partagée « 30+ formats d'exercices ».

const SIZE = 50;
const LINE_H = 76;
const SPACE = textWidth(' ', 'jakarta600', SIZE);
const TYPE = { start: 14, end: 56 };
const SCAN = { start: 60, dur: 16 };
const MARK = 78;
const MOVE = { start: 86, dur: 18 };
const LAND = MOVE.start + MOVE.dur;
const EXPLAIN = 110;
const XP = 124;

const LINE1 = ['Ich', 'lerne', 'Deutsch,', 'weil', 'ich'];
// Ligne 2 : ordre fautif puis ordre corrigé ; le point suit toujours le dernier mot.
const WRONG = ['will', 'in', 'Deutschland', 'studieren'];
const RIGHT = ['in', 'Deutschland', 'studieren', 'will'];

const w = (word: string) => textWidth(word, 'jakarta600', SIZE);
const layout = (words: string[]) => {
    const xs: Record<string, number> = {};
    let x = 0;
    for (const word of words) {
        xs[word] = x;
        x += w(word) + SPACE;
    }
    return { xs, end: x - SPACE };
};
const L1 = layout(LINE1);
const BEFORE = layout(WRONG);
const AFTER = layout(RIGHT);

// Ordre de frappe (pour l'effet machine à écrire).
const TYPED_ORDER = [...LINE1.map((word) => ({ word, line: 1 })), ...WRONG.map((word) => ({ word, line: 2 })), { word: '.', line: 2 }];
const START_INDEX: number[] = [];
TYPED_ORDER.reduce((acc, t) => {
    START_INDEX.push(acc);
    return acc + t.word.length + 1;
}, 0);
const TOTAL_CHARS = TYPED_ORDER.reduce((s, t) => s + t.word.length + 1, 0);

export const DeAI: React.FC = () => {
    const frame = useCurrentFrame();
    return (
        <Move enter="zoom" exit="top" exitAt={232}>
            {frame < PHASE_B + 2 ? <PhaseA frame={frame} /> : null}
            {frame >= PHASE_B ? <PhaseB frame={frame} /> : null}
        </Move>
    );
};

const PhaseA: React.FC<{ frame: number }> = ({ frame }) => {
    const typed = Math.round(tw(frame, TYPE.start, TYPE.end - TYPE.start, 0, TOTAL_CHARS, (t) => t));
    const cardPop = sp(frame, 4, SPRING.soft);
    const out = tw(frame, PHASE_B - 12, 12, 0, 1, EASE.in);
    const scan = tw(frame, SCAN.start, SCAN.dur, 0, 1, EASE.inOutSoft);
    const scanning = frame >= SCAN.start && frame < SCAN.start + SCAN.dur + 2;
    const mark = tw(frame, MARK, 8);
    const move = tw(frame, MOVE.start, MOVE.dur, 0, 1, EASE.inOutSoft);
    const others = tw(frame, MOVE.start + 3, MOVE.dur - 2, 0, 1, EASE.inOutSoft);
    const landed = frame >= LAND;
    const land = sp(frame, LAND, SPRING.bouncy);
    const weil = tw(frame, EXPLAIN - 4, 10);
    const explain = sp(frame, EXPLAIN, SPRING.soft);
    const xp = sp(frame, XP, SPRING.bouncy);
    const arrow = Math.min(tw(frame, MARK + 2, 8), 1 - tw(frame, LAND + 6, 10));

    const visible = (index: number) => {
        const t = TYPED_ORDER[index];
        return t.word.slice(0, Math.max(0, Math.min(t.word.length, typed - START_INDEX[index])));
    };

    // Position du verbe : arc de sa place fautive jusqu'à la fin de la phrase.
    const willX = interpolate(move, [0, 1], [BEFORE.xs.will, AFTER.xs.will]);
    const willY = -Math.sin(move * Math.PI) * 78;
    const dotX = interpolate(others, [0, 1], [BEFORE.end, AFTER.end]);

    const status = landed
        ? { tone: 'green' as const, text: 'Corrigé', icon: 'check' as const }
        : { tone: 'neutral' as const, text: 'Goethe B1 · Schreiben', icon: undefined };

    return (
        <>
            <SceneHeader tag="Correction IA" lines={["L'*IA* te corrige"]} size={112} start={2} sub="…et t'explique pourquoi." exit={PHASE_B - 16} />
            <div
                style={{
                    position: 'absolute',
                    left: 80,
                    top: 680,
                    width: 920,
                    transform: `translateY(${(1 - cardPop) * 160 + out * 340}px) scale(${(0.92 + 0.08 * cardPop) * (1 - out * 0.12)})`,
                    opacity: Math.min(1, cardPop * 1.5) * (1 - out),
                }}
            >
                <Card padding={44}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: 20 }}>
                            <AppIcon name="writing" size={80} tone="blue" shadow={false} />
                            <span style={{ fontSize: 38, fontWeight: 800, letterSpacing: '-0.02em' }}>Expression écrite</span>
                        </div>
                        {scanning ? (
                            <div
                                style={{
                                    display: 'inline-flex',
                                    alignItems: 'center',
                                    gap: 10,
                                    padding: '8px 22px 8px 12px',
                                    borderRadius: 999,
                                    background: C.skyPale,
                                    border: '2px solid rgba(59,130,224,0.35)',
                                    color: '#1d63c4',
                                    fontSize: 28,
                                    fontWeight: 700,
                                }}
                            >
                                <AppGif name="loading" width={44} height={44} />
                                Analyse…
                            </div>
                        ) : (
                            <Pill tone={status.tone} icon={status.icon} size={26}>
                                {status.text}
                            </Pill>
                        )}
                    </div>

                    <div
                        style={{
                            position: 'relative',
                            marginTop: 30,
                            height: LINE_H * 2 + 64,
                            borderRadius: 28,
                            background: C.paperSoft,
                            border: `2.5px solid ${C.paperLine}`,
                            fontFamily: FONT.sans,
                            fontSize: SIZE,
                            fontWeight: 600,
                            color: C.ink,
                            overflow: 'visible',
                        }}
                    >
                        <div style={{ position: 'absolute', left: 34, top: 30, right: 34, height: LINE_H * 2 }}>
                            {/* Ligne 1 */}
                            {LINE1.map((word, i) => (
                                <span key={word} style={{ position: 'absolute', left: L1.xs[word], top: 0, lineHeight: `${LINE_H}px`, whiteSpace: 'pre' }}>
                                    {visible(i)}
                                    {word === 'weil' ? (
                                        <span
                                            style={{
                                                position: 'absolute',
                                                left: 0,
                                                bottom: 8,
                                                height: 6,
                                                borderRadius: 3,
                                                width: `${weil * 100}%`,
                                                background: C.sky,
                                            }}
                                        />
                                    ) : null}
                                </span>
                            ))}

                            {/* Ligne 2 : les mots qui se décalent */}
                            {WRONG.slice(1).map((word, k) => (
                                <span
                                    key={word}
                                    style={{
                                        position: 'absolute',
                                        left: interpolate(others, [0, 1], [BEFORE.xs[word], AFTER.xs[word]]),
                                        top: LINE_H,
                                        lineHeight: `${LINE_H}px`,
                                        whiteSpace: 'pre',
                                    }}
                                >
                                    {visible(LINE1.length + 1 + k)}
                                </span>
                            ))}
                            <span style={{ position: 'absolute', left: dotX, top: LINE_H, lineHeight: `${LINE_H}px` }}>{visible(TYPED_ORDER.length - 1)}</span>

                            {/* Flèche qui annonce le déplacement */}
                            <svg width={800} height={200} style={{ position: 'absolute', left: 0, top: LINE_H - 140, overflow: 'visible', opacity: arrow }}>
                                <path
                                    d={`M ${BEFORE.xs.will + w('will') / 2} 150 C ${BEFORE.xs.will + 120} 40, ${AFTER.xs.will - 60} 40, ${AFTER.xs.will + w('will') / 2} 136`}
                                    fill="none"
                                    stroke={C.sky}
                                    strokeWidth={5}
                                    strokeDasharray="4 12"
                                    strokeLinecap="round"
                                />
                                <path d={`M ${AFTER.xs.will + w('will') / 2 - 14} 122 L ${AFTER.xs.will + w('will') / 2} 140 L ${AFTER.xs.will + w('will') / 2 + 16} 124`} fill="none" stroke={C.sky} strokeWidth={5} strokeLinecap="round" strokeLinejoin="round" />
                            </svg>

                            {/* Le verbe conjugué */}
                            <span
                                style={{
                                    position: 'absolute',
                                    left: willX,
                                    top: LINE_H + willY,
                                    lineHeight: `${LINE_H}px`,
                                    whiteSpace: 'pre',
                                    zIndex: 2,
                                }}
                            >
                                <span
                                    style={{
                                        position: 'absolute',
                                        left: -8,
                                        right: -8,
                                        top: '14%',
                                        bottom: '10%',
                                        borderRadius: 12,
                                        background: landed ? `rgba(34,197,94,${0.18 * land})` : `rgba(239,68,68,${0.18 * mark})`,
                                        boxShadow: move > 0 && !landed ? '0 14px 30px -8px rgba(19,35,63,0.35)' : undefined,
                                    }}
                                />
                                <span
                                    style={{
                                        position: 'relative',
                                        display: 'inline-block',
                                        color: landed ? '#138a3e' : mark > 0.5 ? '#c42b2b' : C.ink,
                                        fontWeight: landed || mark > 0.5 ? 800 : 600,
                                        transform: `scale(${landed ? 0.85 + 0.15 * land : 1 + 0.08 * Math.sin(move * Math.PI)})`,
                                    }}
                                >
                                    {visible(LINE1.length)}
                                </span>
                            </span>
                        </div>

                        {scanning ? (
                            <div
                                style={{
                                    position: 'absolute',
                                    left: 0,
                                    right: 0,
                                    top: `${-20 + scan * 100}%`,
                                    height: 90,
                                    background: 'linear-gradient(180deg, rgba(59,130,224,0) 0%, rgba(59,130,224,0.28) 50%, rgba(59,130,224,0) 100%)',
                                    borderTop: '3px solid rgba(59,130,224,0.6)',
                                }}
                            />
                        ) : null}
                    </div>

                    <div
                        style={{
                            marginTop: 26,
                            display: 'flex',
                            gap: 22,
                            alignItems: 'flex-start',
                            padding: '26px 30px',
                            borderRadius: 26,
                            background: C.skyPale,
                            border: '2.5px solid rgba(59,130,224,0.25)',
                            transform: `translateY(${(1 - explain) * 30}px)`,
                            opacity: Math.min(1, explain * 1.5),
                        }}
                    >
                        <AppIcon name="lightbulb" size={68} tone="amber" shadow={false} />
                        <div style={{ fontSize: 36, fontWeight: 600, lineHeight: 1.4, color: C.ink }}>
                            Après « <b style={{ color: '#1d63c4' }}>weil</b> », le verbe conjugué se place <b style={{ color: '#138a3e' }}>à la fin</b> de la phrase.
                        </div>
                    </div>
                </Card>

                <div
                    style={{
                        position: 'absolute',
                        right: -18,
                        top: -40,
                        transform: `scale(${xp}) rotate(${(1 - xp) * 30 + 6}deg)`,
                        padding: '8px 28px 8px 8px',
                        borderRadius: 999,
                        background: `linear-gradient(135deg, ${C.goldLight}, ${C.gold})`,
                        color: '#1b1204',
                        fontFamily: FONT.sans,
                        fontWeight: 800,
                        fontSize: 40,
                        boxShadow: '0 0 40px rgba(245,166,35,0.6), 0 16px 30px rgba(0,0,0,0.4)',
                        display: 'flex',
                        alignItems: 'center',
                        gap: 10,
                    }}
                >
                    <AppIcon name="zap" size={58} tone="amber" shadow={false} />
                    +15 XP
                </div>
                {frame >= XP && frame < XP + 40 ? (
                    <div style={{ position: 'absolute', right: -70, top: -130, opacity: 1 - tw(frame, XP + 26, 12) }}>
                        <AppGif name="star" width={200} height={200} />
                    </div>
                ) : null}
            </div>
        </>
    );
};
