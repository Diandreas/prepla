import React from 'react';
import { AbsoluteFill, useCurrentFrame } from 'remotion';
import { AppIcon } from '../components/AppAssets';
import { Flag, Icon, type FlagCode } from '../components/Icons';
import { Move, SceneHeader } from '../components/SceneKit';
import { Card, Pointer } from '../components/UI';
import { usePromoContent } from '../content';
import { C, FONT, SPRING, sp, tw } from '../theme';

// Étape 1 (12–18 s) : choisir la langue puis l'examen (et son niveau s'il y en a), fixer son objectif.

const LANGS: Array<{ code: FlagCode; label: string; x: number }> = [
    { code: 'gb', label: 'Anglais', x: 80 },
    { code: 'fr', label: 'Français', x: 395 },
    { code: 'de', label: 'Allemand', x: 710 },
];
const CARD_W = 290;
const TAP_LANG = 50;
const EXAM_H = 112;
const EXAM_GAP = 26;

// Deux mises en page : sans rangée de niveaux (examens à score) ou avec (Goethe A1 → C2).
const LAYOUT = {
    plain: { cardTop: 720, cardH: 300, examTop: 1090, levelTop: 0, goalTop: 1250, statTop: 1500, tapExam: 86, tapLevel: 0, goalAt: 100, statAt: 118 },
    levels: { cardTop: 690, cardH: 270, examTop: 1000, levelTop: 1146, goalTop: 1286, statTop: 1494, tapExam: 78, tapLevel: 102, goalAt: 112, statAt: 126 },
};
const LEVEL_W = 118;
const LEVEL_GAP = 18;

export const S4Exam: React.FC = () => {
    const frame = useCurrentFrame();
    const content = usePromoContent().exam;
    const L = content.levels ? LAYOUT.levels : LAYOUT.plain;
    const exams = content.exams;
    const examsTotal = exams.reduce((s, e) => s + e.w, 0) + EXAM_GAP * (exams.length - 1);
    let examX = 540 - examsTotal / 2;
    const examPos = exams.map((e) => {
        const x = examX;
        examX += e.w + EXAM_GAP;
        return x;
    });
    const levels = content.levels ?? [];
    const levelsLeft = 540 - (levels.length * LEVEL_W + (levels.length - 1) * LEVEL_GAP) / 2;
    const pickLevel = content.pickLevel ?? 0;

    const langPicked = frame >= TAP_LANG;
    const examPicked = frame >= L.tapExam;
    const levelPicked = levels.length > 0 && frame >= L.tapLevel;
    const dim = tw(frame, TAP_LANG + 2, 10);

    const taps = [
        { f: TAP_LANG, x: LANGS[content.pickLang].x + CARD_W / 2, y: L.cardTop + L.cardH * 0.58, tap: true },
        { f: L.tapExam, x: examPos[0] + exams[0].w / 2, y: L.examTop + EXAM_H / 2 + 6, tap: true },
        ...(levels.length ? [{ f: L.tapLevel, x: levelsLeft + pickLevel * (LEVEL_W + LEVEL_GAP) + LEVEL_W / 2, y: L.levelTop + 50, tap: true }] : []),
    ];

    return (
        <Move enter="none" exit="left" exitAt={172}>
            <SceneHeader tag="Étape 1 / 3" lines={content.headline} start={2} />

            {/* Cartes de langue */}
            {LANGS.map((lang, i) => {
                const pop = sp(frame, 12 + i * 4, SPRING.pop);
                const chosen = i === content.pickLang;
                const select = chosen ? sp(frame, TAP_LANG, SPRING.bouncy) : 0;
                const fade = chosen ? 0 : dim;
                return (
                    <div
                        key={lang.code}
                        style={{
                            position: 'absolute',
                            left: lang.x,
                            top: L.cardTop,
                            width: CARD_W,
                            height: L.cardH,
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
                        {chosen ? <CheckBadge scale={select} /> : null}
                    </div>
                );
            })}

            {/* Examens de la langue choisie */}
            {exams.map((exam, i) => {
                const pop = sp(frame, TAP_LANG + 8 + i * 4, SPRING.pop);
                const chosen = i === 0;
                const select = chosen ? sp(frame, L.tapExam, SPRING.bouncy) : 0;
                const on = chosen && examPicked;
                return (
                    <Chip
                        key={exam.label}
                        left={examPos[i]}
                        top={L.examTop}
                        width={exam.w}
                        height={EXAM_H}
                        on={on}
                        select={select}
                        pop={pop}
                        fade={examPicked && !chosen ? tw(frame, L.tapExam + 2, 10) * 0.45 : 0}
                        label={exam.label}
                    />
                );
            })}

            {/* Niveaux (Goethe A1 → C2) */}
            {levels.map((level, i) => {
                const pop = sp(frame, L.tapExam + 6 + i * 2, SPRING.pop);
                const chosen = i === pickLevel;
                const select = chosen ? sp(frame, L.tapLevel, SPRING.bouncy) : 0;
                return (
                    <Chip
                        key={level}
                        left={levelsLeft + i * (LEVEL_W + LEVEL_GAP)}
                        top={L.levelTop}
                        width={LEVEL_W}
                        height={100}
                        on={chosen && levelPicked}
                        select={select}
                        pop={pop}
                        fade={levelPicked && !chosen ? tw(frame, L.tapLevel + 2, 10) * 0.45 : 0}
                        label={level}
                        size={42}
                        hideCheck
                    />
                );
            })}

            <GoalCard frame={frame} top={L.goalTop} at={L.goalAt} values={content.goal} />

            <Pointer keys={taps} hideAt={taps[taps.length - 1].f + 18} />

            <StatLine frame={frame} top={L.statTop} at={L.statAt} stats={content.stats} />
        </Move>
    );
};

const CheckBadge: React.FC<{ scale: number }> = ({ scale }) => (
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
            transform: `scale(${scale})`,
            boxShadow: '0 10px 24px rgba(0,0,0,0.35)',
        }}
    >
        <Icon name="check" size={38} color="#fff" stroke={3.4} />
    </div>
);

