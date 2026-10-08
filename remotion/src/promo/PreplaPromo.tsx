import React from 'react';
import { AbsoluteFill, Sequence } from 'remotion';
import './fonts';
import { Background } from './components/Background';
import { MiniLogo } from './components/MiniLogo';
import { S10Cta } from './scenes/S10Cta';
import { S1Hook } from './scenes/S1Hook';
import { S2Chaos } from './scenes/S2Chaos';
import { S3Logo } from './scenes/S3Logo';
import { S4Exam } from './scenes/S4Exam';
import { S5Diagnostic } from './scenes/S5Diagnostic';
import { S6Path } from './scenes/S6Path';
import { S7AI } from './scenes/S7AI';
import { S8Simulation } from './scenes/S8Simulation';
import { S9Progress } from './scenes/S9Progress';
import { Soundtrack } from './Soundtrack';
import { SCENES, TOTAL_FRAMES } from './theme';

// Les scènes démarrent sur une mesure (cf. theme.ts). Chaque séquence déborde de quelques
// images sur la suivante : la sortie de l'une et l'entrée de l'autre se chevauchent.
const OVERLAP = 8;

export const PreplaPromo: React.FC = () => {
    return (
        <AbsoluteFill style={{ backgroundColor: '#0b1322' }}>
            <Background />

            <Sequence from={SCENES.hook.from} durationInFrames={SCENES.hook.dur} name="1 · Accroche">
                <S1Hook />
            </Sequence>
            <Sequence from={SCENES.chaos.from} durationInFrames={SCENES.chaos.dur} name="2 · Au hasard">
                <S2Chaos />
            </Sequence>
            <Sequence from={SCENES.logo.from} durationInFrames={SCENES.logo.dur} name="3 · Logo">
                <S3Logo />
            </Sequence>
            <Sequence from={SCENES.exam.from} durationInFrames={SCENES.exam.dur + OVERLAP} name="4 · Examen">
                <S4Exam />
            </Sequence>
            <Sequence from={SCENES.diag.from} durationInFrames={SCENES.diag.dur + OVERLAP} name="5 · Diagnostic">
                <S5Diagnostic />
            </Sequence>
            <Sequence from={SCENES.path.from} durationInFrames={SCENES.path.dur + OVERLAP} name="6 · Parcours">
                <S6Path />
            </Sequence>
            <Sequence from={SCENES.ai.from} durationInFrames={SCENES.ai.dur + OVERLAP} name="7 · Correction IA">
                <S7AI />
            </Sequence>
            <Sequence from={SCENES.sim.from} durationInFrames={SCENES.sim.dur + OVERLAP} name="8 · Simulation">
                <S8Simulation />
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

            <Soundtrack />
        </AbsoluteFill>
    );
};
