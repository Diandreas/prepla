import { noise2D } from '@remotion/noise';
import React, { useLayoutEffect, useRef } from 'react';
import { AbsoluteFill, interpolate, useCurrentFrame } from 'remotion';
import { C, H, SCENES, W } from '../theme';

const CLAMP = { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' } as const;

/** Grain filmique déterministe (dessiné à demi-résolution) : donne de la matière et évite le banding. */
const Grain: React.FC<{ opacity: number }> = ({ opacity }) => {
    const frame = useCurrentFrame();
    const ref = useRef<HTMLCanvasElement>(null);

    useLayoutEffect(() => {
        const canvas = ref.current;
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;
        const w = canvas.width;
        const h = canvas.height;
        const img = ctx.createImageData(w, h);
        // mulberry32, graine = numéro d'image : le grain bouge mais reste identique à chaque rendu.
        let a = (Math.floor(frame / 3) + 1) * 0x9e3779b1;
        const rand = () => {
            a |= 0;
            a = (a + 0x6d2b79f5) | 0;
            let t = Math.imul(a ^ (a >>> 15), 1 | a);
            t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
            return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
        };
        const data = img.data;
        for (let i = 0; i < data.length; i += 4) {
            const v = 128 + (rand() + rand() - 1) * 120;
            data[i] = v;
            data[i + 1] = v;
            data[i + 2] = v;
            data[i + 3] = 255;
        }
        ctx.putImageData(img, 0, 0);
    }, [frame]);

    return (
        <canvas
            ref={ref}
            width={W / 2}
            height={H / 2}
            style={{ position: 'absolute', inset: 0, width: W, height: H, mixBlendMode: 'overlay', opacity }}
        />
    );
};

type Keys = { frames: number[]; values: number[] };

// Par défaut (vidéos de 60 s) : teinte d'urgence pendant l'accroche, effacée à l'impact du logo,
// et grille qui accélère pendant le chaos.
const STRESS: Keys = { frames: [0, 30, SCENES.logo.from - 20, SCENES.logo.from + 10], values: [0.4, 1, 1, 0] };
const GRID_RUSH: [number, number] = [SCENES.chaos.from, SCENES.logo.from];

/**
 * Décor continu de toute la vidéo : dégradé navy, grille « blueprint » qui dérive,
 * halos bleu/or qui respirent, teinte d'urgence au début (stress) qui s'efface au logo.
 */
export const Background: React.FC<{ stressKeys?: Keys; gridRush?: [number, number] | null }> = ({ stressKeys = STRESS, gridRush = GRID_RUSH }) => {
    const frame = useCurrentFrame();
    const t = frame / 30;

    // Teinte « stress » (ambre/rouge).
    const stress = interpolate(frame, stressKeys.frames, stressKeys.values, CLAMP);

    // La grille avance comme une caméra qui monte doucement ; elle accélère pendant le chaos.
    const gridShift = frame * 0.6 + (gridRush ? interpolate(frame, gridRush, [0, 140], CLAMP) : 0);

    const g1x = 0.28 + noise2D('g1x', t * 0.08, 0) * 0.12;
    const g1y = 0.22 + noise2D('g1y', t * 0.08, 0) * 0.08;
    const g2x = 0.74 + noise2D('g2x', t * 0.07, 0) * 0.12;
    const g2y = 0.78 + noise2D('g2y', t * 0.07, 0) * 0.08;
    const breathe = 1 + Math.sin(t * 1.2) * 0.05;

    return (
        <AbsoluteFill style={{ background: C.bg, overflow: 'hidden' }}>
            <AbsoluteFill
                style={{
                    background: `radial-gradient(120% 75% at 50% 30%, ${C.bgLift} 0%, ${C.bg} 55%, ${C.bgDeep} 100%)`,
                }}
            />

            {/* Halo bleu */}
            <div
                style={{
                    position: 'absolute',
                    left: g1x * W - 520,
                    top: g1y * H - 520,
                    width: 1040,
                    height: 1040,
                    borderRadius: '50%',
                    background: 'radial-gradient(circle, rgba(59,130,224,0.30) 0%, rgba(59,130,224,0.10) 40%, rgba(59,130,224,0) 70%)',
                    transform: `scale(${breathe})`,
                }}
            />
            {/* Halo or */}
            <div
                style={{
                    position: 'absolute',
                    left: g2x * W - 460,
                    top: g2y * H - 460,
                    width: 920,
                    height: 920,
                    borderRadius: '50%',
                    background: 'radial-gradient(circle, rgba(245,166,35,0.20) 0%, rgba(245,166,35,0.06) 42%, rgba(245,166,35,0) 70%)',
                    transform: `scale(${2 - breathe})`,
                }}
            />

            {/* Teinte d'urgence de l'accroche */}
            <AbsoluteFill
                style={{
                    opacity: stress,
                    background:
                        'radial-gradient(90% 60% at 50% 45%, rgba(245,120,35,0.16) 0%, rgba(239,68,68,0.10) 45%, rgba(0,0,0,0) 75%)',
                }}
            />

            {/* Grille blueprint, estompée vers les bords */}
            <svg
                width={W}
                height={H}
                style={{
                    position: 'absolute',
                    inset: 0,
                    WebkitMaskImage: 'radial-gradient(75% 60% at 50% 45%, black 20%, transparent 100%)',
                    maskImage: 'radial-gradient(75% 60% at 50% 45%, black 20%, transparent 100%)',
                }}
            >
                <defs>
                    <pattern id="promo-grid" width="90" height="90" patternUnits="userSpaceOnUse" patternTransform={`translate(0 ${-gridShift % 90})`}>
                        <path d="M 90 0 L 0 0 0 90" fill="none" stroke={C.sky} strokeWidth="1.2" opacity="0.16" />
                    </pattern>
                </defs>
                <rect width={W} height={H} fill="url(#promo-grid)" />
            </svg>

            <Grain opacity={0.1} />

            {/* Vignette */}
            <AbsoluteFill
                style={{
                    background: 'radial-gradient(110% 80% at 50% 45%, rgba(0,0,0,0) 55%, rgba(2,6,14,0.55) 100%)',
                }}
            />
        </AbsoluteFill>
    );
};
