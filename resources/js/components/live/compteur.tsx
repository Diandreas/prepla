import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

/** Compte a rebours avant le debut d'un live (« Commence dans 2 h 05 min »). */
export default function Compteur({ debut }: { debut: string }) {
    const { t } = useTranslation();
    const [maintenant, setMaintenant] = useState(() => Date.now());
    useEffect(() => {
        const id = window.setInterval(() => setMaintenant(Date.now()), 1000);
        return () => window.clearInterval(id);
    }, []);

    const reste = Math.max(0, Math.floor((new Date(debut).getTime() - maintenant) / 1000));
    if (reste === 0) return <span className="text-sm font-semibold text-rose-600">{t('live.commence', 'Le live commence.')}</span>;

    const j = Math.floor(reste / 86400);
    const h = Math.floor((reste % 86400) / 3600);
    const m = Math.floor((reste % 3600) / 60);
    const s = reste % 60;
    const texte = j > 0 ? `${j} j ${h} h` : h > 0 ? `${h} h ${String(m).padStart(2, '0')} min` : `${m} min ${String(s).padStart(2, '0')} s`;

    return (
        <span className="text-sm text-muted-foreground" aria-live="off">
            {t('live.commence_dans', { defaultValue: 'Commence dans {{temps}}', temps: texte })}
        </span>
    );
}
