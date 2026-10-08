import React from 'react';
import { useCurrentFrame } from 'remotion';
import { AppGif, AppIcon, type AppIconName, type AppTone } from '../components/AppAssets';
import { type IconName } from '../components/Icons';
import { SceneTag } from '../components/KineticText';
import { Move, SceneHeader, TAG_TOP } from '../components/SceneKit';
import { Card, Pill } from '../components/UI';
import { C, EASE, FONT, SPRING, env, sp, tw } from '../theme';

// Correction IA (34–42 s) : une phrase d'expression écrite est tapée, analysée, la faute est
// barrée, corrigée et expliquée. Puis l'éventail des formats d'exercices de l'app.

const BEFORE = "Si j'";
const ERROR = 'aurais';
const AFTER = ' plus de temps, je voyagerais davantage.';
const FULL = BEFORE + ERROR + AFTER;
const TYPE = { start: 14, end: 54 };
const SCAN = { start: 58, dur: 16 };
const MARK = 76;
const STRIKE = 82;
const FIX = 88;
const EXPLAIN = 100;
const XP = 118;
const PHASE_B = 152;

// Libellés réels des formats (resources/js/lib/exercise-schemas.ts).
const ROWS = [
    ['QCM', 'Vrai / Faux / Non mentionné', 'Texte à trous', 'Dictée', 'Association', 'Décrire un graphique'],
    ['Jeu de rôle', 'Écouter et répéter', 'Rédaction / essai', 'Construire la phrase', 'Synthèse', 'Compléter un tableau'],
    ['Discussion académique', 'Écouter et répondre', 'Insérer une phrase', 'Choisir la bonne image', 'Formation de mots', 'Expression orale'],
];

export const S7AI: React.FC = () => {
    const frame = useCurrentFrame();
    return (
        <Move enter="zoom" exit="top" exitAt={232}>
            {frame < PHASE_B + 2 ? <PhaseA frame={frame} /> : null}
            {frame >= PHASE_B ? <PhaseB frame={frame} /> : null}
        </Move>
    );
};

