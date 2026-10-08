import React from 'react';
import { Audio, Sequence, staticFile } from 'remotion';
import { SCENES, TOTAL_FRAMES } from './theme';

// Bande-son : la musique (scripts/compose-promo-music.py) + les bruitages, placés à l'image près
// sur les animations. Les sons d'interface (click, pop, correct…) sont ceux de l'application.

type Sfx = 'click' | 'pop' | 'correct' | 'incorrect' | 'xp' | 'complete' | 'whoosh' | 'swoosh' | 'key-0' | 'key-1' | 'key-2';

const FILES: Record<Sfx, string> = {
    click: 'promo/sfx/click.mp3',
    pop: 'promo/sfx/pop.mp3',
    correct: 'promo/sfx/correct.mp3',
    incorrect: 'promo/sfx/incorrect.mp3',
    xp: 'promo/sfx/xp.mp3',
    complete: 'promo/sfx/complete.mp3',
    whoosh: 'promo/sfx/whoosh.wav',
    swoosh: 'promo/sfx/swoosh.wav',
    'key-0': 'promo/sfx/key-0.wav',
    'key-1': 'promo/sfx/key-1.wav',
    'key-2': 'promo/sfx/key-2.wav',
};

const LENGTH: Record<Sfx, number> = {
    click: 16,
    pop: 16,
    correct: 32,
    incorrect: 26,
    xp: 28,
    complete: 62,
    whoosh: 19,
    swoosh: 10,
    'key-0': 3,
    'key-1': 3,
    'key-2': 3,
};

type Cue = [frame: number, sfx: Sfx, volume: number];

const key = (i: number): Sfx => (['key-0', 'key-1', 'key-2'] as const)[i % 3];
const range = (from: number, to: number, step: number) => {
    const out: number[] = [];
    for (let f = from; f <= to; f += step) out.push(Math.round(f));
    return out;
};

const { hook, chaos, logo, exam, diag, path, ai, sim, progress, cta } = SCENES;

const CUES: Cue[] = [
    // 1 · Accroche : la machine à sous des examens
    [hook.from + 6, 'click', 0.45],
    ...[8, 13, 18, 23, 28, 33, 39, 46, 54, 64].map((f, i): Cue => [hook.from + f, key(i), 0.5]),
    [hook.from + 76, 'pop', 0.42],

    // 2 · Les questions qui fusent
    ...[0, 2, 4, 6, 8, 10].map((i): Cue => [chaos.from + Math.round(4 + i * 3.5), 'pop', 0.11]),

    // 3 · Logo
    [logo.from + 20, 'pop', 0.55],
    [logo.from + 34, 'click', 0.5],
    [logo.from + 44, 'swoosh', 0.22],
    [logo.from + 103, 'swoosh', 0.35],

    // 4 · Choix de l'examen
    ...[12, 16, 20].map((f): Cue => [exam.from + f, 'pop', 0.16]),
    [exam.from + 50, 'click', 0.75],
    ...[58, 62, 66].map((f): Cue => [exam.from + f, 'pop', 0.13]),
    [exam.from + 86, 'click', 0.75],
    [exam.from + 99, 'swoosh', 0.25],
    [exam.from + 170, 'whoosh', 0.5],

    // 5 · Test de placement : section A, section B, rédaction, analyse, résultat
    [diag.from + 40, 'click', 0.75],
    [diag.from + 46, 'correct', 0.5],
    [diag.from + 61, 'swoosh', 0.38],
    [diag.from + 94, 'click', 0.75],
    [diag.from + 100, 'correct', 0.45],
    [diag.from + 105, 'swoosh', 0.3],
    ...range(112, 126, 2).map((f, i): Cue => [diag.from + f, key(i), 0.3]),
    [diag.from + 126, 'swoosh', 0.15],
    [diag.from + 162, 'complete', 0.3],
    [diag.from + 196, 'pop', 0.18],
    [diag.from + 202, 'pop', 0.18],
    [diag.from + 230, 'whoosh', 0.42],

    // 6 · Parcours : un « pop » par étape qui apparaît, une étincelle pour la leçon ajoutée
    ...[0, 1, 2, 3, 4, 5, 6].map((n): Cue => [path.from + Math.round(8 + (n / 6) * 70 * 0.92), 'pop', 0.13]),
    [path.from + 92, 'swoosh', 0.22],
    [path.from + 100, 'pop', 0.2],
    [path.from + 130, 'xp', 0.45],
    [path.from + 140, 'swoosh', 0.18],
    [path.from + 230, 'whoosh', 0.42],

    // 7 · Correction IA : frappe, analyse, faute, correction, XP, puis les formats
    ...range(14, 52, 2).map((f, i): Cue => [ai.from + f, key(i), 0.32]),
    [ai.from + 58, 'swoosh', 0.3],
    [ai.from + 76, 'incorrect', 0.32],
    [ai.from + 88, 'correct', 0.5],
    [ai.from + 100, 'swoosh', 0.18],
    [ai.from + 118, 'xp', 0.55],
    [ai.from + 140, 'swoosh', 0.3],
    ...range(158, 180, 2).map((f, i): Cue => [ai.from + f, key(i), 0.22]),
    [ai.from + 182, 'pop', 0.35],
    ...[178, 182, 186, 190].map((f): Cue => [ai.from + f, 'pop', 0.09]),
    [ai.from + 232, 'whoosh', 0.4],

    // 8 · Simulation : le téléphone monte, tap sur « Commencer l'examen », le chrono jaillit
    [sim.from + 4, 'swoosh', 0.3],
    [sim.from + 34, 'click', 0.75],
    [sim.from + 40, 'swoosh', 0.2],
    [sim.from + 52, 'pop', 0.45],
    [sim.from + 112, 'whoosh', 0.4],

    // 9 · Progrès
    ...[8, 14, 20, 26].map((f): Cue => [progress.from + f, 'pop', 0.14]),
    [progress.from + 56, 'xp', 0.3],
    [progress.from + 158, 'swoosh', 0.4],

    // 10 · Appel à l'action : logo, carte « Ta prochaine mission », renard, adresse, tap
    [cta.from + 140, 'pop', 0.5],
    [cta.from + 144, 'click', 0.4],
    [cta.from + 146, 'swoosh', 0.3],
    [cta.from + 160, 'pop', 0.35],
    ...range(176, 194, 2).map((f, i): Cue => [cta.from + f, key(i), 0.24]),
    [cta.from + 190, 'click', 0.8],
    [cta.from + 192, 'xp', 0.38],
];

export const Soundtrack: React.FC<{ withMusic?: boolean }> = ({ withMusic = true }) => {
    return (
        <>
            {withMusic ? <Audio src={staticFile('promo/music.mp3')} volume={0.9} /> : null}
            {CUES.map(([frame, sfx, volume], i) => (
                <Sequence key={i} from={frame} durationInFrames={Math.min(LENGTH[sfx], TOTAL_FRAMES - frame)} layout="none" name={`sfx ${sfx}`}>
                    <Audio src={staticFile(FILES[sfx])} volume={volume} />
                </Sequence>
            ))}
        </>
    );
};
