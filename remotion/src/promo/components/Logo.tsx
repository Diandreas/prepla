import React, { useId } from 'react';
import { interpolate, random, useCurrentFrame } from 'remotion';
import { C, EASE, FONT, SPRING, sp, tw } from '../theme';

// Le « P » facetté de PrePla (icône Play Store), redessiné en vectoriel pour pouvoir
// animer chaque facette. Coordonnées relevées sur release/google-play/graphics/icon-512.png.
type Pt = [number, number];
const P: Record<string, Pt> = {
    T: [428, 22],
    UL: [50, 228],
    UR: [785, 230],
    RR: [785, 577],
    BL: [50, 1025],
    SB: [268, 905],
    LM: [50, 712],
    CTL: [272, 322],
    CT: [428, 243],
    CTR: [572, 325],
    CBR: [572, 505],
    CBL: [300, 657],
    N: [645, 415],
    BM: [485, 760],
    BB: [303, 862],
};

const FACETS: Array<{ pts: string[]; fill: string }> = [
    { pts: ['T', 'UL', 'CTL'], fill: '#2a6bd2' },
    { pts: ['T', 'CTL', 'CT'], fill: '#103f8c' },
    { pts: ['UL', 'LM', 'CTL'], fill: '#1f5fc6' },
    { pts: ['LM', 'SB', 'CTL'], fill: '#154897' },
    { pts: ['LM', 'BL', 'SB'], fill: '#0e2f6c' },
    { pts: ['T', 'UR', 'CTR'], fill: '#47aff7' },
    { pts: ['T', 'CTR', 'CT'], fill: '#1d7ee6' },
    { pts: ['UR', 'N', 'CTR'], fill: '#2f95f2' },
    { pts: ['UR', 'RR', 'N'], fill: '#58bffb' },
    { pts: ['CTR', 'N', 'CBR'], fill: '#0e52b8' },
    { pts: ['N', 'RR', 'CBR'], fill: '#2389ec' },
    { pts: ['CBL', 'CBR', 'BM'], fill: '#2d91f0' },
    { pts: ['CBR', 'RR', 'BM'], fill: '#1b74dc' },
    { pts: ['CBL', 'BM', 'BB'], fill: '#1866d2' },
    { pts: ['BM', 'RR', 'BB'], fill: '#1050b0' },
];

const WHITE: Pt[] = [P.CTL, P.CT, P.CTR, P.CBR, P.CBL, P.BB, [272, 892]];

const CUBE = {
    top: [422, 268] as Pt,
    ul: [297, 342] as Pt,
    ur: [548, 342] as Pt,
    c: [422, 415] as Pt,
    ll: [297, 490] as Pt,
    lr: [548, 490] as Pt,
    bottom: [422, 565] as Pt,
};

const pts = (list: Pt[]) => list.map(([x, y]) => `${x},${y}`).join(' ');
const centroid = (list: Pt[]): Pt => [list.reduce((s, p) => s + p[0], 0) / list.length, list.reduce((s, p) => s + p[1], 0) / list.length];

const VIEW = { x: 40, y: 12, w: 755, h: 1023 };
export const LOGO_RATIO = VIEW.w / VIEW.h;

export interface LogoMarkProps {
    /** Hauteur en px. */
    size: number;
    /** Image de début d'assemblage ; absent = logo déjà assemblé. */
    assembleAt?: number;
    /** Image du reflet lumineux. */
    glossAt?: number;
    style?: React.CSSProperties;
}

