import React from 'react';
import { AbsoluteFill, Sequence } from 'remotion';
import './fonts';
import { Background } from './components/Background';
import { MiniLogo } from './components/MiniLogo';
import { S10Cta } from './scenes/S10Cta';
import { QUIZ_EXIT, QuizChallenge } from './scenes/short/QuizChallenge';
import { Soundtrack, key, range, type Cue } from './Soundtrack';
import { QUIZ_TOTAL } from './theme';

// Format court (15 s) pour TikTok / Reels / Shorts : un défi de grammaire « Tu as 3 secondes »,
// le verdict et la correction tels que l'app les affiche, puis le diagnostic gratuit.
// Même grille musicale que les vidéos de 60 s (120 BPM : 1 temps = 15 images).

// La fin est montée sous le quiz : le logo s'assemble pendant que le quiz s'éloigne en zoom
// et il est formé sur l'impact musical de la mesure 6 (image 300).
const CTA_FROM = 270;
const CARD_AT = 300 - CTA_FROM;
const LOGO_LEAD = 20;

const QUIZ_CUES: Cue[] = [
    // Le défi : un « pop » par chiffre du compte à rebours
    [0, 'pop', 0.35],
    [30, 'pop', 0.4],
    [60, 'pop', 0.45],
    // Le doigt choisit « était »
    [90, 'click', 1.6],
    [92, 'swoosh', 0.15],
    // Verdict, puis « soit » se pose dans le blanc
    [120, 'incorrect', 0.6],
    [120, 'pop', 0.4],
    [137, 'pop', 0.3],
    // Correction, règle, « Ton erreur reviendra en révision »
    [150, 'swoosh', 0.3],
    [165, 'pop', 0.25],
    [210, 'pop', 0.35],
    // « Et ton vrai niveau ? », puis zoom vers la fin
    [228, 'swoosh', 0.2],
    [QUIZ_EXIT, 'whoosh', 0.45],
    // Logo (le cube se pose), carte « Ta prochaine mission », renard, adresse, tap
    [300 - LOGO_LEAD + 20, 'pop', 0.5],
    [300 + 26, 'swoosh', 0.3],
    [300 + 40, 'pop', 0.35],
    ...range(356, 374, 2).map((f, i): Cue => [f, key(i), 0.6]),
    [375, 'click', 1.8],
    [377, 'xp', 0.38],
];

export const PreplaQuiz15: React.FC = () => {
    return (
        <AbsoluteFill style={{ backgroundColor: '#0b1322' }}>
            {/* Teinte d'urgence dès la première image, apaisée après le verdict */}
            <Background stressKeys={{ frames: [0, 120, 165], values: [1, 1, 0] }} gridRush={null} />

            <Sequence from={CTA_FROM} durationInFrames={QUIZ_TOTAL - CTA_FROM} name="2 · Diagnostic gratuit">
                <S10Cta cardAt={CARD_AT} logoLead={LOGO_LEAD} intro={false} layout="compact" tapOffset={75} showDescription={false} />
            </Sequence>
            <Sequence from={0} durationInFrames={QUIZ_EXIT + 15} name="1 · Quiz « Tu as 3 secondes »">
                <QuizChallenge />
            </Sequence>
            <Sequence from={0} durationInFrames={QUIZ_EXIT} name="Logo discret">
                <MiniLogo top={262} hideAt={QUIZ_EXIT - 6} />
            </Sequence>

            <Soundtrack music="promo/music-15s.mp3" cues={QUIZ_CUES} total={QUIZ_TOTAL} />
        </AbsoluteFill>
    );
};
