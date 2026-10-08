import { evolvePath, getLength, getPointAtLength } from '@remotion/paths';
import React from 'react';
import { interpolate, useCurrentFrame } from 'remotion';
import { AppGif, AppIcon, Fox, type AppIconName, type AppTone } from '../components/AppAssets';
import { Icon } from '../components/Icons';
import { Caption, Move, SceneHeader } from '../components/SceneKit';
import { C, EASE, FONT, SPRING, env, sp, tw } from '../theme';

// Étape 3 (26–34 s) : le parcours se dessine étape par étape, avec les icônes de l'app.
// L'étape du jour s'affiche comme la carte « Ta prochaine mission » de l'accueil, avec le
// renard-guide. Puis une révision ciblée s'insère : les étapes suivantes lui font de la place.

const SLOTS = Array.from({ length: 8 }, (_, i) => ({ x: i % 2 === 0 ? 640 : 900, y: 1452 - i * 96 }));
const PATH = SLOTS.reduce((d, p, i) => {
    if (i === 0) return `M ${p.x} ${p.y}`;
    const prev = SLOTS[i - 1];
    const h = (prev.y - p.y) / 2;
    return `${d} C ${prev.x} ${prev.y - h}, ${p.x} ${p.y + h}, ${p.x} ${p.y}`;
}, '');
const LEN = getLength(PATH);
const LAST = SLOTS.length - 1;
const at = (slot: number) => getPointAtLength(PATH, (Math.max(0, Math.min(LAST, slot)) / LAST) * LEN);

const DRAW = { start: 8, dur: 70 };
const CURRENT_SLOT = 3;
const INSERT = 128;
const MISSION_AT = 92;

type Kind = 'done' | 'current' | 'next' | 'goal';
type Node = { icon: AppIconName; from: number; to: number; kind: Kind };
const NODES: Node[] = [
    { icon: 'courses', from: 0, to: 0, kind: 'done' },
    { icon: 'listening', from: 1, to: 1, kind: 'done' },
    { icon: 'writing', from: 2, to: 2, kind: 'done' },
    { icon: 'courses', from: 3, to: 3, kind: 'current' },
    { icon: 'speaking', from: 4, to: 5, kind: 'next' },
    { icon: 'message-square', from: 5, to: 6, kind: 'next' },
    { icon: 'trophy', from: 6, to: 7, kind: 'goal' },
];

