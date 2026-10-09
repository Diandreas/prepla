import { Easing, interpolate, spring, type SpringConfig } from 'remotion';

// Vidéo de présentation PrePla — 1080×1920 (9:16), 30 i/s, calée sur une musique à 120 BPM :
// 1 temps = 15 images, 1 mesure = 60 images. Chaque scène démarre sur un temps fort.
export const W = 1080;
export const H = 1920;
export const FPS = 30;
export const BEAT = 15;
export const BAR = 60;

export const SCENES = {
    hook: { from: 0, dur: 120 },
    chaos: { from: 120, dur: 120 },
    logo: { from: 240, dur: 120 },
    exam: { from: 360, dur: 180 },
    diag: { from: 540, dur: 240 },
    path: { from: 780, dur: 240 },
    ai: { from: 1020, dur: 240 },
    sim: { from: 1260, dur: 120 },
    progress: { from: 1380, dur: 180 },
    cta: { from: 1560, dur: 240 },
} as const;

export const TOTAL_FRAMES = 1800;

// Format court (quiz de 15 s, PreplaQuiz15.tsx), même grille musicale.
export const QUIZ_TOTAL = 450;

// Couleurs de la landing PrePla (resources/js/components/landing/landing-theme.tsx).
export const C = {
    bg: '#0b1322',
    bgDeep: '#070d18',
    bgLift: '#13223d',
    ink: '#13233f',
    inkSoft: 'rgba(19,35,63,0.62)',
    inkDim: 'rgba(19,35,63,0.38)',
    text: '#eef3fb',
    textMid: 'rgba(238,243,251,0.70)',
    textDim: 'rgba(238,243,251,0.40)',
    line: 'rgba(255,255,255,0.10)',
    sky: '#3B82E0',
    skyLight: '#6aaaf6',
    skyPale: '#e8f1fd',
    gold: '#F5A623',
    goldLight: '#ffc861',
    goldPale: '#fff4e0',
    green: '#22c55e',
    greenPale: '#e5f8ec',
    red: '#ef4444',
    redPale: '#fdecec',
    paper: '#ffffff',
    paperSoft: '#f5f8fc',
    paperLine: '#e3e9f2',
} as const;

export const FONT = {
    sans: '"Plus Jakarta Sans", system-ui, sans-serif',
    serif: '"Cormorant Garamond", Georgia, serif',
} as const;

export const EASE = {
    out: Easing.bezier(0.16, 1, 0.3, 1),
    outSoft: Easing.bezier(0.33, 1, 0.68, 1),
    in: Easing.bezier(0.7, 0, 0.84, 0),
    inOut: Easing.bezier(0.83, 0, 0.17, 1),
    inOutSoft: Easing.bezier(0.65, 0, 0.35, 1),
    outBack: Easing.bezier(0.34, 1.56, 0.64, 1),
} as const;

const CLAMP = { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' } as const;

/** Interpolation 0→1 (ou from→to) entre start et start+dur, bornée. */
export function tw(frame: number, start: number, dur: number, from = 0, to = 1, easing: (t: number) => number = EASE.out) {
    return interpolate(frame, [start, start + Math.max(1, dur)], [from, to], { ...CLAMP, easing });
}

/** Enveloppe entrée/sortie : monte à `a` sur `ia` images, redescend à `b` sur `ob` images. */
export function env(frame: number, a: number, ia: number, b: number, ob: number) {
    return Math.min(tw(frame, a, ia, 0, 1, EASE.out), tw(frame, b, ob, 1, 0, EASE.in));
}

export const SPRING = {
    pop: { damping: 13, stiffness: 190, mass: 0.8 } satisfies Partial<SpringConfig>,
    soft: { damping: 20, stiffness: 120, mass: 1 } satisfies Partial<SpringConfig>,
    bouncy: { damping: 9, stiffness: 160, mass: 0.9 } satisfies Partial<SpringConfig>,
    firm: { damping: 200, stiffness: 200, mass: 1 } satisfies Partial<SpringConfig>,
};

export function sp(frame: number, delay = 0, config: Partial<SpringConfig> = SPRING.pop) {
    return spring({ frame: frame - delay, fps: FPS, config });
}

export function mix(a: number, b: number, t: number) {
    return a + (b - a) * t;
}
