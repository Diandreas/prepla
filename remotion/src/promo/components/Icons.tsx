import React, { useId } from 'react';

// Pictogrammes au trait (tracés type Lucide, licence ISC) et drapeaux vectoriels.
const PATHS: Record<string, React.ReactNode> = {
    check: <path d="M20 6 9 17l-5-5" />,
    arrowRight: (
        <>
            <path d="M5 12h14" />
            <path d="m12 5 7 7-7 7" />
        </>
    ),
    arrowUpRight: (
        <>
            <path d="M7 17 17 7" />
            <path d="M7 7h10v10" />
        </>
    ),
    mic: (
        <>
            <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z" />
            <path d="M19 10v2a7 7 0 0 1-14 0v-2" />
            <path d="M12 19v3" />
        </>
    ),
    headphones: <path d="M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a9 9 0 0 1 18 0v7a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3" />,
    book: (
        <>
            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" />
            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" />
        </>
    ),
    pen: (
        <>
            <path d="M12 20h9" />
            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
        </>
    ),
    timer: (
        <>
            <path d="M10 2h4" />
            <path d="m12 14 3-3" />
            <circle cx="12" cy="14" r="8" />
        </>
    ),
    target: (
        <>
            <circle cx="12" cy="12" r="10" />
            <circle cx="12" cy="12" r="6" />
            <circle cx="12" cy="12" r="2" />
        </>
    ),
    trophy: (
        <>
            <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6" />
            <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18" />
            <path d="M4 22h16" />
            <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22" />
            <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22" />
            <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z" />
        </>
    ),
    flame: (
        <path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z" />
    ),
    sparkles: (
        <>
            <path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z" />
            <path d="M20 3v4" />
            <path d="M22 5h-4" />
            <path d="M4 17v2" />
            <path d="M5 18H3" />
        </>
    ),
    rotate: (
        <>
            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
            <path d="M3 3v5h5" />
        </>
    ),
    bulb: (
        <>
            <path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5" />
            <path d="M9 18h6" />
            <path d="M10 22h4" />
        </>
    ),
    trend: (
        <>
            <path d="M22 7 13.5 15.5 8.5 10.5 2 17" />
            <path d="M16 7h6v6" />
        </>
    ),
    calendar: (
        <>
            <rect width="18" height="18" x="3" y="4" rx="2" />
            <path d="M16 2v4" />
            <path d="M8 2v4" />
            <path d="M3 10h18" />
        </>
    ),
    message: <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />,
    globe: (
        <>
            <circle cx="12" cy="12" r="10" />
            <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20" />
            <path d="M2 12h20" />
        </>
    ),
    play: <path d="M6 3 20 12 6 21Z" />,
    star: <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" />,
    x: (
        <>
            <path d="M18 6 6 18" />
            <path d="m6 6 12 12" />
        </>
    ),
    question: (
        <>
            <circle cx="12" cy="12" r="10" />
            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
            <path d="M12 17h.01" />
        </>
    ),
};

export type IconName = keyof typeof PATHS;

export const Icon: React.FC<{ name: IconName; size: number; color?: string; stroke?: number; fill?: string; style?: React.CSSProperties }> = ({
    name,
    size,
    color = 'currentColor',
    stroke = 2,
    fill = 'none',
    style,
}) => (
    <svg
        width={size}
        height={size}
        viewBox="0 0 24 24"
        fill={fill}
        stroke={color}
        strokeWidth={stroke}
        strokeLinecap="round"
        strokeLinejoin="round"
        style={{ display: 'block', flexShrink: 0, ...style }}
    >
        {PATHS[name]}
    </svg>
);

export type FlagCode = 'gb' | 'fr' | 'de';

export const Flag: React.FC<{ code: FlagCode; width: number; radius?: number; style?: React.CSSProperties }> = ({ code, width, radius = 12, style }) => {
    const id = useId().replace(/:/g, '');
    const height = width * (2 / 3);
    return (
        <svg
            width={width}
            height={height}
            viewBox={code === 'gb' ? '0 0 60 40' : '0 0 3 2'}
            preserveAspectRatio="none"
            style={{ display: 'block', borderRadius: radius, boxShadow: '0 10px 24px -8px rgba(0,0,0,0.35)', ...style }}
        >
            {code === 'fr' ? (
                <>
                    <rect width="1" height="2" x="0" fill="#002395" />
                    <rect width="1" height="2" x="1" fill="#ffffff" />
                    <rect width="1" height="2" x="2" fill="#ED2939" />
                </>
            ) : null}
            {code === 'de' ? (
                <>
                    <rect width="3" height="0.667" y="0" fill="#000000" />
                    <rect width="3" height="0.667" y="0.666" fill="#DD0000" />
                    <rect width="3" height="0.668" y="1.332" fill="#FFCE00" />
                </>
            ) : null}
            {code === 'gb' ? (
                <>
                    <defs>
                        <clipPath id={`t-${id}`}>
                            <path d="M30,20 h30 v20 z v20 h-30 z h-30 v-20 z v-20 h30 z" />
                        </clipPath>
                    </defs>
                    <rect width="60" height="40" fill="#012169" />
                    <path d="M0,0 L60,40 M60,0 L0,40" stroke="#ffffff" strokeWidth="8" />
                    <path d="M0,0 L60,40 M60,0 L0,40" clipPath={`url(#t-${id})`} stroke="#C8102E" strokeWidth="5" />
                    <path d="M30,0 v40 M0,20 h60" stroke="#ffffff" strokeWidth="12" />
                    <path d="M30,0 v40 M0,20 h60" stroke="#C8102E" strokeWidth="7" />
                </>
            ) : null}
        </svg>
    );
};
