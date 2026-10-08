import { noise2D } from '@remotion/noise';
import React from 'react';
import { AbsoluteFill, interpolate, random, useCurrentFrame } from 'remotion';
import { AppIcon, type AppIconName } from '../components/AppAssets';
import { KineticText } from '../components/KineticText';
import { C, EASE, FONT, SPRING, sp, tw } from '../theme';

// Le problème (4–8 s) : « Tu révises au hasard ? » entouré de questions qui flottent
// dans tous les sens, puis tout est aspiré vers un point lumineux d'où naîtra le logo.

// Les icônes sont celles de l'application (public/icons).
const CHIPS: Array<{ text: string; x: number; y: number; z: number; icon?: AppIconName }> = [
    { text: 'Grammaire ?', x: 250, y: 400, z: 0.95, icon: 'courses' },
    { text: 'Par où commencer ?', x: 690, y: 330, z: 0.72 },
    { text: 'Vocabulaire ?', x: 790, y: 540, z: 1.0, icon: 'vocabulary' },
    { text: 'Quel niveau ?', x: 290, y: 650, z: 1.08, icon: 'statistics' },
    { text: 'Subjonctif ?', x: 560, y: 470, z: 0.6 },
    { text: 'Oral ?', x: 220, y: 1200, z: 1.02, icon: 'speaking' },
    { text: 'Combien de temps ?', x: 650, y: 1250, z: 0.86, icon: 'clock' },
    { text: 'Écoute ?', x: 870, y: 1420, z: 0.74, icon: 'listening' },
    { text: 'Conjugaison ?', x: 330, y: 1430, z: 0.92 },
    { text: 'Écrit ?', x: 640, y: 1580, z: 1.06, icon: 'writing' },
    { text: 'Lecture ?', x: 870, y: 1120, z: 0.64 },
    { text: 'Quel examen ?', x: 190, y: 1600, z: 0.7, icon: 'target' },
];

const CENTER = { x: 540, y: 930 };
const IMPLODE = 80;

export const S2Chaos: React.FC = () => {
    const frame = useCurrentFrame();
    const t = frame / 30;

    // Légère secousse de caméra : le stress.
    const shakeAmp = interpolate(frame, [10, 40, IMPLODE, IMPLODE + 20], [0, 5, 5, 0], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
    const shakeX = noise2D('shx', t * 6, 0) * shakeAmp;
    const shakeY = noise2D('shy', t * 6, 0) * shakeAmp;

    const textImplode = tw(frame, IMPLODE + 6, 26, 0, 1, EASE.in);
    const core = tw(frame, IMPLODE + 12, 26, 0, 1, EASE.in);
    const flare = tw(frame, IMPLODE + 22, 18, 0, 1, EASE.in);

    return (
        <AbsoluteFill style={{ transform: `translate(${shakeX}px, ${shakeY}px)` }}>
            {CHIPS.map((chip, i) => {
                const pop = sp(frame, 4 + i * 3.5, SPRING.pop);
                const k = tw(frame, IMPLODE + random(`imp${i}`) * 8, 26, 0, 1, EASE.in);
                const driftX = noise2D(`cx${i}`, t * 0.45, 0) * 38;
                const driftY = noise2D(`cy${i}`, t * 0.45, 0) * 38;
                const rot = noise2D(`cr${i}`, t * 0.35, 0) * 11 + k * (random(`dir${i}`) > 0.5 ? 220 : -220);
                const x = chip.x + driftX;
                const y = chip.y + driftY;
                const px = x + (CENTER.x - x) * k;
                const py = y + (CENTER.y - y) * k;
                const depthBlur = chip.z < 0.8 ? (0.8 - chip.z) * 18 : 0;
                const scale = chip.z * (0.4 + 0.6 * pop) * (1 - k * 0.9);
                return (
                    <div
                        key={i}
                        style={{
                            position: 'absolute',
                            left: px,
                            top: py,
                            transform: `translate(-50%, -50%) rotate(${rot}deg) scale(${scale})`,
                            opacity: Math.min(1, pop * 1.6) * (chip.z < 0.8 ? 0.6 : 0.95) * (1 - Math.pow(k, 3)),
                            filter: depthBlur + k * 8 > 0.3 ? `blur(${depthBlur + k * 8}px)` : undefined,
                            display: 'flex',
                            alignItems: 'center',
                            gap: 16,
                            padding: chip.icon ? '10px 34px 10px 10px' : '20px 36px',
                            borderRadius: 999,
                            background: 'rgba(255,255,255,0.07)',
                            border: '2px solid rgba(255,255,255,0.16)',
                            color: C.textMid,
                            fontFamily: FONT.sans,
                            fontWeight: 700,
                            fontSize: 42,
                            whiteSpace: 'nowrap',
                            boxShadow: '0 20px 40px -20px rgba(0,0,0,0.6)',
                        }}
                    >
                        {chip.icon ? <AppIcon name={chip.icon} size={60} tone="blue" shadow={false} /> : null}
                        {chip.text}
                    </div>
                );
            })}

            <AbsoluteFill
                style={{
                    justifyContent: 'center',
                    alignItems: 'center',
                    transform: `translateY(${CENTER.y - 960}px) scale(${1 - textImplode}) rotate(${-textImplode * 30}deg)`,
                    transformOrigin: `540px 960px`,
                    filter: textImplode > 0.02 ? `blur(${textImplode * 12}px)` : undefined,
                    opacity: 1 - Math.pow(textImplode, 2),
                }}
            >
                <KineticText lines={['Tu révises', '*au hasard* ?']} start={0} size={150} stagger={4} wobbleAccent={5} />
            </AbsoluteFill>

            {/* Point lumineux qui aspire tout */}
            <div
                style={{
                    position: 'absolute',
                    left: CENTER.x,
                    top: CENTER.y,
                    width: 420,
                    height: 420,
                    borderRadius: '50%',
                    transform: `translate(-50%, -50%) scale(${core})`,
                    background: 'radial-gradient(circle, #ffffff 0%, rgba(160,205,255,0.95) 12%, rgba(59,130,224,0.55) 34%, rgba(59,130,224,0) 70%)',
                    opacity: core,
                }}
            />
            <div
                style={{
                    position: 'absolute',
                    left: CENTER.x,
                    top: CENTER.y,
                    width: 1400,
                    height: 10,
                    borderRadius: 10,
                    transform: `translate(-50%, -50%) scaleX(${flare})`,
                    background: 'linear-gradient(90deg, rgba(160,205,255,0) 0%, rgba(220,236,255,0.95) 50%, rgba(160,205,255,0) 100%)',
                    opacity: flare,
                }}
            />
        </AbsoluteFill>
    );
};
