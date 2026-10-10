import React from 'react';
import { AbsoluteFill, interpolate, useCurrentFrame } from 'remotion';
import { Fox } from '../components/AppAssets';
import { Icon } from '../components/Icons';
import { KineticText } from '../components/KineticText';
import { LOGO_RATIO, LogoMark, Wordmark } from '../components/Logo';
import { Pointer } from '../components/UI';
import { usePromoContent } from '../content';
import { BEAT, C, EASE, FONT, SPRING, env, sp, tw } from '../theme';

// Final (52–60 s) : « Ne révise plus au hasard. » → « Prépare-toi avec méthode. », puis le
// logo et la carte « Ta prochaine mission » de l'accueil de l'app (components/daily-mission.tsx)
// avec le renard-guide, le bouton « Créer mon parcours » et l'adresse du site.

type CtaLayout = {
    logo: { size: number; y: number };
    wordTop: number;
    wordSize: number;
    mission: { left: number; top: number; width: number; height: number };
    fox: { left: number; height: number };
    /** Centre du bouton « Créer mon parcours », depuis le haut de la carte. */
    buttonY: number;
    /** Point du tap sur le bouton, depuis le début de la colonne de texte. */
    tapX: number;
    tapHide: number;
    urlTop: number;
    examsTop: number;
    legalTop: number;
    /** Apparition des examens et de la mention légale, en images après CARD_AT. */
    examsAt: number;
    legalAt: number;
    legal: { size: number; color: string; balance: boolean };
};

const LAYOUTS: Record<'full' | 'compact', CtaLayout> = {
    full: {
        logo: { size: 230, y: 430 },
        wordTop: 590,
        wordSize: 104,
        mission: { left: 80, top: 760, width: 920, height: 470 },
        fox: { left: 655, height: 480 },
        buttonY: 384,
        tapX: 280,
        tapHide: 16,
        urlTop: 1262,
        examsTop: 1374,
        legalTop: 1506,
        examsAt: 80,
        legalAt: 90,
        legal: { size: 24, color: C.textDim, balance: false },
    },
    // Format court : sans description, tout tient au-dessus de y 1420 (zone sûre des réseaux sociaux).
    compact: {
        logo: { size: 190, y: 410 },
        wordTop: 530,
        wordSize: 96,
        mission: { left: 80, top: 700, width: 920, height: 380 },
        fox: { left: 660, height: 400 },
        buttonY: 298,
        // Le doigt tape la flèche : le libellé reste lisible.
        tapX: 410,
        tapHide: 8,
        urlTop: 1120,
        examsTop: 1220,
        legalTop: 1350,
        examsAt: 60,
        legalAt: 66,
        legal: { size: 28, color: C.textMid, balance: true },
    },
};
const URL = 'prepla.mirlab.cloud';

