import axios from 'axios';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';

/*
 * La salle video Daily.co, affichee DANS la page PrePla (daily-js, createFrame).
 * Le jeton personnel est demande a PrePla (jamais la cle API) ; les arrivees et
 * departs sont renvoyes a PrePla pour la presence reelle.
 */

type Libelle = (cle: string, fr: string) => string;

export default function SalleVideo({ liveId, L }: { liveId: number; L: Libelle }) {
    const hote = useRef<HTMLDivElement>(null);
    const [etat, setEtat] = useState<'attente' | 'chargement' | 'dedans' | 'erreur'>('attente');
    const [actif, setActif] = useState(false);

    useEffect(() => {
        if (!actif || !hote.current) return;
        let fini = false;
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        let cadre: any = null;
        const signaler = (evenement: 'joined' | 'left') => axios.post(route('live.video.presence', liveId), { evenement }).catch(() => undefined);

        (async () => {
            try {
                const [{ data }, { default: Daily }] = await Promise.all([
                    axios.post<{ url: string; token: string }>(route('live.video.jeton', liveId)),
                    import('@daily-co/daily-js'),
                ]);
                if (fini || !hote.current) return;
                cadre = Daily.createFrame(hote.current, {
                    iframeStyle: { width: '100%', height: '100%', border: '0', borderRadius: '12px' },
                    showLeaveButton: true,
                    showFullscreenButton: true,
                });
                cadre.on('joined-meeting', () => { setEtat('dedans'); signaler('joined'); });
                cadre.on('left-meeting', () => { signaler('left'); setEtat('attente'); setActif(false); });
                cadre.on('error', () => { setEtat('erreur'); setActif(false); });
                await cadre.join({ url: data.url, token: data.token });
            } catch {
                if (!fini) { setEtat('erreur'); setActif(false); }
            }
        })();

        return () => {
            fini = true;
            if (cadre) {
                if (cadre.meetingState() === 'joined-meeting') signaler('left');
                cadre.destroy();
            }
        };
    }, [actif, liveId]);

    return (
        <div className="space-y-2">
            <div className="relative aspect-video w-full overflow-hidden rounded-xl bg-neutral-900 sm:min-h-[360px]">
                <div ref={hote} className="absolute inset-0" />
                {etat !== 'dedans' && etat !== 'chargement' && (
                    <div className="absolute inset-0 flex flex-col items-center justify-center gap-3 p-4 text-center text-sm text-white">
                        {etat === 'erreur' && <p>{L('video_erreur', 'La vidéo n’a pas pu s’ouvrir ici.')}</p>}
                        <Button className="bg-rose-600 text-white hover:bg-rose-700" onClick={() => { setEtat('chargement'); setActif(true); }}>
                            ● {L('video_entrer', 'Entrer dans la salle vidéo')}
                        </Button>
                    </div>
                )}
                {etat === 'chargement' && <p className="absolute left-3 top-3 text-xs text-white/80">{L('video_chargement', 'Connexion à la salle…')}</p>}
            </div>
            <p className="text-right text-xs">
                <a className="text-primary hover:underline" href={route('live.rejoindre', liveId)} target="_blank" rel="noopener">
                    {L('video_onglet', 'Ouvrir la vidéo dans un onglet')}
                </a>
            </p>
        </div>
    );
}
