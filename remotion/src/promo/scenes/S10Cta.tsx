import React from 'react';
import { AbsoluteFill, interpolate, useCurrentFrame } from 'remotion';
import { Fox } from '../components/AppAssets';
import { Icon } from '../components/Icons';
import { KineticText } from '../components/KineticText';
import { LOGO_RATIO, LogoMark, Wordmark } from '../components/Logo';
import { Pointer } from '../components/UI';
import { BEAT, C, EASE, FONT, SPRING, env, sp, tw } from '../theme';

// Final (52–60 s) : « Ne révise plus au hasard. » → « Prépare-toi avec méthode. », puis le
// logo et la carte « Ta prochaine mission » de l'accueil de l'app (components/daily-mission.tsx)
// avec le renard-guide, le bouton « Créer mon parcours » et l'adresse du site.

const CARD_AT = 120;
const LOGO = { size: 230, y: 430 };
const MISSION = { left: 80, top: 760, width: 920, height: 470 };
const BUTTON_TAP = CARD_AT + 70;
const URL = 'prepla.mirlab.cloud';

export const S10Cta: React.FC = () => {
    const frame = useCurrentFrame();
    const card = sp(frame, CARD_AT + 26, SPRING.soft);
    const fox = sp(frame, CARD_AT + 40, SPRING.bouncy);
    const wave = Math.sin(((frame - CARD_AT) / (BEAT * 2)) * Math.PI * 2) * 4 * Math.min(1, fox);
    const press = interpolate(frame, [BUTTON_TAP - 3, BUTTON_TAP, BUTTON_TAP + 8], [0, 1, 0], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
    const shine = tw(frame, CARD_AT + 60, 22, -0.4, 1.4, EASE.inOutSoft);
    const typed = Math.round(tw(frame, CARD_AT + 56, 20, 0, URL.length, (t) => t));
    const line = (d: number) => tw(frame, CARD_AT + 30 + d, 14);
    const exams = tw(frame, CARD_AT + 80, 16);
    const legal = tw(frame, CARD_AT + 90, 16);
    const glow = env(frame, CARD_AT, 20, 999, 1);

    return (
        <AbsoluteFill>
            <div style={{ position: 'absolute', top: 760, left: 0, right: 0 }}>
                <KineticText lines={['Ne révise plus', '*au hasard*.']} start={0} size={132} stagger={4} exit={42} />
            </div>
            <div style={{ position: 'absolute', top: 760, left: 0, right: 0 }}>
                <KineticText lines={['Prépare-toi', 'avec *méthode*.']} start={60} size={132} stagger={4} exit={102} />
            </div>

            {frame >= CARD_AT - 2 ? (
                <>
                    <div
                        style={{
                            position: 'absolute',
                            left: 540 - 460,
                            top: LOGO.y - 460,
                            width: 920,
                            height: 920,
                            borderRadius: '50%',
                            background: 'radial-gradient(circle, rgba(59,130,224,0.32) 0%, rgba(59,130,224,0.08) 40%, rgba(59,130,224,0) 68%)',
                            opacity: glow,
                        }}
                    />
                    <div
                        style={{
                            position: 'absolute',
                            left: 540 - (LOGO.size * LOGO_RATIO) / 2,
                            top: LOGO.y - LOGO.size / 2,
                            filter: 'drop-shadow(0 26px 50px rgba(0,0,0,0.5)) drop-shadow(0 0 36px rgba(59,130,224,0.4))',
                        }}
                    >
                        <LogoMark size={LOGO.size} assembleAt={CARD_AT} glossAt={CARD_AT + 36} />
                    </div>
                    <div style={{ position: 'absolute', top: 590, left: 0, right: 0, display: 'flex', justifyContent: 'center' }}>
                        <Wordmark size={104} revealAt={CARD_AT + 12} badgeAt={CARD_AT + 24} />
                    </div>

                    {/* Carte « Ta prochaine mission » de l'accueil de l'app */}
                    <div
                        style={{
                            position: 'absolute',
                            left: MISSION.left,
                            top: MISSION.top,
                            width: MISSION.width,
                            height: MISSION.height,
                            borderRadius: 44,
                            overflow: 'hidden',
                            background: 'linear-gradient(135deg, #193b67 0%, #173458 50%, #14243e 100%)',
                            border: '2px solid rgba(106,170,246,0.35)',
                            boxShadow: '0 50px 100px -30px rgba(0,0,0,0.8), 0 0 90px rgba(59,130,224,0.22)',
                            transform: `translateY(${(1 - card) * 140}px) scale(${0.94 + 0.06 * card})`,
                            opacity: Math.min(1, card * 1.6),
                            fontFamily: FONT.sans,
                            color: '#fff',
                        }}
                    >
                        <div style={{ position: 'absolute', right: -120, top: -150, width: 520, height: 520, borderRadius: '50%', border: '64px solid rgba(255,255,255,0.04)' }} />
                        <div style={{ position: 'absolute', left: 52, top: 46, width: 560 }}>
                            <div style={{ display: 'flex', alignItems: 'center', gap: 12, fontSize: 28, fontWeight: 700, color: '#c7dcff', opacity: line(0) }}>
                                <Icon name="sparkles" size={30} color="#c7dcff" stroke={2.2} />
                                Ta prochaine mission
                            </div>
                            <div
                                style={{
                                    marginTop: 14,
                                    fontSize: 56,
                                    fontWeight: 800,
                                    lineHeight: 1.08,
                                    letterSpacing: '-0.03em',
                                    opacity: line(4),
                                    transform: `translateY(${(1 - line(4)) * 20}px)`,
                                }}
                            >
                                Fais ton diagnostic{' '}
                                <span style={{ fontFamily: FONT.serif, fontStyle: 'italic', fontWeight: 700, fontSize: '1.2em', color: C.goldLight }}>gratuit</span>
                            </div>
                            <div style={{ marginTop: 14, fontSize: 28, fontWeight: 500, lineHeight: 1.4, color: 'rgba(219,234,254,0.85)', opacity: line(8) }}>
                                Ton niveau en quelques minutes, puis un parcours fait pour ton examen.
                            </div>
                            <div
                                style={{
                                    position: 'relative',
                                    overflow: 'hidden',
                                    marginTop: 26,
                                    display: 'inline-flex',
                                    alignItems: 'center',
                                    gap: 16,
                                    padding: '22px 34px',
                                    borderRadius: 22,
                                    background: '#ffffff',
                                    color: '#193b67',
                                    fontSize: 36,
                                    fontWeight: 800,
                                    letterSpacing: '-0.01em',
                                    boxShadow: `0 ${18 - press * 10}px 40px -12px rgba(0,0,0,0.5), 0 0 ${40 * press}px rgba(255,255,255,0.6)`,
                                    transform: `scale(${(0.8 + 0.2 * line(12)) * (1 - press * 0.05)})`,
                                    transformOrigin: '0% 50%',
                                    opacity: line(12),
                                }}
                            >
                                Créer mon parcours
                                <Icon name="arrowRight" size={38} color="#193b67" stroke={3} style={{ transform: `translateX(${Math.sin(frame * 0.35) * 4}px)` }} />
                                <div
                                    style={{
                                        position: 'absolute',
                                        top: -30,
                                        bottom: -30,
                                        width: 120,
                                        left: `${shine * 100}%`,
                                        background: 'linear-gradient(90deg, rgba(59,130,224,0) 0%, rgba(59,130,224,0.25) 50%, rgba(59,130,224,0) 100%)',
                                        transform: 'rotate(18deg)',
                                    }}
                                />
                            </div>
                        </div>
                    </div>

                    {/* Le renard-guide de l'app salue */}
                    <div
                        style={{
                            position: 'absolute',
                            left: 655,
                            top: MISSION.top + MISSION.height - 480,
                            transform: `translateY(${(1 - fox) * 260}px) rotate(${wave}deg) scale(${0.85 + 0.15 * Math.min(1, fox)})`,
                            transformOrigin: '50% 100%',
                            opacity: Math.min(1, fox * 2),
                            filter: 'drop-shadow(0 24px 30px rgba(0,0,0,0.45))',
                        }}
                    >
                        <Fox height={480} />
                    </div>

                    {/* Adresse */}
                    <div
                        style={{
                            position: 'absolute',
                            top: 1262,
                            left: 0,
                            right: 0,
                            display: 'flex',
                            justifyContent: 'center',
                            alignItems: 'center',
                            gap: 18,
                            fontFamily: FONT.sans,
                            fontWeight: 800,
                            fontSize: 58,
                            letterSpacing: '-0.02em',
                            color: C.text,
                            opacity: typed > 0 ? 1 : 0,
                        }}
                    >
                        <Icon name="globe" size={52} color={C.skyLight} stroke={2.2} />
                        <span>
                            {URL.slice(0, typed)}
                            <span style={{ opacity: typed < URL.length && Math.floor(frame / 6) % 2 === 0 ? 1 : 0, color: C.skyLight }}>|</span>
                        </span>
                    </div>

                    <div
                        style={{
                            position: 'absolute',
                            top: 1374,
                            left: 60,
                            right: 60,
                            textAlign: 'center',
                            fontFamily: FONT.sans,
                            fontWeight: 700,
                            fontSize: 34,
                            lineHeight: 1.5,
                            letterSpacing: '0.02em',
                            color: C.textMid,
                            opacity: exams,
                            transform: `translateY(${(1 - exams) * 16}px)`,
                        }}
                    >
                        IELTS · TOEFL · Cambridge · DELF/DALF
                        <br />
                        TCF · TEF · Goethe · TestDaF
                    </div>
                    <div
                        style={{
                            position: 'absolute',
                            top: 1506,
                            left: 90,
                            right: 90,
                            textAlign: 'center',
                            fontFamily: FONT.sans,
                            fontWeight: 500,
                            fontSize: 24,
                            lineHeight: 1.4,
                            color: C.textDim,
                            opacity: legal,
                        }}
                    >
                        PrePla est une plateforme indépendante, non affiliée aux organismes certificateurs.
                    </div>

                    <Pointer keys={[{ f: BUTTON_TAP, x: MISSION.left + 52 + 280, y: MISSION.top + 384, tap: true }]} hideAt={BUTTON_TAP + 16} />
                </>
            ) : null}
        </AbsoluteFill>
    );
};
