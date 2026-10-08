import { noise2D } from '@remotion/noise';
import React from 'react';
import { AbsoluteFill, interpolate, useCurrentFrame } from 'remotion';
import { Icon } from '../../components/Icons';
import { KineticText } from '../../components/KineticText';
import { textWidth } from '../../components/textWidth';
import { C, EASE, FONT, SPRING, sp, tw } from '../../theme';

// Accroche allemande (0–4 s) : « [der / die / das] Mädchen ? » — l'article hésite sans jamais
// se poser (la bonne réponse arrive au test de placement), puis « Tu hésites encore ? ».
// Code couleur des manuels d'allemand : der bleu, die rouge, das vert.

export const ARTICLE_COLORS: Record<string, string> = { der: '#4c95f0', die: '#f0565b', das: '#2fcf73' };
const SLOT: Array<{ at: number; word: 'der' | 'die' | 'das' }> = [
    { at: 8, word: 'der' },
    { at: 12, word: 'die' },
    { at: 16, word: 'das' },
    { at: 20, word: 'der' },
    { at: 25, word: 'die' },
    { at: 30, word: 'das' },
    { at: 36, word: 'der' },
    { at: 43, word: 'die' },
    { at: 51, word: 'das' },
    { at: 60, word: 'der' },
    { at: 70, word: 'die' },
    { at: 82, word: 'das' },
    { at: 94, word: 'der' },
];
export const DE_HOOK_SLOT_FRAMES = SLOT.map((s) => s.at);
const slotAt = (frame: number) => SLOT.reduce((current, slot, i) => (frame >= slot.at ? i : current), 0);

const ARTICLE_SIZE = 120;
const NOUN = 'Mädchen';
const NOUN_SIZE = 132;
const ROW_Y = 820;
const PILL_PAD = 92;
const GAP = 30;

