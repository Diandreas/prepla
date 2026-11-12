import { useCallback, useRef, useState } from 'react';
import { formatEnregistrement } from '@/lib/format-audio';

interface UseAudioRecorderReturn {
    isRecording: boolean;
    audioUrl: string | null;
    audioBlob: Blob | null;
    startRecording: () => Promise<void>;
    stopRecording: () => void;
    clearRecording: () => void;
    error: string | null;
}

export function useAudioRecorder(): UseAudioRecorderReturn {
    const [isRecording, setIsRecording] = useState(false);
    const [audioUrl, setAudioUrl] = useState<string | null>(null);
    const [audioBlob, setAudioBlob] = useState<Blob | null>(null);
    const [error, setError] = useState<string | null>(null);
    const mediaRecorderRef = useRef<MediaRecorder | null>(null);
    const chunksRef = useRef<Blob[]>([]);

    const startRecording = useCallback(async () => {
        try {
            setError(null);
            setAudioUrl(null);
            setAudioBlob(null);
            chunksRef.current = [];

            if (typeof MediaRecorder === 'undefined' || !navigator.mediaDevices?.getUserMedia) {
                setError("Ce navigateur ne sait pas enregistrer le micro. Mets-le à jour, ou essaie avec Chrome ou Safari.");
                return;
            }
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            // Le format que CE navigateur sait faire : webm sur Chrome et Android, mp4 sur
            // Safari et iPhone. Imposer le webm faisait echouer tout enregistrement sur iPhone.
            const format = formatEnregistrement();
            const mediaRecorder = format ? new MediaRecorder(stream, { mimeType: format }) : new MediaRecorder(stream);

            mediaRecorder.ondataavailable = (e) => {
                if (e.data.size > 0) chunksRef.current.push(e.data);
            };

            mediaRecorder.onstop = () => {
                // Le vrai type de l'enregistrement : le serveur et Deepgram en ont besoin.
                const blob = new Blob(chunksRef.current, { type: mediaRecorder.mimeType || format || 'audio/webm' });
                const url = URL.createObjectURL(blob);
                setAudioBlob(blob);
                setAudioUrl(url);
                stream.getTracks().forEach((t) => t.stop());
            };

            mediaRecorderRef.current = mediaRecorder;
            mediaRecorder.start(100);
            setIsRecording(true);
        } catch {
            setError('Impossible d\'accéder au microphone. Vérifiez les permissions.');
            setIsRecording(false);
        }
    }, []);

    const stopRecording = useCallback(() => {
        if (mediaRecorderRef.current && mediaRecorderRef.current.state !== 'inactive') {
            mediaRecorderRef.current.stop();
            setIsRecording(false);
        }
    }, []);

    const clearRecording = useCallback(() => {
        setAudioUrl(null);
        setAudioBlob(null);
        chunksRef.current = [];
    }, []);

    return { isRecording, audioUrl, audioBlob, startRecording, stopRecording, clearRecording, error };
}
