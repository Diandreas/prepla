import React from 'react';
import { Composition, staticFile } from 'remotion';
import { getAudioDurationInSeconds } from '@remotion/media-utils';
import { QuizCard, type Episode } from './QuizCard';
import ieltsEpisode from './data/would-you-pass-ielts-001.json';

const FPS = 30;
const OUTRO_SECONDS = 3;
const REVEAL_HOLD_SECONDS = 2.5;
const FALLBACK_DURATION_SECONDS = 20;

// TODO: repointer vers 'audio/would-you-pass-ielts-001.mp3' une fois la voix off
// ElevenLabs générée (le compte a un paiement en attente — voir docs/content-studio.md).
const episodes: Array<{ id: string; episode: Episode; audioSrc: string }> = [
    {
        id: 'WouldYouPassIELTS001',
        episode: ieltsEpisode as Episode,
        audioSrc: 'audio/would-you-pass-ielts-001-placeholder.wav',
    },
];

export const RemotionRoot: React.FC = () => {
    return (
        <>
            {episodes.map(({ id, episode, audioSrc }) => (
                <Composition
                    key={id}
                    id={id}
                    component={QuizCard}
                    fps={FPS}
                    width={1080}
                    height={1920}
                    durationInFrames={FALLBACK_DURATION_SECONDS * FPS}
                    defaultProps={{
                        episode,
                        audioSrc,
                        revealHoldSeconds: REVEAL_HOLD_SECONDS,
                        outroSeconds: OUTRO_SECONDS,
                    }}
                    calculateMetadata={async ({ props }) => {
                        let audioDuration = FALLBACK_DURATION_SECONDS - OUTRO_SECONDS;
                        try {
                            audioDuration = await getAudioDurationInSeconds(staticFile(props.audioSrc));
                        } catch {
                            // Voix off pas encore générée (npm run voiceover) — on garde une durée de secours.
                        }
                        return {
                            durationInFrames: Math.max(1, Math.round((audioDuration + OUTRO_SECONDS) * FPS)),
                        };
                    }}
                />
            ))}
        </>
    );
};
