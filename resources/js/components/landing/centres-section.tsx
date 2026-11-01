import { useTranslation } from 'react-i18next';
import { SUPPORT_WHATSAPP_URL } from '@/lib/contact';

/**
 * L'offre aux centres de langues, que la page ne mentionnait nulle part. Cinq
 * points, chacun verifie dans l'application ; ni prix ni chiffre.
 *
 * L'appel passe par WhatsApp : un compte cree sur /register mene a l'accueil
 * des apprenants, pas a un espace enseignant.
 */
const POINTS = ['classes', 'devoirs', 'suivi', 'exercices', 'equipe'] as const;

export function CentresSection() {
    const { t } = useTranslation();

    return (
        <section id="centres" className="accueil-section" aria-labelledby="centres-titre" style={{ background: 'var(--fond-alt)' }}>
            <div className="accueil-section-interieur grid items-center gap-10 lg:grid-cols-2">
                <div>
                    <p className="text-xs font-bold uppercase tracking-widest" style={{ color: 'var(--accent)' }}>{t('landing.centres_surtitre')}</p>
                    <h2 id="centres-titre" className="mt-2" style={{ textAlign: 'left' }}>
                        {t('landing.centres_titre')} <em>{t('landing.centres_titre_accent')}</em>
                    </h2>
                    <p className="mt-4 leading-relaxed" style={{ color: 'var(--texte-moyen)' }}>{t('landing.centres_chapo')}</p>
                    <a href={SUPPORT_WHATSAPP_URL} target="_blank" rel="noopener noreferrer" className="bouton-compte mt-6">
                        {t('landing.centres_cta')}
                    </a>
                </div>

                <ul className="space-y-3">
                    {POINTS.map((point) => (
                        <li key={point} className="accueil-carte flex gap-3 p-4">
                            <span aria-hidden="true" style={{ color: 'var(--officiel)', fontWeight: 900 }}>✓</span>
                            <span>
                                <span className="block font-semibold" style={{ color: 'var(--texte)' }}>{t(`landing.centres_${point}_titre`)}</span>
                                <span className="mt-0.5 block text-sm leading-relaxed" style={{ color: 'var(--texte-moyen)' }}>{t(`landing.centres_${point}_texte`)}</span>
                            </span>
                        </li>
                    ))}
                </ul>
            </div>
        </section>
    );
}