const Chip: React.FC<{
    left: number;
    top: number;
    width: number;
    height: number;
    on: boolean;
    select: number;
    pop: number;
    fade: number;
    label: string;
    size?: number;
    hideCheck?: boolean;
}> = ({ left, top, width, height, on, select, pop, fade, label, size = 46, hideCheck }) => (
    <div
        style={{
            position: 'absolute',
            left,
            top,
            width,
            height,
            borderRadius: 999,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            gap: 14,
            fontFamily: FONT.sans,
            fontWeight: 800,
            fontSize: size,
            letterSpacing: '-0.01em',
            color: on ? '#fff' : C.ink,
            background: on ? `linear-gradient(135deg, ${C.goldLight} 0%, ${C.gold} 100%)` : C.paper,
            boxShadow: on ? '0 0 50px rgba(245,166,35,0.55), 0 30px 50px -20px rgba(0,0,0,0.6)' : '0 30px 50px -20px rgba(0,0,0,0.6)',
            transform: `translateY(${(1 - pop) * 60}px) scale(${(0.6 + 0.4 * pop) * (1 + select * 0.07)})`,
            opacity: Math.min(1, pop * 1.6) * (1 - fade),
        }}
    >
        {on && !hideCheck ? <Icon name="check" size={40} color="#fff" stroke={3.4} style={{ transform: `scale(${select})` }} /> : null}
        {label}
    </div>
);

const GoalCard: React.FC<{ frame: number; top: number; at: number; values: [string, string] }> = ({ frame, top, at, values }) => {
    const pop = sp(frame, at, SPRING.pop);
    const row = (delay: number) => tw(frame, at + delay, 14);
    return (
        <div
            style={{
                position: 'absolute',
                left: 80,
                top,
                width: 920,
                transform: `translateY(${(1 - pop) * 120}px) scale(${0.92 + 0.08 * pop})`,
                opacity: Math.min(1, pop * 1.6),
            }}
        >
            <Card radius={40} padding="34px 40px" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                {[
                    { icon: 'target' as const, tone: 'blue' as const, label: 'Objectif', value: values[0], d: 4 },
                    { icon: 'calendar-days' as const, tone: 'amber' as const, label: 'Mon examen', value: values[1], d: 9 },
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

const StatLine: React.FC<{ frame: number; top: number; at: number; stats: Array<{ value: string; label: string }> }> = ({ frame, top, at, stats }) => {
    const t = tw(frame, at, 18);
    return (
        <AbsoluteFill style={{ pointerEvents: 'none' }}>
            <div
                style={{
                    position: 'absolute',
                    top,
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
                {stats.map((stat, i) => (
                    <React.Fragment key={stat.label}>
                        {i > 0 ? <span style={{ color: C.textDim }}>·</span> : null}
                        <span>
                            {stat.value ? (
                                <>
                                    <b style={{ fontFamily: FONT.serif, fontStyle: 'italic', fontSize: 64, color: C.gold, fontVariantNumeric: 'lining-nums' }}>{stat.value}</b>{' '}
                                </>
                            ) : null}
                            {stat.label}
                        </span>
                    </React.Fragment>
                ))}
            </div>
        </AbsoluteFill>
    );
};
