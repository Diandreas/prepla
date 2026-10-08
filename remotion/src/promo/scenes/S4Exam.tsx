import React from 'react';
import { AbsoluteFill, useCurrentFrame } from 'remotion';
import { AppIcon } from '../components/AppAssets';
import { Flag, Icon, type FlagCode } from '../components/Icons';
import { Move, SceneHeader } from '../components/SceneKit';
import { Card, Pointer } from '../components/UI';
import { C, FONT, SPRING, sp, tw } from '../theme';

// Étape 1 (12–18 s) : choisir la langue puis l'examen, et fixer son objectif.

const LANGS: Array<{ code: FlagCode; label: string; x: number }> = [
    { code: 'gb', label: 'Anglais', x: 80 },
    { code: 'fr', label: 'Français', x: 395 },
    { code: 'de', label: 'Allemand', x: 710 },
];
const CARD_W = 290;
const CARD_H = 300;
const CARD_TOP = 720;
const PICK_LANG = 1;
const TAP_LANG = 50;

const EXAMS = [
    { label: 'TCF', w: 210 },
    { label: 'TEF', w: 210 },
    { label: 'DELF / DALF', w: 380 },
];
const EXAM_TOP = 1090;
const EXAM_H = 112;
const EXAM_GAP = 26;
const TAP_EXAM = 86;
const GOAL_AT = 100;

export const S4Exam: React.FC = () => {
    const frame = useCurrentFrame();
    const examsTotal = EXAMS.reduce((s, e) => s + e.w, 0) + EXAM_GAP * (EXAMS.length - 1);
    let examX = 540 - examsTotal / 2;
    const examPos = EXAMS.map((e) => {
        const x = examX;
        examX += e.w + EXAM_GAP;
        return x;
    });

    const langPicked = frame >= TAP_LANG;
    const examPicked = frame >= TAP_EXAM;
    const dim = tw(frame, TAP_LANG + 2, 10);

    return (
        <Move enter="none" exit="left" exitAt={172}>
            <SceneHeader tag="Étape 1 / 3" lines={['Choisis ton', '*examen*']} start={2} />

            {/* Cartes de langue */}
            {LANGS.map((lang, i) => {
                const pop = sp(frame, 12 + i * 4, SPRING.pop);
                const chosen = i === PICK_LANG;
                const select = chosen ? sp(frame, TAP_LANG, SPRING.bouncy) : 0;
                const fade = chosen ? 0 : dim;
                return (
                    <div
                        key={lang.code}
                        style={{
                            position: 'absolute',
                            left: lang.x,
                            top: CARD_TOP,
                            width: CARD_W,
                            height: CARD_H,
                            transform: `translateY(${(1 - pop) * 300}px) rotate(${(1 - pop) * (i - 1) * 8}deg) scale(${1 + select * 0.06 - fade * 0.05})`,
                            opacity: Math.min(1, pop * 1.5) * (1 - fade * 0.55),
                        }}
                    >
                        <Card
                            radius={40}
                            padding={0}
                            style={{
                                width: '100%',
                                height: '100%',
                                display: 'flex',
                                flexDirection: 'column',
                                alignItems: 'center',
                                justifyContent: 'center',
                                gap: 34,
                                boxShadow: chosen && langPicked
                                    ? `0 0 0 ${6 * Math.min(1, select)}px ${C.sky}, 0 0 60px rgba(59,130,224,0.55), 0 60px 100px -40px rgba(0,0,0,0.7)`
                                    : undefined,
                            }}
                        >
                            <Flag code={lang.code} width={176} radius={14} />
                            <div style={{ fontSize: 44, fontWeight: 800, letterSpacing: '-0.02em' }}>{lang.label}</div>
                        </Card>
                        {chosen ? (
                            <div
                                style={{
                                    position: 'absolute',
                                    top: -22,
                                    right: -22,
                                    width: 72,
                                    height: 72,
                                    borderRadius: '50%',
                                    background: C.sky,
                                    border: '5px solid #fff',
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    transform: `scale(${select})`,
                                    boxShadow: '0 10px 24px rgba(0,0,0,0.35)',
                                }}
                            >
                                <Icon name="check" size={38} color="#fff" stroke={3.4} />
                            </div>
                        ) : null}
                    </div>
                );
            })}

            {/* Examens de la langue choisie */}
            {EXAMS.map((exam, i) => {
                const pop = sp(frame, TAP_LANG + 8 + i * 4, SPRING.pop);
                const chosen = i === 0;
                const select = chosen ? sp(frame, TAP_EXAM, SPRING.bouncy) : 0;
                const on = chosen && examPicked;
                return (
                    <div
                        key={exam.label}
                        style={{
                            position: 'absolute',
                            left: examPos[i],
                            top: EXAM_TOP,
                            width: exam.w,
                            height: EXAM_H,
                            borderRadius: 999,
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            gap: 14,
                            fontFamily: FONT.sans,
                            fontWeight: 800,
                            fontSize: 46,
                            letterSpacing: '-0.01em',
                            color: on ? '#fff' : C.ink,
                            background: on ? `linear-gradient(135deg, ${C.goldLight} 0%, ${C.gold} 100%)` : C.paper,
                            boxShadow: on
                                ? '0 0 50px rgba(245,166,35,0.55), 0 30px 50px -20px rgba(0,0,0,0.6)'
                                : '0 30px 50px -20px rgba(0,0,0,0.6)',
                            transform: `translateY(${(1 - pop) * 60}px) scale(${(0.6 + 0.4 * pop) * (1 + select * 0.07)})`,
                            opacity: Math.min(1, pop * 1.6) * (examPicked && !chosen ? 1 - tw(frame, TAP_EXAM + 2, 10) * 0.45 : 1),
                        }}
                    >
                        {on ? <Icon name="check" size={40} color="#fff" stroke={3.4} style={{ transform: `scale(${select})` }} /> : null}
                        {exam.label}
                    </div>
                );
            })}

            {/* Objectif + date */}
            <GoalCard frame={frame} />

            <Pointer
                keys={[
                    { f: TAP_LANG, x: 540, y: CARD_TOP + 175, tap: true },
                    { f: TAP_EXAM, x: examPos[0] + EXAMS[0].w / 2, y: EXAM_TOP + EXAM_H / 2 + 6, tap: true },
                ]}
                hideAt={TAP_EXAM + 18}
            />

            {/* 3 langues · 8 examens */}
            <StatLine frame={frame} />
        </Move>
    );
};

