import { router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { langueInterface } from '@/lib/i18n/traduire';

/**
 * FR / EN dans l'en-tete : l'interface change tout de suite, et le choix est
 * garde sur le compte (il revient a la prochaine connexion, sur tout appareil).
 */
export default function ChoixLangue() {
    const { i18n } = useTranslation();
    const actuelle = langueInterface(i18n.language);

    const choisir = (langue: 'fr' | 'en') => {
        if (langue === actuelle) return;
        void i18n.changeLanguage(langue);
        router.patch(route('langue.interface'), { langue }, { preserveScroll: true, preserveState: true });
    };

    return (
        <div className="flex items-center rounded-full border border-border/60 bg-card/60 p-0.5 text-xs font-bold" role="group" aria-label="Langue / Language">
            {(['fr', 'en'] as const).map((l) => (
                <button
                    key={l}
                    type="button"
                    onClick={() => choisir(l)}
                    aria-pressed={actuelle === l}
                    className={`rounded-full px-2.5 py-1 uppercase transition ${actuelle === l ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground'}`}
                >
                    {l}
                </button>
            ))}
        </div>
    );
}
