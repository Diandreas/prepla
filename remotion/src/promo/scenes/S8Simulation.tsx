import React from 'react';
import { useCurrentFrame } from 'remotion';
import { Icon, type IconName } from '../components/Icons';
import { Move, SceneHeader } from '../components/SceneKit';
import { Card, Pill, ProgressBar } from '../components/UI';
import { BEAT, C, FONT, SPRING, sp, tw } from '../theme';

// Simulation (42–46 s) : le bandeau « MODE EXAMEN » de l'app et les épreuves du TCF Canada.

const SECTIONS: Array<{ icon: IconName; label: string; doneAt?: number; currentAt?: number }> = [
    { icon: 'headphones', label: 'Compréhension orale', doneAt: 30 },
    { icon: 'book', label: 'Compréhension écrite', doneAt: 44 },
    { icon: 'pen', label: 'Expression écrite', currentAt: 50 },
    { icon: 'mic', label: 'Expression orale' },
];

const pad = (n: number) => String(n).padStart(2, '0');

export const S8Simulation: React.FC = () => {
    const frame = useCurrentFrame();
    const totalSeconds = 2 * 3600 + 47 * 60 - Math.max(0, Math.floor((frame - 8) / BEAT) + 1);
    const time = `${Math.floor(totalSeconds / 3600)}:${pad(Math.floor((totalSeconds % 3600) / 60))}:${pad(totalSeconds % 60)}`;
    const pill = sp(frame, 6, SPRING.bouncy);
    const tick = 1 + 0.025 * Math.max(0, 1 - ((frame - 8) % BEAT) / 5);
    const card = sp(frame, 12, SPRING.soft);

    return (
        <Move enter="bottom" exit="top" exitAt={106}>
            <SceneHeader tag="Mode examen" lines={['Simule le', '*jour J*']} start={2} sub="Examens blancs chronométrés, en conditions réelles." exit={104} />

            {/* Bandeau chrono, comme dans l'app */}
            <div style={{ position: 'absolute', top: 770, left: 0, right: 0, display: 'flex', justifyContent: 'center' }}>
                <div
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: 30,
                        padding: '26px 52px 26px 30px',
                        borderRadius: 999,
                        background: 'linear-gradient(180deg, #172a4b 0%, #0f1d36 100%)',
                        border: '2.5px solid rgba(255,255,255,0.14)',
                        boxShadow: '0 40px 80px -30px rgba(0,0,0,0.8), 0 0 60px rgba(59,130,224,0.25)',
                        transform: `translateY(${(1 - pill) * -120}px) scale(${(0.7 + 0.3 * pill) * tick})`,
                        opacity: Math.min(1, pill * 1.6),
                        fontFamily: FONT.sans,
                    }}
                >
                    <div
                        style={{
                            width: 100,
                            height: 100,
                            borderRadius: '50%',
                            background: 'rgba(255,255,255,0.1)',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                        }}
                    >
                        <Icon name="timer" size={58} color="#fff" stroke={2.2} />
                    </div>
                    <span style={{ fontSize: 104, fontWeight: 800, letterSpacing: '-0.02em', color: C.text, fontVariantNumeric: 'tabular-nums' }}>{time}</span>
                    <span style={{ fontSize: 30, fontWeight: 800, letterSpacing: '0.14em', color: C.skyLight }}>
                        MODE
                        <br />
                        EXAMEN
                    </span>
                </div>
            </div>

            {/* Épreuves */}
            <div
                style={{
                    position: 'absolute',
                    left: 80,
                    top: 990,
                    width: 920,
                    transform: `translateY(${(1 - card) * 200}px)`,
                    opacity: Math.min(1, card * 1.5),
                }}
            >
                <Card padding="38px 44px">
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20 }}>
                        <span style={{ fontSize: 36, fontWeight: 800, letterSpacing: '-0.02em' }}>TCF Canada · Examen blanc</span>
                        <Pill tone="neutral" size={26}>
                            4 épreuves
                        </Pill>
                    </div>
                    {SECTIONS.map((s, i) => {
                        const rowIn = tw(frame, 18 + i * 5, 14);
                        const done = s.doneAt !== undefined && frame >= s.doneAt;
                        const current = s.currentAt !== undefined && frame >= s.currentAt;
                        const check = s.doneAt !== undefined ? sp(frame, s.doneAt, SPRING.bouncy) : 0;
                        const pulse = current ? (frame % BEAT) / BEAT : 0;
                        return (
                            <div
                                key={s.label}
                                style={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: 24,
                                    padding: '18px 0',
                                    borderTop: i === 0 ? undefined : `2px solid ${C.paperLine}`,
                                    opacity: rowIn,
                                    transform: `translateX(${(1 - rowIn) * 40}px)`,
                                }}
                            >
                                <div
                                    style={{
                                        width: 72,
                                        height: 72,
                                        borderRadius: 22,
                                        background: done ? C.greenPale : current ? C.skyPale : '#eef2f7',
                                        display: 'flex',
                                        alignItems: 'center',
                                        justifyContent: 'center',
                                        flexShrink: 0,
                                    }}
                                >
                                    <Icon name={s.icon} size={38} color={done ? '#138a3e' : current ? C.sky : C.inkDim} stroke={2.4} />
                                </div>
                                <div style={{ flex: 1 }}>
                                    <div style={{ fontSize: 38, fontWeight: 700, color: done || current ? C.ink : C.inkSoft }}>{s.label}</div>
                                    {current ? (
                                        <div style={{ marginTop: 12 }}>
                                            <ProgressBar value={0.15 + tw(frame, s.currentAt ?? 0, 60) * 0.35} width={520} height={12} />
                                        </div>
                                    ) : null}
                                </div>
                                {done ? (
                                    <div
                                        style={{
                                            width: 58,
                                            height: 58,
                                            borderRadius: '50%',
                                            background: C.green,
                                            display: 'flex',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            transform: `scale(${check})`,
                                        }}
                                    >
                                        <Icon name="check" size={32} color="#fff" stroke={3.4} />
                                    </div>
                                ) : current ? (
                                    <div style={{ position: 'relative', width: 58, height: 58 }}>
                                        <div style={{ position: 'absolute', inset: 0, borderRadius: '50%', border: `5px solid ${C.sky}` }} />
                                        <div
                                            style={{
                                                position: 'absolute',
                                                inset: 0,
                                                borderRadius: '50%',
                                                border: `4px solid ${C.sky}`,
                                                transform: `scale(${1 + pulse * 0.7})`,
                                                opacity: 1 - pulse,
                                            }}
                                        />
                                        <div style={{ position: 'absolute', inset: 17, borderRadius: '50%', background: C.sky }} />
                                    </div>
                                ) : (
                                    <div style={{ width: 58, height: 58, borderRadius: '50%', border: `4px solid ${C.paperLine}` }} />
                                )}
                            </div>
                        );
                    })}
                </Card>
            </div>
        </Move>
    );
};
