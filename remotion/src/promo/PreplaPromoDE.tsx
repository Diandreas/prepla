import React from 'react';
import { AbsoluteFill, Sequence } from 'remotion';
import './fonts';
import { Background } from './components/Background';
import { MiniLogo } from './components/MiniLogo';
import { DE, PromoContentContext } from './content';
import { S10Cta } from './scenes/S10Cta';
import { S2Chaos } from './scenes/S2Chaos';
import { S3Logo } from './scenes/S3Logo';
import { S4Exam } from './scenes/S4Exam';
import { S5Diagnostic } from './scenes/S5Diagnostic';
import { S6Path } from './scenes/S6Path';
import { S9Progress } from './scenes/S9Progress';
import { DE_HOOK_SLOT_FRAMES, DeHook } from './scenes/de/DeHook';
import { DeAI } from './scenes/de/DeAI';
import { DeGoethe } from './scenes/de/DeGoethe';
import { FR_CUES, Soundtrack, key, range, type Cue } from './Soundtrack';
import { SCENES, TOTAL_FRAMES } from './theme';

// Version allemande (Goethe-Zertifikat, TestDaF) : même montage et même grille musicale que la
// version française, avec ses propres scènes d'accroche (der / die / das), de correction IA
// (place du verbe après « weil ») et de simulation (Modellprüfung Goethe B1), et les textes de
// content.ts (DE). Les explications restent en français : la vidéo vise les francophones qui
// préparent un examen d'allemand.

const OVERLAP = 8;
const { hook, exam, ai, sim, cta } = SCENES;
const between = (from: number, to: number) => FR_CUES.filter(([f]) => f >= from && f < to);

const DE_CUES: Cue[] = [
    // 1 · Accroche : l'article hésite entre der, die et das
    [hook.from + 6, 'click', 0.45],
    ...DE_HOOK_SLOT_FRAMES.map((f, i): Cue => [hook.from + f, key(i), 0.5]),
    [hook.from + 20, 'pop', 0.35],

    // 2–3 · Questions, logo : comme la version française
    ...between(SCENES.chaos.from, exam.from),

    // 4 · Allemand → Goethe-Zertifikat → B1
    ...[12, 16, 20].map((f): Cue => [exam.from + f, 'pop', 0.16]),
    [exam.from + 50, 'click', 0.75],
    ...[58, 62].map((f): Cue => [exam.from + f, 'pop', 0.13]),
    [exam.from + 78, 'click', 0.75],
    ...[84, 88, 92].map((f): Cue => [exam.from + f, 'pop', 0.08]),
    [exam.from + 102, 'click', 0.75],
    [exam.from + 111, 'swoosh', 0.25],
    [exam.from + 170, 'whoosh', 0.5],

    // 5–6 · Test de placement, parcours : comme la version française
    ...between(SCENES.diag.from, ai.from),

    // 7 · Correction IA : frappe, analyse, faute, le verbe part en fin de phrase, XP
    ...range(12, 48, 2).map((f, i): Cue => [ai.from + f, key(i), 0.32]),
    [ai.from + 50, 'swoosh', 0.3],
    [ai.from + 66, 'incorrect', 0.32],
    [ai.from + 74, 'swoosh', 0.22],
    [ai.from + 96, 'correct', 0.5],
    [ai.from + 100, 'swoosh', 0.16],
    [ai.from + 118, 'xp', 0.55],
    ...between(ai.from + 140, sim.from),

    // 8 · Modellprüfung : le téléphone monte, surlignage, réponse, le chrono jaillit
    [sim.from + 4, 'swoosh', 0.3],
    [sim.from + 14, 'swoosh', 0.12],
    [sim.from + 34, 'click', 0.75],
    [sim.from + 40, 'pop', 0.14],
    [sim.from + 52, 'pop', 0.45],
    [sim.from + 112, 'whoosh', 0.4],

    // 9–10 · Progrès, appel à l'action (+ la bulle du renard)
    ...between(SCENES.progress.from, TOTAL_FRAMES),
    [cta.from + 174, 'pop', 0.3],
];

export const PreplaPromoDE: React.FC = () => {
    return (
        <PromoContentContext.Provider value={DE}>
            <AbsoluteFill style={{ backgroundColor: '#0b1322' }}>
                <Background />

                <Sequence from={SCENES.hook.from} durationInFrames={SCENES.hook.dur} name="1 · Accroche der/die/das">
                    <DeHook />
                </Sequence>
                <Sequence from={SCENES.chaos.from} durationInFrames={SCENES.chaos.dur} name="2 · Au hasard">
                    <S2Chaos />
                </Sequence>
                <Sequence from={SCENES.logo.from} durationInFrames={SCENES.logo.dur} name="3 · Logo">
                    <S3Logo />
                </Sequence>
                <Sequence from={SCENES.exam.from} durationInFrames={SCENES.exam.dur + OVERLAP} name="4 · Goethe / TestDaF">
                    <S4Exam />
                </Sequence>
                <Sequence from={SCENES.diag.from} durationInFrames={SCENES.diag.dur + OVERLAP} name="5 · Diagnostic">
                    <S5Diagnostic />
                </Sequence>
                <Sequence from={SCENES.path.from} durationInFrames={SCENES.path.dur + OVERLAP} name="6 · Parcours">
                    <S6Path />
                </Sequence>
                <Sequence from={SCENES.ai.from} durationInFrames={SCENES.ai.dur + OVERLAP} name="7 · Correction IA (weil)">
                    <DeAI />
                </Sequence>
                <Sequence from={SCENES.sim.from} durationInFrames={SCENES.sim.dur + OVERLAP} name="8 · Modellprüfung">
                    <DeGoethe />
                </Sequence>
                <Sequence from={SCENES.progress.from} durationInFrames={SCENES.progress.dur + 2} name="9 · Progrès">
                    <S9Progress />
                </Sequence>
                <Sequence from={SCENES.cta.from} durationInFrames={TOTAL_FRAMES - SCENES.cta.from} name="10 · Appel à l'action">
                    <S10Cta />
                </Sequence>

                <Sequence from={SCENES.exam.from} durationInFrames={SCENES.cta.from - SCENES.exam.from} name="Logo discret">
                    <MiniLogo hideAt={SCENES.cta.from - SCENES.exam.from - 12} />
                </Sequence>

                <Soundtrack music="promo/music-de.mp3" cues={DE_CUES} />
            </AbsoluteFill>
        </PromoContentContext.Provider>
    );
};
