import { Gif } from '@remotion/gif';
import React from 'react';
import { Img, staticFile } from 'remotion';

// Éléments réels de l'application PrePla, copiés depuis public/ (icônes illustrées,
// renard-guide, animations) et release/google-play/screenshots (écrans réels).

export type AppIconName =
    | 'courses'
    | 'listening'
    | 'writing'
    | 'speaking'
    | 'message-square'
    | 'trophy'
    | 'sparkles'
    | 'target'
    | 'calendar-days'
    | 'lightbulb'
    | 'zap'
    | 'statistics'
    | 'review'
    | 'clock'
    | 'vocabulary';

export type AppTone = 'blue' | 'mint' | 'amber' | 'rose' | 'neutral';

const TINTS: Record<AppTone, string> = {
    blue: '#e7f2ff',
    mint: '#daf4e6',
    amber: '#fff0cd',
    rose: '#fce3e9',
    neutral: '#e9edf3',
};

/**
 * Icône de l'app sur sa tuile claire — reprise de `ArtIcon` (resources/js/components/art-icon.tsx,
 * classe `.studio-icon`) : « l'illustration repose toujours sur une surface claire, même en sombre ».
 */
export const AppIcon: React.FC<{ name: AppIconName; size: number; tone?: AppTone; style?: React.CSSProperties; shadow?: boolean }> = ({
    name,
    size,
    tone = 'blue',
    style,
    shadow = true,
}) => (
    <div
        style={{
            width: size,
            height: size,
            flexShrink: 0,
            display: 'grid',
            placeItems: 'center',
            borderRadius: '30%',
            border: `${Math.max(1, size / 48)}px solid #fff`,
            background: `linear-gradient(145deg, #fff 20%, ${TINTS[tone]})`,
            boxShadow: `inset 0 ${-size / 16}px 0 rgba(22,51,83,0.05)${shadow ? `, 0 ${size / 8}px ${size / 3.2}px rgba(3,9,20,0.35)` : ''}`,
            boxSizing: 'border-box',
            ...style,
        }}
    >
        <Img src={staticFile(`promo/app/icons/${name}.png`)} style={{ width: size * 0.66, height: size * 0.66, objectFit: 'contain' }} />
    </div>
);

/** Le renard-guide de l'app (public/illustrations/prepla-guide/welcome.png). */
export const Fox: React.FC<{ height: number; style?: React.CSSProperties }> = ({ height, style }) => (
    <Img src={staticFile('promo/app/fox.png')} style={{ height, width: height * (750 / 900), objectFit: 'contain', ...style }} />
);

/** Animation GIF de l'app, lue en phase avec l'image (Remotion). */
export const AppGif: React.FC<{ name: 'Fire' | 'star' | 'Trophy' | 'loading'; width: number; height: number; style?: React.CSSProperties }> = ({
    name,
    width,
    height,
    style,
}) => <Gif src={staticFile(`promo/app/${name}.gif`)} width={width} height={height} fit="contain" style={style} />;

/** Téléphone sobre (cadre + écran 9:16) pour montrer les vrais écrans de l'app. */
export const Phone: React.FC<{ width: number; children: React.ReactNode; style?: React.CSSProperties }> = ({ width, children, style }) => {
    const pad = width * 0.032;
    const screenW = width - pad * 2;
    const screenH = (screenW * 16) / 9;
    return (
        <div
            style={{
                width,
                height: screenH + pad * 2 + width * 0.05,
                padding: `${pad + width * 0.05}px ${pad}px ${pad}px`,
                boxSizing: 'border-box',
                borderRadius: width * 0.13,
                background: 'linear-gradient(160deg, #2a3a57 0%, #121c30 45%, #0a111f 100%)',
                boxShadow: '0 80px 140px -40px rgba(0,0,0,0.85), inset 0 2px 0 rgba(255,255,255,0.18), 0 0 0 2px rgba(255,255,255,0.08)',
                position: 'relative',
                ...style,
            }}
        >
            <div
                style={{
                    position: 'absolute',
                    top: pad + width * 0.016,
                    left: '50%',
                    width: width * 0.026,
                    height: width * 0.026,
                    marginLeft: -width * 0.013,
                    borderRadius: '50%',
                    background: '#05080f',
                    boxShadow: 'inset 0 0 0 2px rgba(255,255,255,0.08)',
                }}
            />
            <div style={{ width: screenW, height: screenH, borderRadius: width * 0.1, overflow: 'hidden', position: 'relative', background: '#f3f5f5' }}>{children}</div>
        </div>
    );
};

export const AppScreen: React.FC<{ name: '03-simulation' | '04-exercice'; style?: React.CSSProperties }> = ({ name, style }) => (
    <Img src={staticFile(`promo/app/screens/${name}.png`)} style={{ position: 'absolute', inset: 0, width: '100%', height: '100%', objectFit: 'cover', ...style }} />
);