export const LogoMark: React.FC<LogoMarkProps> = ({ size, assembleAt, glossAt, style }) => {
    const frame = useCurrentFrame();
    const id = useId().replace(/:/g, '');
    const assembling = assembleAt !== undefined;

    const cubeDrop = assembling ? sp(frame, assembleAt + 15, SPRING.bouncy) : 1;
    const whiteIn = assembling ? tw(frame, assembleAt + 8, 14) : 1;
    const glossX = glossAt === undefined ? -2 : interpolate(frame, [glossAt, glossAt + 26], [-0.6, 1.6], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp', easing: EASE.inOutSoft });

    return (
        <svg
            width={size * LOGO_RATIO}
            height={size}
            viewBox={`${VIEW.x} ${VIEW.y} ${VIEW.w} ${VIEW.h}`}
            style={{ overflow: 'visible', ...style }}
        >
            <defs>
                <clipPath id={`clip-${id}`}>
                    {FACETS.map((f, i) => (
                        <polygon key={i} points={pts(f.pts.map((k) => P[k]))} />
                    ))}
                    <polygon points={pts(WHITE)} />
                </clipPath>
                <linearGradient id={`gloss-${id}`} x1="0" y1="0" x2="1" y2="0.35">
                    <stop offset="0" stopColor="#fff" stopOpacity="0" />
                    <stop offset="0.5" stopColor="#fff" stopOpacity="0.55" />
                    <stop offset="1" stopColor="#fff" stopOpacity="0" />
                </linearGradient>
                <linearGradient id={`cubeTop-${id}`} x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stopColor="#ffd572" />
                    <stop offset="1" stopColor="#ffb42e" />
                </linearGradient>
                <linearGradient id={`cubeLeft-${id}`} x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stopColor="#f7a41a" />
                    <stop offset="1" stopColor="#e9870a" />
                </linearGradient>
                <linearGradient id={`cubeRight-${id}`} x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stopColor="#ffbe3d" />
                    <stop offset="1" stopColor="#f59d14" />
                </linearGradient>
            </defs>

            {FACETS.map((f, i) => {
                const list = f.pts.map((k) => P[k]);
                const [cx, cy] = centroid(list);
                let transform: string | undefined;
                let opacity = 1;
                if (assembling) {
                    const s = sp(frame, assembleAt + i * 0.9, SPRING.pop);
                    const angle = random(`a${i}`) * Math.PI * 2;
                    const dist = 520 + random(`d${i}`) * 520;
                    const dx = Math.cos(angle) * dist * (1 - s);
                    const dy = Math.sin(angle) * dist * (1 - s);
                    const rot = (random(`r${i}`) - 0.5) * 260 * (1 - s);
                    const sc = 0.25 + 0.75 * s;
                    transform = `translate(${dx} ${dy}) rotate(${rot} ${cx} ${cy}) translate(${cx} ${cy}) scale(${sc}) translate(${-cx} ${-cy})`;
                    opacity = Math.min(1, s * 2.2);
                }
                return (
                    <polygon
                        key={i}
                        points={pts(list)}
                        fill={f.fill}
                        stroke={f.fill}
                        strokeWidth={2.5}
                        strokeLinejoin="round"
                        transform={transform}
                        opacity={opacity}
                    />
                );
            })}

            <polygon
                points={pts(WHITE)}
                fill="#ffffff"
                stroke="#ffffff"
                strokeWidth={2}
                strokeLinejoin="round"
                opacity={whiteIn}
                transform={`translate(422 470) scale(${0.6 + 0.4 * whiteIn}) translate(-422 -470)`}
            />

            <g transform={`translate(0 ${(1 - cubeDrop) * -760})`} opacity={assembling ? Math.min(1, cubeDrop * 3) : 1}>
                <polygon points={pts([CUBE.top, CUBE.ur, CUBE.c, CUBE.ul])} fill={`url(#cubeTop-${id})`} />
                <polygon points={pts([CUBE.ul, CUBE.c, CUBE.bottom, CUBE.ll])} fill={`url(#cubeLeft-${id})`} />
                <polygon points={pts([CUBE.c, CUBE.ur, CUBE.lr, CUBE.bottom])} fill={`url(#cubeRight-${id})`} />
                <polyline points={pts([CUBE.ul, CUBE.c, CUBE.ur])} fill="none" stroke="#fff3cf" strokeWidth={5} strokeLinejoin="round" opacity={0.85} />
                <line x1={CUBE.c[0]} y1={CUBE.c[1]} x2={CUBE.bottom[0]} y2={CUBE.bottom[1]} stroke="#ffd98a" strokeWidth={3} opacity={0.55} />
            </g>

            {glossAt !== undefined ? (
                <g clipPath={`url(#clip-${id})`}>
                    <rect
                        x={VIEW.x + glossX * VIEW.w - 260}
                        y={VIEW.y - 200}
                        width={520}
                        height={VIEW.h + 400}
                        fill={`url(#gloss-${id})`}
                        transform={`rotate(18 ${VIEW.x + glossX * VIEW.w} ${VIEW.y + VIEW.h / 2})`}
                    />
                </g>
            ) : null}
        </svg>
    );
};

/** « PrePla » + badge IA, comme dans la barre de navigation de la landing. */
export const Wordmark: React.FC<{ size: number; revealAt?: number; badgeAt?: number; style?: React.CSSProperties }> = ({
    size,
    revealAt,
    badgeAt,
    style,
}) => {
    const frame = useCurrentFrame();
    const letters = ['P', 'r', 'e', 'P', 'l', 'a'];
    const badge = badgeAt === undefined ? 1 : sp(frame, badgeAt, SPRING.bouncy);
    return (
        <div
            style={{
                display: 'flex',
                alignItems: 'center',
                gap: size * 0.16,
                fontFamily: FONT.sans,
                fontWeight: 800,
                fontSize: size,
                letterSpacing: '-0.03em',
                lineHeight: 1,
                ...style,
            }}
        >
            <span style={{ display: 'inline-flex' }}>
                {letters.map((ch, i) => {
                    const t = revealAt === undefined ? 1 : tw(frame, revealAt + i * 2, 16);
                    return (
                        <span key={i} style={{ display: 'inline-block', overflow: 'hidden', padding: '0.1em 0.02em 0.14em', margin: '-0.1em -0.02em -0.14em' }}>
                            <span
                                style={{
                                    display: 'inline-block',
                                    transform: `translateY(${(1 - t) * 120}%)`,
                                    color: i < 3 ? C.text : C.sky,
                                    backgroundImage: i < 3 ? undefined : `linear-gradient(180deg, ${C.skyLight} 0%, ${C.sky} 100%)`,
                                    WebkitBackgroundClip: i < 3 ? undefined : 'text',
                                    WebkitTextFillColor: i < 3 ? undefined : 'transparent',
                                }}
                            >
                                {ch}
                            </span>
                        </span>
                    );
                })}
            </span>
            <span
                style={{
                    display: 'inline-flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    padding: `${size * 0.08}px ${size * 0.14}px`,
                    borderRadius: size * 0.14,
                    background: 'rgba(245,166,35,0.14)',
                    border: `${Math.max(1.5, size * 0.02)}px solid rgba(245,166,35,0.45)`,
                    color: C.gold,
                    fontSize: size * 0.3,
                    fontWeight: 800,
                    letterSpacing: '0.12em',
                    transform: `scale(${badge})`,
                    opacity: Math.min(1, badge * 2),
                    alignSelf: 'center',
                }}
            >
                IA
            </span>
        </div>
    );
};
