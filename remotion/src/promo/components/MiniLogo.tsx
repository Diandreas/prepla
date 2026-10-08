import React from 'react';
import { useCurrentFrame } from 'remotion';
import { EASE, tw } from '../theme';
import { LOGO_RATIO, LogoMark, Wordmark } from './Logo';

// Logo discret en haut à gauche, sous la zone des boutons TikTok/Reels.
const MARK_SIZE = 66;
const LEFT = 76;
const TOP = 210;
const WORD_SIZE = 42;

export const MINI = {
    markSize: MARK_SIZE,
    wordSize: WORD_SIZE,
    markCenter: { x: LEFT + (MARK_SIZE * LOGO_RATIO) / 2, y: TOP + MARK_SIZE / 2 },
    wordLeft: LEFT + MARK_SIZE * LOGO_RATIO + 18,
};

/** Visible entre `from` et `to` (images relatives à sa séquence). */
export const MiniLogo: React.FC<{ hideAt: number }> = ({ hideAt }) => {
    const frame = useCurrentFrame();
    const out = tw(frame, hideAt, 12, 0, 1, EASE.in);
    return (
        <div
            style={{
                position: 'absolute',
                left: LEFT,
                top: TOP,
                display: 'flex',
                alignItems: 'center',
                gap: 18,
                opacity: 1 - out,
                transform: `translateY(${-out * 30}px)`,
            }}
        >
            <LogoMark size={MARK_SIZE} />
            <div style={{ height: MARK_SIZE, display: 'flex', alignItems: 'center' }}>
                <Wordmark size={WORD_SIZE} />
            </div>
        </div>
    );
};