const GoalCard: React.FC<{ frame: number }> = ({ frame }) => {
    const pop = sp(frame, GOAL_AT, SPRING.pop);
    const row = (delay: number) => tw(frame, GOAL_AT + delay, 14);
    return (
        <div
            style={{
                position: 'absolute',
                left: 80,
                top: 1250,
                width: 920,
                transform: `translateY(${(1 - pop) * 120}px) scale(${0.92 + 0.08 * pop})`,
                opacity: Math.min(1, pop * 1.6),
            }}
        >
            <Card radius={40} padding="34px 40px" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                {[
                    { icon: 'target' as const, tone: 'blue' as const, label: 'Objectif', value: 'Niveau B2', d: 4 },
                    { icon: 'calendar-days' as const, tone: 'amber' as const, label: 'Mon examen', value: 'dans 8 semaines', d: 9 },
                ].map((item, i) => (
                    <div key={i} style={{ display: 'flex', alignItems: 'center', gap: 22, opacity: row(item.d), transform: `translateX(${(1 - row(item.d)) * 30}px)` }}>
                        <AppIcon name={item.icon} size={88} tone={item.tone} shadow={false} />
                        <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
                            <span style={{ fontSize: 28, fontWeight: 600, color: C.inkSoft }}>{item.label}</span>
                            <span style={{ fontSize: 40, fontWeight: 800, letterSpacing: '-0.02em' }}>{item.value}</span>
                        </div>
                    </div>
                ))}
            </Card>
        </div>
    );
};

const StatLine: React.FC<{ frame: number }> = ({ frame }) => {
    const t = tw(frame, 118, 18);
    return (
        <AbsoluteFill style={{ pointerEvents: 'none' }}>
            <div
                style={{
                    position: 'absolute',
                    top: 1500,
                    left: 0,
                    right: 0,
                    display: 'flex',
                    justifyContent: 'center',
                    alignItems: 'baseline',
                    gap: 26,
                    fontFamily: FONT.sans,
                    fontWeight: 700,
                    fontSize: 42,
                    color: C.textMid,
                    opacity: t,
                    transform: `translateY(${(1 - t) * 24}px)`,
                }}
            >
                <span>
                    <b style={{ fontFamily: FONT.serif, fontStyle: 'italic', fontSize: 64, color: C.gold, fontVariantNumeric: 'lining-nums' }}>3</b> langues
                </span>
                <span style={{ color: C.textDim }}>·</span>
                <span>
                    <b style={{ fontFamily: FONT.serif, fontStyle: 'italic', fontSize: 64, color: C.gold, fontVariantNumeric: 'lining-nums' }}>8</b> examens officiels
                </span>
            </div>
        </AbsoluteFill>
    );
};
