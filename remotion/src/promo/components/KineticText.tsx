import { noise2D } from '@remotion/noise';
import React from 'react';
import { useCurrentFrame } from 'remotion';
import { C, EASE, FONT, tw } from '../theme';

type Piece = { text: string; accent: boolean };
/** Un « mot » animé comme un bloc : morceaux collés sans espace (ex. « hasard » + « ? »). */
type Group = Piece[];

/**
 * Découpe une ligne en groupes de mots. Les passages entre astérisques sont des mots
 * d'accent (serif italique doré, la signature de la landing PrePla) :
 * "Tu révises *au hasard* ?" → [Tu] [révises] [au*] [hasard* ?]
 * Un espace insécable (U+00A0 ou U+202F) colle deux morceaux dans le même groupe.
 */
export function splitGroups(line: string): Group[] {
    const groups: Group[] = [];
    let current: Group = [];
    line.split('*').forEach((segment, index) => {
        const accent = index % 2 === 1;
        const tokens = segment.split(' ');
        tokens.forEach((token, ti) => {
            if (ti > 0 && current.length) {
                groups.push(current);
                current = [];
            }
            if (token) current.push({ text: token, accent });
        });
    });
    if (current.length) groups.push(current);
    return groups;
}

export type RevealStyle = 'mask' | 'blur' | 'rise';

export interface KineticTextProps {
    lines: string[];
    /** Image (relative à la séquence) où commence l'apparition. */
    start: number;
    size: number;
    stagger?: number;
    /** Durée de l'apparition de chaque mot. */
    dur?: number;
    /** Image où commence la sortie (aucune sortie si absent). */
    exit?: number;
    exitStagger?: number;
    color?: string;
    accentColor?: string;
    accentScale?: number;
    weight?: number;
    align?: 'center' | 'left';
    lineHeight?: number;
    reveal?: RevealStyle;
    style?: React.CSSProperties;
    letterSpacing?: string;
    /** Amplitude (degrés) d'un tremblement aléatoire des mots d'accent. */
    wobbleAccent?: number;
}

export const KineticText: React.FC<KineticTextProps> = ({
    lines,
    start,
    size,
    stagger = 3,
    dur = 18,
    exit,
    exitStagger = 1.5,
    color = C.text,
    accentColor = C.gold,
    accentScale = 1.2,
    weight = 800,
    align = 'center',
    lineHeight = 1.06,
    reveal = 'mask',
    style,
    letterSpacing = '-0.035em',
    wobbleAccent = 0,
}) => {
    const frame = useCurrentFrame();
    let index = 0;

    return (
        <div
            style={{
                display: 'flex',
                flexDirection: 'column',
                alignItems: align === 'center' ? 'center' : 'flex-start',
                fontFamily: FONT.sans,
                fontWeight: weight,
                fontSize: size,
                lineHeight,
                letterSpacing,
                color,
                ...style,
            }}
        >
            {lines.map((line, li) => (
                <div
                    key={li}
                    style={{
                        display: 'flex',
                        flexWrap: 'nowrap',
                        alignItems: 'baseline',
                        justifyContent: align === 'center' ? 'center' : 'flex-start',
                        gap: '0.26em',
                        whiteSpace: 'nowrap',
                    }}
                >
                    {splitGroups(line).map((group, gi) => {
                        const i = index++;
                        const tIn = tw(frame, start + i * stagger, dur, 0, 1, EASE.out);
                        const tOut = exit === undefined ? 0 : tw(frame, exit + i * exitStagger, 11, 0, 1, EASE.in);
                        const block = (
                            <WordBlock key={gi} group={group} tIn={tIn} tOut={tOut} reveal={reveal} accentColor={accentColor} accentScale={accentScale} />
                        );
                        if (!wobbleAccent || !group.some((p) => p.accent)) return block;
                        const rot = noise2D(`wob-rot-${li}-${gi}`, frame * 0.09, 0) * wobbleAccent;
                        const dy = noise2D(`wob-y-${li}-${gi}`, frame * 0.11, 0) * wobbleAccent * 1.6;
                        return (
                            <span key={gi} style={{ display: 'inline-flex', transform: `translateY(${dy}px) rotate(${rot}deg)` }}>
                                {block}
                            </span>
                        );
                    })}
                </div>
            ))}
        </div>
    );
};