export const S10Cta: React.FC<{
    /** Image (relative à la séquence) de l'impact du logo ; la carte et le reste suivent. */
    cardAt?: number;
    /** Phrases d'introduction (« Ne révise plus au hasard. »…) avant le logo. */
    intro?: boolean;
    layout?: 'full' | 'compact';
    tapOffset?: number;
    showDescription?: boolean;
    /** Le logo commence à s'assembler `logoLead` images avant CARD_AT (il est formé sur le temps fort). */
    logoLead?: number;
}> = ({ cardAt: CARD_AT = 120, intro = true, layout = 'full', tapOffset = 70, showDescription = true, logoLead = 0 }) => {
    const frame = useCurrentFrame();
    const content = usePromoContent().cta;
    const { logo: LOGO, mission: MISSION, ...L } = LAYOUTS[layout];
    const BUTTON_TAP = CARD_AT + tapOffset;
    const LOGO_AT = CARD_AT - logoLead;
    const bubble = sp(frame, CARD_AT + 54, SPRING.bouncy);
    const card = sp(frame, CARD_AT + 26, SPRING.soft);
    const fox = sp(frame, CARD_AT + 40, SPRING.bouncy);
    const wave = Math.sin(((frame - CARD_AT) / (BEAT * 2)) * Math.PI * 2) * 4 * Math.min(1, fox);
    const press = interpolate(frame, [BUTTON_TAP - 3, BUTTON_TAP, BUTTON_TAP + 8], [0, 1, 0], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
    // Deux passages de reflet sur le bouton : à l'arrivée, puis sur l'accord final (format court).
    const shine = frame < CARD_AT + 120 ? tw(frame, CARD_AT + 60, 22, -0.4, 1.4, EASE.inOutSoft) : tw(frame, CARD_AT + 120, 18, -0.4, 1.4, EASE.inOutSoft);
    const pulse = interpolate(frame, [CARD_AT + 120, CARD_AT + 124, CARD_AT + 136], [0, 1, 0], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
    const typed = Math.round(tw(frame, CARD_AT + 56, 20, 0, URL.length, (t) => t));
    const line = (d: number) => tw(frame, CARD_AT + 30 + d, 14);
    const exams = tw(frame, CARD_AT + L.examsAt, 16);
    const legal = tw(frame, CARD_AT + L.legalAt, 16);
    const glow = env(frame, LOGO_AT, 20, 999, 1);

    return (
        <AbsoluteFill>
            {intro ? (
                <>
                    <div style={{ position: 'absolute', top: 760, left: 0, right: 0 }}>
                        <KineticText lines={content.first} start={0} size={132} stagger={4} exit={42} />
                    </div>
                    <div style={{ position: 'absolute', top: content.secondTop, left: 0, right: 0 }}>
                        <KineticText lines={content.second} start={60} size={content.secondSize} stagger={4} exit={102} />
                    </div>
                </>
            ) : null}

            {frame >= LOGO_AT - 2 ? (
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
                        <LogoMark size={LOGO.size} assembleAt={LOGO_AT} glossAt={LOGO_AT + 36} />
                    </div>
                    <div style={{ position: 'absolute', top: L.wordTop, left: 0, right: 0, display: 'flex', justifyContent: 'center' }}>
                        <Wordmark size={L.wordSize} revealAt={LOGO_AT + 12} badgeAt={LOGO_AT + 24} />
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
                                {content.title}{' '}
                                <span style={{ fontFamily: FONT.serif, fontStyle: 'italic', fontWeight: 700, fontSize: '1.2em', color: C.goldLight }}>{content.titleAccent}</span>
                            </div>
                            {showDescription ? (
                                <div style={{ marginTop: 14, fontSize: 28, fontWeight: 500, lineHeight: 1.4, color: 'rgba(219,234,254,0.85)', opacity: line(8) }}>
                                    {content.description}
                                </div>
                            ) : null}
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
                                    transform: `scale(${(0.8 + 0.2 * line(12)) * (1 - press * 0.05) * (1 + 0.04 * pulse)})`,
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
                            left: L.fox.left,
                            top: MISSION.top + MISSION.height - L.fox.height,
                            transform: `translateY(${(1 - fox) * 260}px) rotate(${wave}deg) scale(${0.85 + 0.15 * Math.min(1, fox)})`,
                            transformOrigin: '50% 100%',
                            opacity: Math.min(1, fox * 2),
                            filter: 'drop-shadow(0 24px 30px rgba(0,0,0,0.45))',
                        }}
                    >
                        <Fox height={L.fox.height} />
                    </div>

                    {/* Bulle du renard (version allemande : « Los geht's! ») */}
                    {content.foxSays && frame >= CARD_AT + 54 ? (
                        <div
                            style={{
                                position: 'absolute',
                                left: 770,
                                top: MISSION.top - 84,
                                transform: `scale(${bubble})`,
                                transformOrigin: '0% 100%',
                                padding: '14px 24px',
                                borderRadius: 26,
                                borderBottomLeftRadius: 6,
                                background: '#ffffff',
                                color: '#193b67',
                                fontFamily: FONT.sans,
                                fontWeight: 800,
                                fontSize: 34,
                                letterSpacing: '-0.01em',
                                whiteSpace: 'nowrap',
                                boxShadow: '0 20px 40px -12px rgba(0,0,0,0.6)',
                            }}
                        >
                            {content.foxSays}
                        </div>
                    ) : null}

                    {/* Adresse */}
                    <div
                        style={{
                            position: 'absolute',
                            top: L.urlTop,
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
                        {/* Largeur finale réservée : la ligne ne se recentre pas à chaque lettre. */}
                        <span style={{ position: 'relative' }}>
                            <span style={{ visibility: 'hidden' }}>{URL}</span>
                            <span style={{ position: 'absolute', left: 0, top: 0, whiteSpace: 'nowrap' }}>
                                {URL.slice(0, typed)}
                                <span style={{ opacity: typed < URL.length && Math.floor(frame / 6) % 2 === 0 ? 1 : 0, color: C.skyLight }}>|</span>
                            </span>
                        </span>
                    </div>

                    <div
                        style={{
                            position: 'absolute',
                            top: L.examsTop,
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
                        {content.exams.map((line, i) => (
                            <React.Fragment key={line}>
                                {i > 0 ? <br /> : null}
                                {line}
                            </React.Fragment>
                        ))}
                    </div>
                    <div
                        style={{
                            position: 'absolute',
                            top: L.legalTop,
                            left: 90,
                            right: 90,
                            textAlign: 'center',
                            fontFamily: FONT.sans,
                            fontWeight: 500,
                            fontSize: L.legal.size,
                            lineHeight: 1.4,
                            color: L.legal.color,
                            textWrap: L.legal.balance ? 'balance' : undefined,
                            opacity: legal,
                        }}
                    >
                        {content.legal}
                    </div>

                    <Pointer keys={[{ f: BUTTON_TAP, x: MISSION.left + 52 + L.tapX, y: MISSION.top + L.buttonY, tap: true }]} hideAt={BUTTON_TAP + L.tapHide} />
                </>
            ) : null}
        </AbsoluteFill>
    );
};
