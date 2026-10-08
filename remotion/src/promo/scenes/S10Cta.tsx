import React from 'react';
import { AbsoluteFill, interpolate, useCurrentFrame } from 'remotion';
import { Icon } from '../components/Icons';
import { KineticText } from '../components/KineticText';
import { LOGO_RATIO, LogoMark, Wordmark } from '../components/Logo';
import { Pointer } from '../components/UI';
import { C, EASE, FONT, SPRING, env, sp, tw } from '../theme';

// Final (52–60 s) : « Ne révise plus au hasard. » → « Prépare-toi avec méthode. »,
// puis le logo, le bouton « Fais ton diagnostic gratuit » et l'adresse du site.

const CARD_AT = 120;
const LOGO = { size: 300, y: 600 };
const BUTTON = { at: CARD_AT + 30, tap: CARD_AT + 68, top: 1080, w: 880, h: 156 };
const URL = 'prepla.mirlab.cloud';

export const S10Cta: React.FC = () => {
    const frame = useCurrentFrame();
    const button = sp(frame, BUTTON.at, SPRING.pop);
    const press = interpolate(frame, [BUTTON.tap - 3, BUTTON.tap, BUTTON.tap + 8], [0, 1, 0], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
    const shine = (start: number) => tw(frame, start, 22, -0.4, 1.4, EASE.inOutSoft);
    const typed = Math.round(tw(frame, CARD_AT + 44, 22, 0, URL.length, (t) => t));
    const tagline = tw(frame, CARD_AT + 26, 16);
    const exams = tw(frame, CARD_AT + 62, 18);
    const legal = tw(frame, CARD_AT + 76, 18);
    const glow = env(frame, CARD_AT, 20, 999, 1);

    return (
        <AbsoluteFill>
            {/* 1. Ne révise plus au hasard. */}
            <div style={{ position: 'absolute', top: 760, left: 0, right: 0 }}>
                <KineticText lines={['Ne révise plus', '*au hasard*.']} start={0} size={132} stagger={4} exit={42} />
            </div>
            {/* 2. Prépare-toi avec méthode. */}
            <div style={{ position: 'absolute', top: 760, left: 0, right: 0 }}>
                <KineticText lines={['Prépare-toi', 'avec *méthode*.']} start={60} size={132} stagger={4} exit={102} />
            </div>

            {/* 3. Carte de fin */}
            {frame >= CARD_AT - 2 ? (
                <>
                    <div
                        style={{
                            position: 'absolute',
                            left: 540 - 520,
                            top: LOGO.y - 520,
                            width: 1040,
                            height: 1040,
                            borderRadius: '50%',
                            background: 'radial-gradient(circle, rgba(59,130,224,0.35) 0%, rgba(59,130,224,0.08) 40%, rgba(59,130,224,0) 68%)',
                            opacity: glow,
                        }}
                    />
                    <div
                        style={{
                            position: 'absolute',
                            left: 540 - (LOGO.size * LOGO_RATIO) / 2,
                            top: LOGO.y - LOGO.size / 2,
                            filter: 'drop-shadow(0 30px 60px rgba(0,0,0,0.5)) drop-shadow(0 0 40px rgba(59,130,224,0.4))',
                        }}
                    >
                        <LogoMark size={LOGO.size} assembleAt={CARD_AT} glossAt={CARD_AT + 40} />
                    </div>
                    <div style={{ position: 'absolute', top: 820, left: 0, right: 0, display: 'flex', justifyContent: 'center' }}>
                        <Wordmark size={124} revealAt={CARD_AT + 12} badgeAt={CARD_AT + 26} />
                    </div>
                    <div
                        style={{
                            position: 'absolute',
                            top: 966,
                            left: 0,
                            right: 0,
                            textAlign: 'center',
                            fontFamily: FONT.sans,
                            fontWeight: 600,
                            fontSize: 40,
                            color: C.textMid,
                            opacity: tagline,
                            transform: `translateY(${(1 - tagline) * 16}px)`,
                        }}
                    >
                        Ton coach IA pour les examens de langue
                    </div>

                    {/* Bouton */}
                    <div
                        style={{
                            position: 'absolute',
                            left: 540 - BUTTON.w / 2,
                            top: BUTTON.top,
                            width: BUTTON.w,
                            height: BUTTON.h,
                            borderRadius: 999,
                            overflow: 'hidden',
                            background: `linear-gradient(135deg, #6aaaf6 0%, ${C.sky} 55%, #2f6fcc 100%)`,
                            boxShadow: `0 0 ${60 + press * 40}px rgba(59,130,224,0.6), 0 30px 60px -20px rgba(0,0,0,0.7), inset 0 2px 0 rgba(255,255,255,0.35)`,
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            gap: 26,
                            transform: `scale(${(0.6 + 0.4 * button) * (1 - press * 0.05)})`,
                            opacity: Math.min(1, button * 1.6),
                            fontFamily: FONT.sans,
                            fontWeight: 800,
                            fontSize: 52,
                            letterSpacing: '-0.02em',
                            color: '#fff',
                        }}
                    >
                        Fais ton diagnostic gratuit
                        <div
                            style={{
                                width: 84,
                                height: 84,
                                borderRadius: '50%',
                                background: 'rgba(255,255,255,0.22)',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                transform: `translateX(${Math.sin(frame * 0.35) * 4}px)`,
                            }}
                        >
                            <Icon name="arrowRight" size={46} color="#fff" stroke={3} />
                        </div>
                        {[CARD_AT + 46, CARD_AT + 96].map((s) => (
                            <div
                                key={s}
                                style={{
                                    position: 'absolute',
                                    top: -40,
                                    bottom: -40,
                                    width: 140,
                                    left: `${shine(s) * 100}%`,
                                    background: 'linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.45) 50%, rgba(255,255,255,0) 100%)',
                                    transform: 'rotate(18deg)',
                                }}
                            />
                        ))}
                    </div>

                    {/* Adresse */}
                    <div
                        style={{
                            position: 'absolute',
                            top: 1282,
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
                            top: 1400,
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
                            top: 1530,
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

                    <Pointer keys={[{ f: BUTTON.tap, x: 540 + 250, y: BUTTON.top + BUTTON.h / 2 + 10, tap: true }]} hideAt={BUTTON.tap + 16} />
                </>
            ) : null}
        </AbsoluteFill>
    );
};
