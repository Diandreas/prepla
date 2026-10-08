import React from 'react';
import { AbsoluteFill, useCurrentFrame } from 'remotion';
import { C, EASE, FONT, tw } from '../theme';
import { KineticText, SceneTag } from './KineticText';

export const TAG_TOP = 326;
export const HEAD_TOP = 392;

/** Étiquette + titre d'une scène, toujours au même endroit pour donner un rythme lisible. */
export const SceneHeader: React.FC<{
    tag: string;
    lines: string[];
    start?: number;
    exit?: number;
    size?: number;
    sub?: string;
    subTop?: number;
}> = ({ tag, lines, start = 0, exit, size = 118, sub, subTop }) => {
    const frame = useCurrentFrame();
    const subIn = tw(frame, start + 14, 18);
    const subOut = exit === undefined ? 0 : tw(frame, exit, 10, 0, 1, EASE.in);
    return (
        <>
            <div style={{ position: 'absolute', top: TAG_TOP, left: 0, right: 0, display: 'flex', justifyContent: 'center' }}>
                <SceneTag label={tag} start={start} exit={exit} />
            </div>
            <div style={{ position: 'absolute', top: HEAD_TOP, left: 0, right: 0 }}>
                <KineticText lines={lines} start={start + 3} size={size} exit={exit} stagger={3} />
            </div>
            {sub ? (
                <div
                    style={{
                        position: 'absolute',
                        top: subTop ?? HEAD_TOP + lines.length * size * 1.06 + 22 + (lines[lines.length - 1].includes('*') ? size * 0.24 : 0),
                        left: 60,
                        right: 60,
                        textAlign: 'center',
                        fontFamily: FONT.sans,
                        fontWeight: 600,
                        fontSize: 40,
                        lineHeight: 1.3,
                        color: C.textMid,
                        opacity: subIn * (1 - subOut),
                        transform: `translateY(${(1 - subIn) * 18}px)`,
                    }}
                >
                    {sub}
                </div>
            ) : null}
        </>
    );
};

export type EnterKind = 'right' | 'bottom' | 'zoom' | 'fade' | 'none';
export type ExitKind = 'left' | 'top' | 'zoom' | 'fade' | 'none';

/** Mouvement de caméra d'entrée/sortie appliqué à tout le contenu d'une scène. */
export const Move: React.FC<{
    children: React.ReactNode;
    enter?: EnterKind;
    exit?: ExitKind;
    exitAt?: number;
    enterDur?: number;
    exitDur?: number;
    origin?: string;
}> = ({ children, enter = 'none', exit = 'none', exitAt = Infinity, enterDur = 16, exitDur = 14, origin = '50% 50%' }) => {
    const frame = useCurrentFrame();
    const e = enter === 'none' ? 1 : tw(frame, 0, enterDur, 0, 1, EASE.out);
    const x = exit === 'none' || !Number.isFinite(exitAt) ? 0 : tw(frame, exitAt, exitDur, 0, 1, EASE.in);

    let tx = 0;
    let ty = 0;
    let scale = 1;
    let opacity = 1;
    let blur = 0;

    if (enter === 'right') {
        tx += 1080 * (1 - e);
        blur += (1 - e) * 14;
    } else if (enter === 'bottom') {
        ty += 420 * (1 - e);
        opacity *= e;
        blur += (1 - e) * 8;
    } else if (enter === 'zoom') {
        scale *= 0.82 + 0.18 * e;
        opacity *= e;
        blur += (1 - e) * 10;
    } else if (enter === 'fade') {
        opacity *= e;
    }

    if (exit === 'left') {
        tx -= 1080 * x;
        blur += x * 14;
    } else if (exit === 'top') {
        ty -= 420 * x;
        opacity *= 1 - x;
        blur += x * 8;
    } else if (exit === 'zoom') {
        scale *= 1 + 0.45 * x;
        opacity *= 1 - x;
        blur += x * 12;
    } else if (exit === 'fade') {
        opacity *= 1 - x;
    }

    return (
        <AbsoluteFill
            style={{
                transform: `translate(${tx}px, ${ty}px) scale(${scale})`,
                transformOrigin: origin,
                opacity,
                filter: blur > 0.3 ? `blur(${blur}px)` : undefined,
            }}
        >
            {children}
        </AbsoluteFill>
    );
};

/** Ligne de texte secondaire centrée. */
export const Caption: React.FC<{ top: number; start: number; exit?: number; children: React.ReactNode; size?: number }> = ({
    top,
    start,
    exit,
    children,
    size = 40,
}) => {
    const frame = useCurrentFrame();
    const tIn = tw(frame, start, 18);
    const tOut = exit === undefined ? 0 : tw(frame, exit, 10, 0, 1, EASE.in);
    return (
        <div
            style={{
                position: 'absolute',
                top,
                left: 60,
                right: 60,
                textAlign: 'center',
                fontFamily: FONT.sans,
                fontWeight: 600,
                fontSize: size,
                lineHeight: 1.3,
                color: C.textMid,
                opacity: tIn * (1 - tOut),
                transform: `translateY(${(1 - tIn) * 20}px)`,
            }}
        >
            {children}
        </div>
    );
};
