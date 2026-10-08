import { noise2D } from '@remotion/noise';
import React from 'react';
import { useCurrentFrame } from 'remotion';
import { AppGif, AppIcon } from '../components/AppAssets';
import { Icon } from '../components/Icons';
import { Move, SceneHeader } from '../components/SceneKit';
import { Card, Pill, ProgressBar } from '../components/UI';
import { C, EASE, FONT, SPRING, sp, tw } from '../theme';

// Progrès (46–52 s) : compétences, série de jours, objectif B2, erreurs à revoir.
// En sortie, les cartes sont aspirées vers le centre pour laisser place au message final.

const SKILLS = [
    { label: 'Compréhension écrite', value: 78, color: C.sky },
    { label: 'Compréhension orale', value: 64, color: C.sky },
    { label: 'Expression écrite', value: 58, color: C.gold },
    { label: 'Expression orale', value: 46, color: C.gold },
];
const COLLAPSE = 158;
const CENTER = { x: 540, y: 1060 };

const Collapse: React.FC<{ frame: number; index: number; box: { x: number; y: number; w: number; h: number }; children: React.ReactNode }> = ({
    frame,
    index,
    box,
    children,
}) => {
    const pop = sp(frame, 8 + index * 6, SPRING.soft);
    const k = tw(frame, COLLAPSE + index * 2, 16, 0, 1, EASE.in);
    const cx = box.x + box.w / 2;
    const cy = box.y + box.h / 2;
    return (
        <div
            style={{
                position: 'absolute',
                left: box.x,
                top: box.y,
                width: box.w,
                transform: `translate(${(CENTER.x - cx) * k}px, ${(CENTER.y - cy) * k + (1 - pop) * 160}px) scale(${(0.9 + 0.1 * pop) * (1 - k * 0.92)}) rotate(${k * (index % 2 ? 14 : -14)}deg)`,
                opacity: Math.min(1, pop * 1.5) * (1 - Math.pow(k, 2)),
            }}
        >
            {children}
        </div>
    );
};

export const S9Progress: React.FC = () => {
    const frame = useCurrentFrame();
    const streak = Math.round(tw(frame, 30, 26, 1, 12, EASE.out));
    const ring = tw(frame, 36, 40, 0, 0.72, EASE.out);
    const r = 66;
    const circ = 2 * Math.PI * r;
    const flame = 1 + noise2D('flame', frame * 0.25, 0) * 0.06;

    return (
        <Move enter="bottom">
            <SceneHeader tag="Suivi" lines={['Vois tes', '*progrès*']} start={2} exit={COLLAPSE - 2} />

            <Collapse frame={frame} index={0} box={{ x: 80, y: 692, w: 920, h: 470 }}>
                <Card padding="40px 44px">
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: 18 }}>
                            <AppIcon name="statistics" size={68} tone="blue" shadow={false} />
                            <span style={{ fontSize: 38, fontWeight: 800, letterSpacing: '-0.02em' }}>Tes compétences</span>
                        </div>
                        <Pill tone="sky" size={26}>
                            TCF · B1
                        </Pill>
                    </div>
                    {SKILLS.map((s, i) => {
                        const v = tw(frame, 16 + i * 5, 34, 0, s.value, EASE.out);
                        return (
                            <div key={s.label} style={{ marginTop: 22 }}>
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline', marginBottom: 12 }}>
                                    <span style={{ fontSize: 32, fontWeight: 700, color: C.ink }}>{s.label}</span>
                                    <span style={{ fontSize: 34, fontWeight: 800, color: s.color === C.gold ? '#c27a06' : '#1d63c4', fontVariantNumeric: 'tabular-nums' }}>
                                        {Math.round(v)} %
                                    </span>
                                </div>
                                <ProgressBar value={v / 100} width={832} height={16} color={s.color} />
                            </div>
                        );
                    })}
                </Card>
            </Collapse>

            <Collapse frame={frame} index={1} box={{ x: 80, y: 1192, w: 440, h: 220 }}>
                <Card padding="30px 34px" style={{ display: 'flex', alignItems: 'center', gap: 24, height: 220, boxSizing: 'border-box' }}>
                    <div style={{ width: 108, height: 150, flexShrink: 0, transform: `scale(${flame})`, filter: 'drop-shadow(0 10px 18px rgba(242,104,58,0.45))' }}>
                        <AppGif name="Fire" width={108} height={150} />
                    </div>
                    <div>
                        <div style={{ fontSize: 60, fontWeight: 800, letterSpacing: '-0.03em', lineHeight: 1, fontVariantNumeric: 'tabular-nums' }}>{streak} jours</div>
                        <div style={{ marginTop: 8, fontSize: 28, fontWeight: 600, color: C.inkSoft }}>d'affilée</div>
                    </div>
                </Card>
            </Collapse>

            <Collapse frame={frame} index={2} box={{ x: 540, y: 1192, w: 460, h: 220 }}>
                <Card padding="26px 30px" style={{ display: 'flex', alignItems: 'center', gap: 22, height: 220, boxSizing: 'border-box' }}>
                    <svg width={160} height={160} style={{ flexShrink: 0 }}>
                        <circle cx={80} cy={80} r={r} fill="none" stroke="#e6edf6" strokeWidth={16} />
                        <circle
                            cx={80}
                            cy={80}
                            r={r}
                            fill="none"
                            stroke={C.sky}
                            strokeWidth={16}
                            strokeLinecap="round"
                            strokeDasharray={`${circ * ring} ${circ}`}
                            transform="rotate(-90 80 80)"
                        />
                        <text x={80} y={92} textAnchor="middle" fontFamily={FONT.sans} fontWeight={800} fontSize={36} fill={C.ink}>
                            {Math.round(ring * 100)}%
                        </text>
                    </svg>
                    <div>
                        <div style={{ fontSize: 26, fontWeight: 700, color: C.inkSoft }}>Objectif</div>
                        <div style={{ fontSize: 46, fontWeight: 800, letterSpacing: '-0.02em' }}>Vers le B2</div>
                    </div>
                </Card>
            </Collapse>

            <Collapse frame={frame} index={3} box={{ x: 80, y: 1438, w: 920, h: 112 }}>
                <div
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: 22,
                        padding: '22px 30px',
                        borderRadius: 30,
                        background: 'linear-gradient(160deg, rgba(36,56,94,0.95), rgba(17,29,52,0.95))',
                        border: '2px solid rgba(245,166,35,0.45)',
                        fontFamily: FONT.sans,
                        color: C.text,
                        boxShadow: '0 30px 60px -30px rgba(0,0,0,0.8)',
                    }}
                >
                    <AppIcon name="review" size={72} tone="amber" shadow={false} />
                    <span style={{ flex: 1, fontSize: 36, fontWeight: 700 }}>
                        <b style={{ color: C.gold }}>6 erreurs</b> à revoir aujourd'hui
                    </span>
                    <Icon name="arrowRight" size={40} color={C.textMid} stroke={2.6} />
                </div>
            </Collapse>
        </Move>
    );
};