export const S6Path: React.FC = () => {
    const frame = useCurrentFrame();
    const drawBase = tw(frame, DRAW.start, DRAW.dur, 0, 1, EASE.inOutSoft) * (6 / LAST);
    const extend = tw(frame, INSERT + 2, 18, 0, 1, EASE.inOutSoft) * (1 / LAST);
    const drawn = drawBase + extend;
    const solid = evolvePath(Math.min(drawn, CURRENT_SLOT / LAST), PATH);
    const ghost = evolvePath(drawn, PATH);
    const shift = tw(frame, INSERT, 20, 0, 1, EASE.inOutSoft);
    const insertPop = sp(frame, INSERT + 2, SPRING.bouncy);
    const cam = interpolate(frame, [0, 240], [1, 1.04]);

    const current = at(CURRENT_SLOT);
    const inserted = at(4);
    const goal = at(interpolate(shift, [0, 1], [6, 7]));

    return (
        <Move enter="zoom" exit="zoom" exitAt={232} origin="50% 52%">
            <SceneHeader tag="Étape 3 / 3" lines={['Suis ton', '*parcours*']} start={4} exit={226} />

            <div style={{ position: 'absolute', inset: 0, transform: `scale(${cam})`, transformOrigin: '540px 1090px' }}>
                <svg width={1080} height={1920} style={{ position: 'absolute', inset: 0, overflow: 'visible' }}>
                    <defs>
                        <linearGradient id="path-solid" x1="0" y1="1" x2="0" y2="0">
                            <stop offset="0" stopColor={C.sky} />
                            <stop offset="1" stopColor={C.skyLight} />
                        </linearGradient>
                        <mask id="path-reveal" maskUnits="userSpaceOnUse" x="0" y="0" width="1080" height="1920">
                            <path d={PATH} fill="none" stroke="#fff" strokeWidth={40} strokeDasharray={ghost.strokeDasharray} strokeDashoffset={ghost.strokeDashoffset} />
                        </mask>
                    </defs>
                    <path d={PATH} fill="none" stroke="rgba(255,255,255,0.28)" strokeWidth={10} strokeLinecap="round" strokeDasharray="1 24" mask="url(#path-reveal)" />
                    <path
                        d={PATH}
                        fill="none"
                        stroke="url(#path-solid)"
                        strokeWidth={16}
                        strokeLinecap="round"
                        strokeDasharray={solid.strokeDasharray}
                        strokeDashoffset={solid.strokeDashoffset}
                        style={{ filter: 'drop-shadow(0 0 14px rgba(59,130,224,0.75))' }}
                    />
                    <Connector frame={frame} from={MISSION_AT} to={INSERT - 4} node={current} y={current.y} />
                    <Connector frame={frame} from={INSERT + 12} to={300} node={inserted} y={inserted.y} />
                    <Connector frame={frame} from={INSERT + 30} to={300} node={goal} y={goal.y} />
                </svg>

                {NODES.map((node, i) => {
                    const slot = interpolate(shift, [0, 1], [node.from, node.to]);
                    const p = at(slot);
                    const appearAt = DRAW.start + (node.from / 6) * DRAW.dur * 0.92;
                    const appear = drawBase >= node.from / LAST - 0.004 ? sp(frame, appearAt, SPRING.pop) : 0;
                    return <PathNode key={i} x={p.x} y={p.y} icon={node.icon} kind={node.kind} appear={appear} frame={frame} checkAt={appearAt + 6} />;
                })}

                {frame >= INSERT ? (
                    <div
                        style={{
                            position: 'absolute',
                            left: inserted.x - 66,
                            top: inserted.y - 66,
                            width: 132,
                            height: 132,
                            borderRadius: '34%',
                            border: `4px dashed ${C.gold}`,
                            display: 'grid',
                            placeItems: 'center',
                            transform: `scale(${insertPop}) rotate(${(1 - insertPop) * -120}deg)`,
                            boxShadow: '0 0 50px rgba(245,166,35,0.5)',
                        }}
                    >
                        <AppIcon name="sparkles" size={104} tone="amber" />
                    </div>
                ) : null}

                <MissionCallout frame={frame} from={MISSION_AT} to={INSERT - 4} y={current.y} />
                <Callout frame={frame} from={INSERT + 12} to={300} y={inserted.y} label="Ajouté pour toi" title="Révision ciblée" meta="d'après tes erreurs" tone="gold" />
                <Callout frame={frame} from={INSERT + 30} to={300} y={goal.y} label="Objectif" title="Examen blanc" meta="" tone="dark" />
            </div>

            <Caption top={1530} start={152} exit={226}>
                Ton plan s'adapte à tes erreurs.
            </Caption>
        </Move>
    );
};

const TONE: Record<Kind, AppTone> = { done: 'blue', current: 'amber', next: 'neutral', goal: 'amber' };