const PhaseA: React.FC<{ frame: number }> = ({ frame }) => {
    const typed = Math.round(tw(frame, TYPE.start, TYPE.end - TYPE.start, 0, FULL.length, (t) => t));
    const part = (from: number, text: string) => text.slice(0, Math.max(0, Math.min(text.length, typed - from)));
    const caretOn = frame < SCAN.start && Math.floor(frame / 8) % 2 === 0;
    const cardPop = sp(frame, 4, SPRING.soft);
    const out = tw(frame, PHASE_B - 12, 12, 0, 1, EASE.in);
    const scan = tw(frame, SCAN.start, SCAN.dur, 0, 1, EASE.inOutSoft);
    const scanning = frame >= SCAN.start && frame < SCAN.start + SCAN.dur + 4;
    const mark = tw(frame, MARK, 8);
    const strike = tw(frame, STRIKE, 8, 0, 1, EASE.inOutSoft);
    const fix = sp(frame, FIX, SPRING.bouncy);
    const explain = sp(frame, EXPLAIN, SPRING.soft);
    const xp = sp(frame, XP, SPRING.bouncy);
    const status: { tone: 'neutral' | 'sky' | 'green'; icon?: IconName; text: string } =
        frame >= FIX ? { tone: 'green', icon: 'check', text: 'Corrigé' } : scanning ? { tone: 'sky', icon: 'sparkles', text: 'Analyse…' } : { tone: 'neutral', text: 'TCF · Tâche 2' };

    return (
        <>
            <SceneHeader tag="Correction IA" lines={["L'*IA* te corrige"]} size={112} start={2} sub="…et t'explique pourquoi." exit={PHASE_B - 16} />
            <div
                style={{
                    position: 'absolute',
                    left: 80,
                    top: 680,
                    width: 920,
                    transform: `translateY(${(1 - cardPop) * 160 + out * 340}px) scale(${(0.92 + 0.08 * cardPop) * (1 - out * 0.12)})`,
                    opacity: Math.min(1, cardPop * 1.5) * (1 - out),
                }}
            >
                <Card padding={44}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: 20 }}>
                            <AppIcon name="writing" size={80} tone="blue" shadow={false} />
                            <span style={{ fontSize: 38, fontWeight: 800, letterSpacing: '-0.02em' }}>Expression écrite</span>
                        </div>
                        {scanning && frame < FIX ? (
                            <div
                                style={{
                                    display: 'inline-flex',
                                    alignItems: 'center',
                                    gap: 10,
                                    padding: '8px 22px 8px 12px',
                                    borderRadius: 999,
                                    background: C.skyPale,
                                    border: '2px solid rgba(59,130,224,0.35)',
                                    color: '#1d63c4',
                                    fontSize: 28,
                                    fontWeight: 700,
                                }}
                            >
                                <AppGif name="loading" width={44} height={44} />
                                Analyse…
                            </div>
                        ) : (
                            <Pill tone={status.tone} icon={status.icon} size={28}>
                                {status.text}
                            </Pill>
                        )}
                    </div>

                    <div
                        style={{
                            position: 'relative',
                            marginTop: 30,
                            padding: '30px 34px',
                            minHeight: 230,
                            borderRadius: 28,
                            background: C.paperSoft,
                            border: `2.5px solid ${C.paperLine}`,
                            fontSize: 50,
                            fontWeight: 600,
                            lineHeight: 1.55,
                            color: C.ink,
                            overflow: 'visible',
                        }}
                    >
                        <span>{part(0, BEFORE)}</span>
                        <span style={{ position: 'relative', display: 'inline-block' }}>
                            <span
                                style={{
                                    position: 'absolute',
                                    left: -6,
                                    right: -6,
                                    top: '12%',
                                    bottom: '8%',
                                    borderRadius: 10,
                                    background: `rgba(239,68,68,${0.16 * mark})`,
                                }}
                            />
                            <span style={{ position: 'relative', color: mark > 0.5 ? '#c42b2b' : C.ink }}>{part(BEFORE.length, ERROR)}</span>
                            <span
                                style={{
                                    position: 'absolute',
                                    left: -4,
                                    top: '54%',
                                    height: 6,
                                    width: `calc(${strike * 100}% + 8px)`,
                                    borderRadius: 3,
                                    background: C.red,
                                }}
                            />
                            {frame >= FIX ? (
                                <span
                                    style={{
                                        position: 'absolute',
                                        left: '50%',
                                        bottom: '92%',
                                        transform: `translateX(-50%) scale(${fix})`,
                                        transformOrigin: '50% 100%',
                                        padding: '6px 22px',
                                        borderRadius: 16,
                                        background: C.green,
                                        color: '#fff',
                                        fontSize: 44,
                                        fontWeight: 800,
                                        lineHeight: 1.2,
                                        whiteSpace: 'nowrap',
                                        boxShadow: '0 12px 24px -8px rgba(19,138,62,0.6)',
                                    }}
                                >
                                    avais
                                </span>
                            ) : null}
                        </span>
                        <span>{part(BEFORE.length + ERROR.length, AFTER)}</span>
                        {caretOn ? <span style={{ display: 'inline-block', width: 5, height: 56, marginLeft: 4, background: C.sky, verticalAlign: 'middle' }} /> : null}

                        {scanning ? (
                            <div
                                style={{
                                    position: 'absolute',
                                    left: 0,
                                    right: 0,
                                    top: `${-20 + scan * 100}%`,
                                    height: 90,
                                    background: 'linear-gradient(180deg, rgba(59,130,224,0) 0%, rgba(59,130,224,0.28) 50%, rgba(59,130,224,0) 100%)',
                                    borderTop: `3px solid rgba(59,130,224,${0.6 * env(frame, SCAN.start, 4, SCAN.start + SCAN.dur, 4)})`,
                                }}
                            />
                        ) : null}
                    </div>

                    <div
                        style={{
                            marginTop: 26,
                            display: 'flex',
                            gap: 22,
                            alignItems: 'flex-start',
                            padding: '26px 30px',
                            borderRadius: 26,
                            background: C.skyPale,
                            border: '2.5px solid rgba(59,130,224,0.25)',
                            transform: `translateY(${(1 - explain) * 30}px)`,
                            opacity: Math.min(1, explain * 1.5),
                        }}
                    >
                        <AppIcon name="lightbulb" size={68} tone="amber" shadow={false} />
                        <div style={{ fontSize: 36, fontWeight: 600, lineHeight: 1.4, color: C.ink }}>
                            Après « <b>si</b> », on utilise l'<b style={{ color: '#1d63c4' }}>imparfait</b>, pas le conditionnel.
                        </div>
                    </div>
                </Card>

                <div
                    style={{
                        position: 'absolute',
                        right: -18,
                        top: -40,
                        transform: `scale(${xp}) rotate(${(1 - xp) * 30 + 6}deg)`,
                        padding: '8px 28px 8px 8px',
                        borderRadius: 999,
                        background: `linear-gradient(135deg, ${C.goldLight}, ${C.gold})`,
                        color: '#1b1204',
                        fontFamily: FONT.sans,
                        fontWeight: 800,
                        fontSize: 40,
                        boxShadow: '0 0 40px rgba(245,166,35,0.6), 0 16px 30px rgba(0,0,0,0.4)',
                        display: 'flex',
                        alignItems: 'center',
                        gap: 10,
                    }}
                >
                    <AppIcon name="zap" size={58} tone="amber" shadow={false} />
                    +15 XP
                </div>
                {frame >= XP && frame < XP + 40 ? (
                    <div style={{ position: 'absolute', right: -70, top: -130, opacity: 1 - tw(frame, XP + 26, 12) }}>
                        <AppGif name="star" width={200} height={200} />
                    </div>
                ) : null}
            </div>
        </>
    );
};

const SKILLS: Array<{ icon: AppIconName; tone: AppTone; label: string }> = [
    { icon: 'courses', tone: 'blue', label: 'Lecture' },
    { icon: 'listening', tone: 'mint', label: 'Écoute' },
    { icon: 'writing', tone: 'amber', label: 'Écrit' },
    { icon: 'speaking', tone: 'rose', label: 'Oral' },
];