const WordBlock: React.FC<{
    group: Group;
    tIn: number;
    tOut: number;
    reveal: RevealStyle;
    accentColor: string;
    accentScale: number;
}> = ({ group, tIn, tOut, reveal, accentColor, accentScale }) => {
    const content = group.map((piece, pi) => (
        <span
            key={pi}
            style={
                piece.accent
                    ? {
                          fontFamily: FONT.serif,
                          fontStyle: 'italic',
                          fontWeight: 700,
                          fontSize: `${accentScale}em`,
                          letterSpacing: '-0.015em',
                          fontVariantNumeric: 'lining-nums',
                          color: accentColor,
                          paddingRight: '0.04em',
                      }
                    : undefined
            }
        >
            {piece.text}
        </span>
    ));

    if (reveal === 'blur') {
        const opacity = tIn * (1 - tOut);
        return (
            <span
                style={{
                    display: 'inline-block',
                    opacity,
                    filter: `blur(${(1 - tIn) * 18 + tOut * 14}px)`,
                    transform: `translateY(${(1 - tIn) * 30 - tOut * 30}px) scale(${1.12 - tIn * 0.12 + tOut * 0.06})`,
                }}
            >
                {content}
            </span>
        );
    }

    if (reveal === 'rise') {
        return (
            <span
                style={{
                    display: 'inline-block',
                    opacity: Math.min(1, tIn * 1.6) * (1 - tOut),
                    transform: `translateY(${(1 - tIn) * 0.6 - tOut * 0.4}em)`,
                }}
            >
                {content}
            </span>
        );
    }

    // Masque : le mot glisse de derrière une fenêtre invisible (padding = marge pour accents et jambages).
    // Hors animation, le mot est masqué pour de bon : sinon un jambage (p, g, j) dépasse du masque.
    const hidden = tIn <= 0.001 || tOut >= 0.999;
    return (
        <span
            style={{
                display: 'inline-block',
                overflow: 'hidden',
                padding: '0.16em 0.1em 0.2em',
                margin: '-0.16em -0.1em -0.2em',
                visibility: hidden ? 'hidden' : 'visible',
            }}
        >
            <span
                style={{
                    display: 'inline-block',
                    transform: `translateY(${(1 - tIn) * 130 - tOut * 140}%) rotate(${(1 - tIn) * 6}deg)`,
                    transformOrigin: '0% 100%',
                }}
            >
                {content}
            </span>
        </span>
    );
};

/** Petite étiquette de scène : « ÉTAPE 1 » etc. */
export const SceneTag: React.FC<{ label: string; start: number; exit?: number; color?: string; index?: string }> = ({
    label,
    start,
    exit,
    color = C.skyLight,
    index,
}) => {
    const frame = useCurrentFrame();
    const tIn = tw(frame, start, 16);
    const tOut = exit === undefined ? 0 : tw(frame, exit, 10, 0, 1, EASE.in);
    const line = tw(frame, start + 2, 20);
    return (
        <div
            style={{
                display: 'flex',
                alignItems: 'center',
                gap: 18,
                opacity: tIn * (1 - tOut),
                transform: `translateY(${(1 - tIn) * 16 - tOut * 10}px)`,
                fontFamily: FONT.sans,
                fontWeight: 700,
                fontSize: 30,
                letterSpacing: '0.24em',
                textTransform: 'uppercase',
                color,
            }}
        >
            <span style={{ width: 56 * line, height: 3, borderRadius: 2, background: color, opacity: 0.8 }} />
            {index ? (
                <span
                    style={{
                        display: 'inline-flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        width: 52,
                        height: 52,
                        borderRadius: 16,
                        border: `2px solid ${color}`,
                        letterSpacing: 0,
                        fontSize: 28,
                        fontWeight: 800,
                    }}
                >
                    {index}
                </span>
            ) : null}
            <span>{label}</span>
            <span style={{ width: 56 * line, height: 3, borderRadius: 2, background: color, opacity: 0.8 }} />
        </div>
    );
};