const PathNode: React.FC<{ x: number; y: number; icon: AppIconName; kind: Kind; appear: number; frame: number; checkAt: number }> = ({
    x,
    y,
    icon,
    kind,
    appear,
    frame,
    checkAt,
}) => {
    const size = kind === 'current' ? 128 : 108;
    const pulse = kind === 'current' ? (frame % 15) / 15 : 0;
    const ring = { done: C.sky, current: C.gold, next: 'rgba(255,255,255,0.18)', goal: 'rgba(245,166,35,0.75)' }[kind];
    return (
        <div style={{ position: 'absolute', left: x - size / 2, top: y - size / 2, width: size, height: size, transform: `scale(${appear})` }}>
            {kind === 'current' ? (
                <div
                    style={{
                        position: 'absolute',
                        inset: -8,
                        borderRadius: '36%',
                        border: `4px solid ${C.gold}`,
                        transform: `scale(${1 + pulse * 0.5})`,
                        opacity: (1 - pulse) * 0.85,
                    }}
                />
            ) : null}
            <div
                style={{
                    position: 'absolute',
                    inset: -7,
                    borderRadius: '36%',
                    border: `4px ${kind === 'goal' ? 'dashed' : 'solid'} ${ring}`,
                    boxShadow: kind === 'current' ? '0 0 50px rgba(245,166,35,0.65)' : kind === 'done' ? '0 0 30px rgba(59,130,224,0.45)' : undefined,
                }}
            />
            {kind === 'goal' ? (
                <div
                    style={{
                        width: size,
                        height: size,
                        borderRadius: '30%',
                        border: '2px solid #fff',
                        background: 'linear-gradient(145deg, #fff 20%, #fff0cd)',
                        display: 'grid',
                        placeItems: 'center',
                        boxShadow: '0 14px 34px rgba(3,9,20,0.35)',
                        boxSizing: 'border-box',
                    }}
                >
                    <AppGif name="Trophy" width={size * 0.8} height={size * 0.8} />
                </div>
            ) : (
                <AppIcon name={icon} size={size} tone={TONE[kind]} style={{ opacity: kind === 'next' ? 0.62 : 1 }} />
            )}
            {kind === 'done' ? (
                <div
                    style={{
                        position: 'absolute',
                        right: -14,
                        bottom: -14,
                        width: 46,
                        height: 46,
                        borderRadius: '50%',
                        background: C.green,
                        border: '4px solid #0b1322',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        transform: `scale(${sp(frame, checkAt, SPRING.bouncy)})`,
                    }}
                >
                    <Icon name="check" size={24} color="#fff" stroke={3.6} />
                </div>
            ) : null}
        </div>
    );
};

const CALLOUT_RIGHT = 520;

const Connector: React.FC<{ frame: number; from: number; to: number; node: { x: number; y: number }; y: number }> = ({ frame, from, to, node, y }) => {
    const t = env(frame, from + 4, 12, to, 8);
    if (t <= 0) return null;
    const x1 = CALLOUT_RIGHT + 6;
    const x2 = node.x - 76;
    return <line x1={x1} y1={y} x2={x1 + (x2 - x1) * t} y2={node.y} stroke="rgba(255,255,255,0.45)" strokeWidth={3} strokeDasharray="2 10" strokeLinecap="round" />;
};

