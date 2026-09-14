import React from 'react';
import { AbsoluteFill, Audio, Sequence, interpolate, spring, staticFile, useCurrentFrame, useVideoConfig } from 'remotion';

export interface Episode {
    id: string;
    exam: string;
    flag: string;
    hook: string;
    question: string;
    options: string[];
    correctIndex: number;
    cta: string;
}

export interface QuizCardProps {
    episode: Episode;
    audioSrc: string;
    revealHoldSeconds?: number;
    outroSeconds?: number;
}

const COLORS = {
    bg: '#0b0d12',
    bgCard: 'rgba(255,255,255,0.06)',
    border: 'rgba(255,255,255,0.12)',
    text: '#f5f6f8',
    textMid: '#b9bec9',
    sky: '#3B82E0',
    gold: '#F5A623',
    green: '#22c55e',
};

function useSpringIn(delaySeconds: number, durationSeconds = 0.5) {
    const frame = useCurrentFrame();
    const { fps } = useVideoConfig();
    const delayFrames = delaySeconds * fps;
    return spring({
        frame: frame - delayFrames,
        fps,
        durationInFrames: durationSeconds * fps,
        config: { damping: 200 },
    });
}

export const QuizCard: React.FC<QuizCardProps> = ({
    episode,
    audioSrc,
    revealHoldSeconds = 2.5,
    outroSeconds = 3,
}) => {
    const frame = useCurrentFrame();
    const { fps, durationInFrames, width, height } = useVideoConfig();

    const outroFrames = outroSeconds * fps;
    const revealHoldFrames = revealHoldSeconds * fps;
    const revealStartFrame = durationInFrames - outroFrames - revealHoldFrames;
    const outroStartFrame = durationInFrames - outroFrames;

    const badgeIn = useSpringIn(0.15);
    const hookIn = useSpringIn(0.5);
    const hookOut = interpolate(frame, [2.6 * fps, 3.1 * fps], [1, 0], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
    const cardIn = useSpringIn(2.6, 0.6);

    const revealed = frame >= revealStartFrame;
    const revealProgress = spring({
        frame: frame - revealStartFrame,
        fps,
        durationInFrames: 0.4 * fps,
        config: { damping: 200 },
    });

    const outroProgress = interpolate(frame, [outroStartFrame, outroStartFrame + 0.6 * fps], [0, 1], {
        extrapolateLeft: 'clamp',
        extrapolateRight: 'clamp',
    });
    const cardExit = interpolate(frame, [outroStartFrame, outroStartFrame + 0.5 * fps], [0, 40], {
        extrapolateLeft: 'clamp',
        extrapolateRight: 'clamp',
    });
    const cardFade = interpolate(frame, [outroStartFrame, outroStartFrame + 0.5 * fps], [1, 0.15], {
        extrapolateLeft: 'clamp',
        extrapolateRight: 'clamp',
    });

    return (
        <AbsoluteFill style={{ background: COLORS.bg, fontFamily: '"Plus Jakarta Sans", system-ui, sans-serif' }}>
            <Audio src={staticFile(audioSrc)} />

            {/* Glow */}
            <div
                style={{
                    position: 'absolute',
                    top: height * 0.1,
                    left: width * 0.15,
                    width: width * 0.7,
                    height: width * 0.7,
                    borderRadius: '50%',
                    background: `${COLORS.gold}22`,
                    filter: 'blur(120px)',
                }}
            />
            <div
                style={{
                    position: 'absolute',
                    bottom: height * 0.05,
                    right: width * 0.1,
                    width: width * 0.6,
                    height: width * 0.6,
                    borderRadius: '50%',
                    background: `${COLORS.sky}22`,
                    filter: 'blur(120px)',
                }}
            />

            <AbsoluteFill style={{ padding: 80, justifyContent: 'center', alignItems: 'center' }}>
                {/* Badge */}
                <div
                    style={{
                        opacity: badgeIn,
                        transform: `translateY(${(1 - badgeIn) * 20}px)`,
                        display: 'flex',
                        alignItems: 'center',
                        gap: 12,
                        padding: '14px 28px',
                        borderRadius: 999,
                        border: `1px solid ${COLORS.gold}55`,
                        background: `${COLORS.gold}14`,
                        marginBottom: 40,
                    }}
                >
                    <span style={{ fontSize: 34 }}>{episode.flag}</span>
                    <span style={{ color: COLORS.gold, fontWeight: 700, fontSize: 30, letterSpacing: 1 }}>{episode.exam}</span>
                </div>

                {/* Hook */}
                <div
                    style={{
                        opacity: hookIn * hookOut,
                        transform: `translateY(${(1 - hookIn) * 24}px)`,
                        position: 'absolute',
                        top: height * 0.32,
                        textAlign: 'center',
                        fontSize: 56,
                        fontWeight: 800,
                        color: COLORS.text,
                        maxWidth: width * 0.8,
                        lineHeight: 1.15,
                    }}
                >
                    {episode.hook}
                </div>

                {/* Quiz card */}
                <div
                    style={{
                        opacity: cardIn * (1 - (revealed ? 0 : 0)) * (outroStartFrame > frame ? 1 : cardFade),
                        transform: `scale(${0.9 + cardIn * 0.1}) translateY(${(1 - cardIn) * 30 + cardExit}px)`,
                        width: width * 0.82,
                        borderRadius: 32,
                        border: `1px solid ${COLORS.border}`,
                        background: 'linear-gradient(135deg, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0.03) 100%)',
                        padding: 48,
                        boxShadow: '0 40px 100px rgba(0,0,0,0.5)',
                    }}
                >
                    <div style={{ color: COLORS.text, fontSize: 42, lineHeight: 1.35, marginBottom: 36, fontWeight: 600 }}>
                        {episode.question}
                    </div>

                    <div style={{ display: 'flex', flexDirection: 'column', gap: 18 }}>
                        {episode.options.map((opt, i) => {
                            const optionDelay = 3.0 + i * 0.5;
                            const optIn = spring({
                                frame: frame - optionDelay * fps,
                                fps,
                                durationInFrames: 0.4 * fps,
                                config: { damping: 200 },
                            });
                            const isCorrect = i === episode.correctIndex;
                            const highlight = revealed && isCorrect ? revealProgress : 0;

                            return (
                                <div
                                    key={i}
                                    style={{
                                        opacity: optIn,
                                        transform: `translateX(${(1 - optIn) * -30}px)`,
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: 20,
                                        padding: '22px 28px',
                                        borderRadius: 18,
                                        border: `2px solid ${highlight > 0 ? COLORS.green : COLORS.border}`,
                                        background: highlight > 0 ? `rgba(34,197,94,${0.08 + highlight * 0.08})` : 'rgba(255,255,255,0.03)',
                                    }}
                                >
                                    <span
                                        style={{
                                            width: 44,
                                            height: 44,
                                            borderRadius: '50%',
                                            display: 'flex',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            fontWeight: 800,
                                            fontSize: 26,
                                            flexShrink: 0,
                                            background: highlight > 0 ? COLORS.green : 'rgba(255,255,255,0.08)',
                                            color: highlight > 0 ? '#fff' : COLORS.textMid,
                                        }}
                                    >
                                        {String.fromCharCode(65 + i)}
                                    </span>
                                    <span style={{ color: COLORS.text, fontSize: 32, fontWeight: 500 }}>{opt}</span>
                                    {highlight > 0.5 && (
                                        <span style={{ marginLeft: 'auto', color: COLORS.green, fontSize: 34, fontWeight: 800 }}>✓</span>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </div>

                {/* Outro CTA */}
                <Sequence from={outroStartFrame} durationInFrames={outroFrames}>
                    <div
                        style={{
                            position: 'absolute',
                            bottom: height * 0.12,
                            opacity: outroProgress,
                            transform: `translateY(${(1 - outroProgress) * 30}px)`,
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: 'center',
                            gap: 16,
                        }}
                    >
                        <div style={{ color: COLORS.gold, fontSize: 30, fontWeight: 700, letterSpacing: 1 }}>{episode.cta}</div>
                        <div style={{ color: COLORS.text, fontSize: 54, fontWeight: 900, letterSpacing: -1 }}>
                            Prepla
                        </div>
                    </div>
                </Sequence>
            </AbsoluteFill>
        </AbsoluteFill>
    );
};