export const DeHook: React.FC = () => {
    const frame = useCurrentFrame();
    const current = slotAt(frame);
    const cur = SLOT[current];
    const next = SLOT[current + 1];
    const span = next ? next.at - cur.at : 8;
    const moveT = tw(frame, cur.at, Math.max(3, span * 0.7), 0, 1, EASE.out);
    const width = (i: number) => textWidth(SLOT[i].word, 'jakarta800', ARTICLE_SIZE, -0.035) + PILL_PAD;
    const pillW = interpolate(moveT, [0, 1], [current > 0 ? width(current - 1) : width(0), width(current)]);
    const color = ARTICLE_COLORS[cur.word];

    const open = tw(frame, 6, 10);
    const out = tw(frame, 98, 12, 0, 1, EASE.in);
    const nounW = textWidth(NOUN, 'jakarta800', NOUN_SIZE, -0.035);
    const markW = 80;
    const rowW = pillW + GAP + nounW + 10 + markW;
    const rowLeft = 540 - rowW / 2;
    const nounIn = tw(frame, 4, 16);
    const mark = sp(frame, 20, SPRING.bouncy);
    const wobble = noise2D('mark', frame * 0.08, 0) * 10;

    // Compte à rebours de l'examen, comme dans l'accroche française.
    const day = Math.max(16, 30 - Math.max(0, Math.floor((frame - 12) / 7.5)));
    const chipIn = sp(frame, 6, SPRING.pop);
    const urgent = day <= 20;

    return (
        <AbsoluteFill>
            {/* DER DIE DAS en filigrane */}
            {(['der', 'die', 'das'] as const).map((word, i) => (
                <div
                    key={word}
                    style={{
                        position: 'absolute',
                        top: 380 + i * 420,
                        left: -60 + Math.sin(frame / 40 + i) * 30 + (i % 2 ? 260 : 0),
                        fontFamily: FONT.sans,
                        fontWeight: 800,
                        fontSize: 360,
                        letterSpacing: '-0.04em',
                        color: 'transparent',
                        WebkitTextStroke: `3px ${ARTICLE_COLORS[word]}`,
                        opacity: 0.1 * tw(frame, i * 4, 20) * (1 - out),
                        textTransform: 'uppercase',
                        whiteSpace: 'nowrap',
                    }}
                >
                    {word}
                </div>
            ))}

            {/* Goethe B1 · J-30 */}
            <div
                style={{
                    position: 'absolute',
                    top: 330,
                    left: 0,
                    right: 0,
                    display: 'flex',
                    justifyContent: 'center',
                    opacity: Math.min(1, chipIn * 1.5) * (1 - out),
                    transform: `scale(${0.6 + 0.4 * chipIn}) translateY(${-out * 20}px)`,
                }}
            >
                <div
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: 18,
                        padding: '18px 34px',
                        borderRadius: 999,
                        background: urgent ? 'rgba(242,104,58,0.16)' : 'rgba(245,166,35,0.14)',
                        border: `2.5px solid ${urgent ? 'rgba(242,104,58,0.65)' : 'rgba(245,166,35,0.55)'}`,
                        color: urgent ? '#ff8a5c' : C.gold,
                        fontFamily: FONT.sans,
                        fontWeight: 800,
                        fontSize: 44,
                    }}
                >
                    <Icon name="calendar" size={44} color="currentColor" stroke={2.4} />
                    <span>Goethe B1</span>
                    <span style={{ opacity: 0.6 }}>·</span>
                    <span style={{ fontVariantNumeric: 'tabular-nums' }}>J-{day}</span>
                </div>
            </div>

            {/* [article] Mädchen ? */}
            <div style={{ position: 'absolute', top: ROW_Y, left: rowLeft, height: 190, display: 'flex', alignItems: 'center', gap: 0 }}>
                <div
                    style={{
                        position: 'relative',
                        width: pillW,
                        height: 176,
                        borderRadius: 999,
                        border: `5px solid ${color}`,
                        background: `${color}22`,
                        boxShadow: `0 0 60px ${color}44`,
                        transform: `scaleX(${open * (1 - out * 0.4)})`,
                        opacity: open * (1 - out),
                        overflow: 'hidden',
                    }}
                >
                    {frame >= SLOT[0].at ? (
                        <>
                            {current > 0 && moveT < 1 ? <Article word={SLOT[current - 1].word} y={-moveT * 110} blur={moveT * 10} /> : null}
                            <Article word={cur.word} y={(1 - moveT) * 110} blur={(1 - moveT) * 10} />
                        </>
                    ) : null}
                </div>
                <div style={{ width: GAP }} />
                <span style={{ display: 'inline-block', overflow: 'hidden', padding: '0.18em 0.06em 0.22em', margin: '-0.18em -0.06em -0.22em', visibility: nounIn > 0.001 && out < 0.999 ? 'visible' : 'hidden' }}>
                    <span
                        style={{
                            display: 'inline-block',
                            fontFamily: FONT.sans,
                            fontWeight: 800,
                            fontSize: NOUN_SIZE,
                            letterSpacing: '-0.035em',
                            color: C.text,
                            transform: `translateY(${(1 - nounIn) * 130 - out * 140}%)`,
                        }}
                    >
                        {NOUN}
                    </span>
                </span>
                <span
                    style={{
                        marginLeft: 10,
                        width: markW,
                        fontFamily: FONT.serif,
                        fontStyle: 'italic',
                        fontWeight: 700,
                        fontSize: 190,
                        lineHeight: 1,
                        color: C.gold,
                        transform: `scale(${mark * (1 - out)}) rotate(${wobble}deg)`,
                        display: 'inline-block',
                    }}
                >
                    ?
                </span>
            </div>

            {/* Tu hésites encore ? */}
            <div style={{ position: 'absolute', top: 1090, left: 0, right: 0 }}>
                <KineticText lines={['Tu *hésites*', 'encore ?']} start={34} size={112} stagger={4} exit={94} wobbleAccent={4} />
            </div>
        </AbsoluteFill>
    );
};

const Article: React.FC<{ word: 'der' | 'die' | 'das'; y: number; blur: number }> = ({ word, y, blur }) => (
    <div
        style={{
            position: 'absolute',
            inset: 0,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            transform: `translateY(${y}%)`,
            filter: blur > 0.2 ? `blur(${blur}px)` : undefined,
            fontFamily: FONT.sans,
            fontWeight: 800,
            fontSize: ARTICLE_SIZE,
            letterSpacing: '-0.035em',
            color: ARTICLE_COLORS[word],
        }}
    >
        {word}
    </div>
);