/** L'étape du jour, comme la carte « Ta prochaine mission » de l'accueil (daily-mission.tsx). */
const MissionCallout: React.FC<{ frame: number; from: number; to: number; y: number }> = ({ frame, from, to, y }) => {
    if (frame < from) return null;
    const pop = sp(frame, from, SPRING.pop);
    const fox = sp(frame, from + 8, SPRING.bouncy);
    const out = tw(frame, to, 8, 0, 1, EASE.in);
    if (out >= 1) return null;
    return (
        <div
            style={{
                position: 'absolute',
                top: y,
                right: 1080 - CALLOUT_RIGHT,
                width: 440,
                transform: `translateY(-50%) translateX(${(1 - pop) * -50 - out * 30}px) scale(${(0.75 + 0.25 * pop) * (1 - out * 0.1)})`,
                transformOrigin: '100% 50%',
                opacity: Math.min(1, pop * 1.6) * (1 - out),
            }}
        >
            <div
                style={{
                    position: 'absolute',
                    left: 4,
                    bottom: '100%',
                    marginBottom: -14,
                    transform: `translateY(${(1 - fox) * 60}px) scale(${Math.min(1, fox)})`,
                    transformOrigin: '50% 100%',
                    filter: 'drop-shadow(0 12px 18px rgba(0,0,0,0.45))',
                }}
            >
                <Fox height={190} />
            </div>
            <div
                style={{
                    position: 'relative',
                    overflow: 'hidden',
                    padding: '26px 30px',
                    borderRadius: 30,
                    background: 'linear-gradient(135deg, #193b67 0%, #173458 50%, #14243e 100%)',
                    border: '2px solid rgba(106,170,246,0.4)',
                    boxShadow: '0 30px 60px -20px rgba(0,0,0,0.75)',
                    fontFamily: FONT.sans,
                    color: '#fff',
                }}
            >
                <div style={{ position: 'absolute', right: -60, top: -80, width: 240, height: 240, borderRadius: '50%', border: '30px solid rgba(255,255,255,0.04)' }} />
                <div style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 22, fontWeight: 700, color: '#c7dcff' }}>
                    <Icon name="sparkles" size={24} color="#c7dcff" stroke={2.2} />
                    Ta prochaine mission
                </div>
                <div style={{ marginTop: 6, fontSize: 44, fontWeight: 800, letterSpacing: '-0.03em' }}>Le subjonctif</div>
                <div
                    style={{
                        marginTop: 16,
                        display: 'inline-flex',
                        alignItems: 'center',
                        gap: 10,
                        padding: '14px 22px',
                        borderRadius: 16,
                        background: '#fff',
                        color: '#193b67',
                        fontSize: 26,
                        fontWeight: 800,
                    }}
                >
                    Continuer ma séance
                    <Icon name="arrowRight" size={26} color="#193b67" stroke={3} />
                </div>
            </div>
        </div>
    );
};

const Callout: React.FC<{
    frame: number;
    from: number;
    to: number;
    y: number;
    label: string;
    title: string;
    meta: string;
    tone: 'gold' | 'dark';
}> = ({ frame, from, to, y, label, title, meta, tone }) => {
    if (frame < from) return null;
    const pop = sp(frame, from, SPRING.pop);
    const out = tw(frame, to, 8, 0, 1, EASE.in);
    if (out >= 1) return null;
    const styles = {
        gold: { bg: 'rgba(40,31,14,0.95)', fg: C.text, label: C.gold, meta: C.textMid, border: 'rgba(245,166,35,0.75)' },
        dark: { bg: 'rgba(21,35,61,0.95)', fg: C.text, label: C.skyLight, meta: C.textMid, border: 'rgba(255,255,255,0.18)' },
    }[tone];
    return (
        <div
            style={{
                position: 'absolute',
                top: y,
                right: 1080 - CALLOUT_RIGHT,
                transform: `translateY(-50%) translateX(${(1 - pop) * -50 - out * 30}px) scale(${(0.75 + 0.25 * pop) * (1 - out * 0.1)})`,
                transformOrigin: '100% 50%',
                opacity: Math.min(1, pop * 1.6) * (1 - out),
                display: 'flex',
                alignItems: 'center',
                gap: 20,
                padding: '20px 28px 20px 20px',
                borderRadius: 28,
                background: styles.bg,
                border: `2.5px solid ${styles.border}`,
                boxShadow: '0 30px 60px -20px rgba(0,0,0,0.7)',
                fontFamily: FONT.sans,
                color: styles.fg,
                whiteSpace: 'nowrap',
            }}
        >
            <AppIcon name={tone === 'gold' ? 'sparkles' : 'trophy'} size={76} tone="amber" shadow={false} />
            <div>
                <div style={{ fontSize: 22, fontWeight: 800, letterSpacing: '0.14em', textTransform: 'uppercase', color: styles.label }}>{label}</div>
                <div style={{ marginTop: 4, fontSize: 40, fontWeight: 800, letterSpacing: '-0.02em' }}>{title}</div>
                {meta ? <div style={{ marginTop: 2, fontSize: 26, fontWeight: 600, color: styles.meta }}>{meta}</div> : null}
            </div>
        </div>
    );
};
