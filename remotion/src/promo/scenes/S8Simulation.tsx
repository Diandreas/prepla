import React from 'react';
import { interpolate, useCurrentFrame } from 'remotion';
import { AppIcon, AppScreen, Phone } from '../components/AppAssets';
import { Move, SceneHeader } from '../components/SceneKit';
import { Pointer } from '../components/UI';
import { BEAT, C, EASE, FONT, SPRING, sp, tw } from '../theme';

// Simulation (42–46 s) : les vrais écrans de l'app (release/google-play/screenshots) —
// « Prêt à commencer ? » puis l'exercice en « MODE EXAMEN ». Le chrono de l'app sort du
// téléphone et continue de défiler au format de l'app (minutes:secondes).

const PHONE = { width: 620, left: 230, top: 772 };
const SCREEN_SCALE = (PHONE.width - PHONE.width * 0.064) / 1080;
const SCREEN_LEFT = PHONE.left + PHONE.width * 0.032;
const SCREEN_TOP = PHONE.top + PHONE.width * 0.032 + PHONE.width * 0.05;
const TAP = 34;
const SWITCH = 40;
const POP = 52;

const pad = (n: number) => String(n).padStart(2, '0');

export const S8Simulation: React.FC = () => {
    const frame = useCurrentFrame();
    const rise = sp(frame, 2, SPRING.soft);
    const switchT = tw(frame, SWITCH, 8, 0, 1, EASE.inOutSoft);
    const pop = sp(frame, POP, SPRING.bouncy);
    const popped = frame >= POP;

    // Chrono au format de l'app (« 163:36 »), une seconde par temps.
    const seconds = 163 * 60 + 36 - Math.max(0, Math.floor((frame - SWITCH) / BEAT));
    const time = `${Math.floor(seconds / 60)}:${pad(seconds % 60)}`;
    const tick = popped ? 1 + 0.03 * Math.max(0, 1 - ((frame - POP) % BEAT) / 5) : 1;

    // Position du bandeau chrono dans la capture (≈ x 524, y 85 sur 1080 × 1920).
    const fromX = SCREEN_LEFT + 524 * SCREEN_SCALE;
    const fromY = SCREEN_TOP + 85 * SCREEN_SCALE;
    const toX = 540;
    const toY = 846;
    const pillX = interpolate(pop, [0, 1], [fromX, toX]);
    const pillY = interpolate(pop, [0, 1], [fromY, toY]);
    const pillScale = interpolate(pop, [0, 1], [SCREEN_SCALE * 1.05, 1]);

    return (
        <Move enter="bottom" exit="top" exitAt={112}>
            <SceneHeader tag="Mode examen" lines={['Simule le', '*jour J*']} start={2} sub="Examens blancs chronométrés" exit={108} />

            {/* Le vrai écran de l'app */}
            <div
                style={{
                    position: 'absolute',
                    left: PHONE.left,
                    top: PHONE.top,
                    transform: `translateY(${(1 - rise) * 700}px) perspective(1800px) rotateX(${(1 - rise) * 18}deg) scale(${1 - pop * 0.03})`,
                    transformOrigin: '50% 0%',
                    filter: popped ? `brightness(${1 - pop * 0.18})` : undefined,
                }}
            >
                <Phone width={PHONE.width}>
                    <AppScreen name="03-simulation" />
                    <AppScreen name="04-exercice" style={{ opacity: switchT, transform: `translateX(${(1 - switchT) * 40}px)` }} />
                </Phone>
            </div>

            <Pointer keys={[{ f: TAP, x: SCREEN_LEFT + 524 * SCREEN_SCALE, y: SCREEN_TOP + 1440 * SCREEN_SCALE, tap: true }]} hideAt={TAP + 10} />

            {/* Le chrono sort du téléphone */}
            {popped ? (
                <div
                    style={{
                        position: 'absolute',
                        left: pillX,
                        top: pillY,
                        transform: `translate(-50%, -50%) scale(${pillScale * tick})`,
                        display: 'flex',
                        alignItems: 'center',
                        gap: 26,
                        padding: '22px 46px 22px 24px',
                        borderRadius: 999,
                        background: 'linear-gradient(180deg, #1d2f50 0%, #13233f 100%)',
                        border: '2.5px solid rgba(255,255,255,0.16)',
                        boxShadow: `0 ${30 * pop}px ${80 * pop}px -20px rgba(0,0,0,0.85), 0 0 ${70 * pop}px rgba(59,130,224,0.35)`,
                        fontFamily: FONT.sans,
                        whiteSpace: 'nowrap',
                    }}
                >
                    <AppIcon name="clock" size={96} tone="blue" shadow={false} />
                    <span style={{ fontSize: 108, fontWeight: 800, letterSpacing: '-0.02em', color: C.text, fontVariantNumeric: 'tabular-nums' }}>{time}</span>
                    <span style={{ fontSize: 30, fontWeight: 800, letterSpacing: '0.14em', color: C.skyLight, lineHeight: 1.15 }}>
                        MODE
                        <br />
                        EXAMEN
                    </span>
                </div>
            ) : null}
        </Move>
    );
};
