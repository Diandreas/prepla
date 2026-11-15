import { Link } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import Compteur from './compteur';

interface LiveProchain { id: number; title: string; classe: string | null; starts_at: string; duration_minutes: number; statut: 'a_venir' | 'en_cours' }

/**
 * Cote eleve : « Live en cours — Rejoindre », ou le prochain live de ses
 * classes avec compte a rebours et ajout au calendrier. Rien si aucun live.
 * Relu chaque minute (un live peut demarrer pendant que la page est ouverte).
 */
export default function BandeauLive({ className = '' }: { className?: string }) {
    const { t, i18n } = useTranslation();
    const [lives, setLives] = useState<LiveProchain[]>([]);

    useEffect(() => {
        let actif = true;
        const lire = () =>
            axios.get<{ lives: LiveProchain[] }>(route('live.prochains')).then(({ data }) => actif && setLives(data.lives)).catch(() => undefined);
        lire();
        const id = window.setInterval(lire, 60000);
        return () => {
            actif = false;
            window.clearInterval(id);
        };
    }, []);

    if (lives.length === 0) return null;
    const enCours = lives.filter((l) => l.statut === 'en_cours');
    const prochain = lives.find((l) => l.statut === 'a_venir');
    const date = (iso: string) => new Date(iso).toLocaleString(i18n.language, { weekday: 'long', day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit' });

    return (
        <div className={`space-y-2 ${className}`}>
            {enCours.map((l) => (
                <div key={l.id} className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-rose-300 bg-rose-50 p-4 dark:border-rose-900 dark:bg-rose-950/30">
                    <div className="min-w-0">
                        <p className="text-xs font-black uppercase tracking-widest text-rose-600">● {t('live.en_cours', 'Live en cours')}</p>
                        <p className="truncate font-bold">{l.title}</p>
                        {l.classe && <p className="text-xs text-muted-foreground">{l.classe}</p>}
                    </div>
                    <div className="flex gap-2">
                        <a href={route('live.rejoindre', l.id)} target="_blank" rel="noopener" className="rounded-lg bg-rose-600 px-4 py-2 text-sm font-bold text-white hover:bg-rose-700">
                            {t('live.rejoindre_court', 'Rejoindre')}
                        </a>
                        <Link href={route('live.show', l.id)} className="rounded-lg border border-rose-300 px-3 py-2 text-sm font-medium">
                            {t('live.salle', 'Questions et sondages')}
                        </Link>
                    </div>
                </div>
            ))}
            {prochain && (
                <div className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-border bg-background p-4">
                    <div className="min-w-0">
                        <p className="text-xs font-black uppercase tracking-widest text-primary">{t('live.prochain', 'Prochain live')}</p>
                        <Link href={route('live.show', prochain.id)} className="block truncate font-bold hover:underline">{prochain.title}</Link>
                        <p className="text-xs text-muted-foreground">{date(prochain.starts_at)} · <Compteur debut={prochain.starts_at} /></p>
                    </div>
                    <a href={route('live.ics', prochain.id)} className="rounded-lg border border-border px-3 py-2 text-sm font-medium hover:bg-muted">
                        📅 {t('live.calendrier', 'Ajouter au calendrier')}
                    </a>
                </div>
            )}
        </div>
    );
}