const PhaseB: React.FC<{ frame: number }> = ({ frame }) => {
    const f = frame - PHASE_B;
    const count = Math.round(tw(f, 6, 24, 0, 30, EASE.out));
    const plus = sp(f, 30, SPRING.bouncy);
    const numIn = sp(f, 4, SPRING.pop);
    const label = tw(f, 12, 16);
    const sub = tw(f, 18, 16);
    return (
        <>
            <div style={{ position: 'absolute', top: TAG_TOP, left: 0, right: 0, display: 'flex', justifyContent: 'center' }}>
                <SceneTag label="Entraînement complet" start={PHASE_B + 2} />
            </div>
            <div
                style={{
                    position: 'absolute',
                    top: 400,
                    left: 0,
                    right: 0,
                    display: 'flex',
                    justifyContent: 'center',
                    alignItems: 'baseline',
                    fontFamily: FONT.serif,
                    fontStyle: 'italic',
                    fontWeight: 700,
                    fontSize: 300,
                    lineHeight: 1,
                    color: C.gold,
                    transform: `scale(${0.6 + 0.4 * numIn})`,
                    opacity: Math.min(1, numIn * 1.5),
                    textShadow: '0 0 60px rgba(245,166,35,0.35)',
                }}
            >
                <span style={{ fontVariantNumeric: 'lining-nums tabular-nums' }}>{count}</span>
                <span style={{ display: 'inline-block', transform: `scale(${plus})`, transformOrigin: '20% 70%' }}>+</span>
            </div>
            <div
                style={{
                    position: 'absolute',
                    top: 720,
                    left: 0,
                    right: 0,
                    textAlign: 'center',
                    fontFamily: FONT.sans,
                    fontWeight: 800,
                    fontSize: 76,
                    letterSpacing: '-0.03em',
                    color: C.text,
                    opacity: label,
                    transform: `translateY(${(1 - label) * 30}px)`,
                }}
            >
                formats d'exercices
            </div>
            <div
                style={{
                    position: 'absolute',
                    top: 818,
                    left: 0,
                    right: 0,
                    textAlign: 'center',
                    fontFamily: FONT.sans,
                    fontWeight: 600,
                    fontSize: 42,
                    color: C.textMid,
                    opacity: sub,
                    transform: `translateY(${(1 - sub) * 20}px)`,
                }}
            >
                comme le jour de l'examen
            </div>

            {ROWS.map((row, r) => {
                const dir = r % 2 === 0 ? -1 : 1;
                const speed = 5.2 + r * 0.8;
                const appear = tw(f, 10 + r * 4, 14);
                const offset = dir * f * speed + (dir > 0 ? -900 : 0);
                return (
                    <div
                        key={r}
                        style={{
                            position: 'absolute',
                            top: 950 + r * 112,
                            left: 0,
                            display: 'flex',
                            gap: 20,
                            transform: `translateX(${offset - r * 140}px)`,
                            opacity: appear,
                            WebkitMaskImage: 'linear-gradient(90deg, transparent 0%, black 12%, black 88%, transparent 100%)',
                        }}
                    >
                        {[...row, ...row].map((label, i) => (
                            <div
                                key={i}
                                style={{
                                    padding: '22px 36px',
                                    borderRadius: 999,
                                    background: i % 3 === r % 3 ? 'rgba(59,130,224,0.18)' : 'rgba(255,255,255,0.07)',
                                    border: `2px solid ${i % 3 === r % 3 ? 'rgba(106,170,246,0.55)' : 'rgba(255,255,255,0.14)'}`,
                                    color: C.text,
                                    fontFamily: FONT.sans,
                                    fontWeight: 700,
                                    fontSize: 38,
                                    whiteSpace: 'nowrap',
                                }}
                            >
                                {label}
                            </div>
                        ))}
                    </div>
                );
            })}

            <div style={{ position: 'absolute', top: 1310, left: 80, width: 920, display: 'flex', gap: 20 }}>
                {SKILLS.map((s, i) => {
                    const pop = sp(f, 26 + i * 4, SPRING.pop);
                    return (
                        <div
                            key={s.label}
                            style={{
                                flex: 1,
                                height: 190,
                                borderRadius: 32,
                                background: 'linear-gradient(160deg, rgba(36,56,94,0.95), rgba(17,29,52,0.95))',
                                border: '2px solid rgba(255,255,255,0.12)',
                                display: 'flex',
                                flexDirection: 'column',
                                alignItems: 'center',
                                justifyContent: 'center',
                                gap: 18,
                                transform: `translateY(${(1 - pop) * 80}px) scale(${0.8 + 0.2 * pop})`,
                                opacity: Math.min(1, pop * 1.6),
                                fontFamily: FONT.sans,
                                fontWeight: 800,
                                fontSize: 36,
                                color: C.text,
                            }}
                        >
                            <AppIcon name={s.icon} size={92} tone={s.tone} />
                            {s.label}
                        </div>
                    );
                })}
            </div>
        </>
    );
};
