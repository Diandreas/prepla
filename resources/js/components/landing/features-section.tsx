import { useTranslation } from 'react-i18next';

/**
 * Les quatre tuiles que montre la video entre 16 et 20 s — redaction, ecoute,
 * oral, a la maison —, avec ce que l'application fait vraiment de chacune.
 *
 * Remplace une grille de quatre cartes disposees en 3 + 1, qui promettait des
 * exercices « illimites » et une analyse « en quelques secondes » de tout.
 */
const EPREUVES = [
    { cle: 'redaction', icone: '/icons/writing.png' },
    { cle: 'ecoute', icone: '/icons/listening.png' },
    { cle: 'oral', icone: '/icons/speaking.png' },
    { cle: 'maison', icone: '/icons/home.png' },
] as const;

export function FeaturesSection() {
    const { t } = useTranslation();

    return (
        <section id="epreuves" className="accueil-section" aria-labelledby="epreuves-titre" style={{ background: 'var(--fond-alt)' }}>
            <div className="accueil-section-interieur">
                <h2 id="epreuves-titre">
                    {t('landing.epreuves_titre')} <em>{t('landing.epreuves_titre_accent')}</em>
                </h2>
                <p className="accueil-section-chapo">{t('landing.epreuves_chapo')}</p>

                <ul className="accueil-grille quatre">
                    {EPREUVES.map((epreuve) => (
                        <li key={epreuve.cle} className="accueil-carte p-5">
                            <img src={epreuve.icone} alt="" width={44} height={44} loading="lazy" decoding="async" className="h-11 w-11 object-contain" />
                            <h3 className="mt-4 text-base font-bold" style={{ color: 'var(--texte)' }}>
                                {t(`landing.epreuve_${epreuve.cle}_titre`)}
                            </h3>
                            <p className="mt-2 text-sm leading-relaxed" style={{ color: 'var(--texte-moyen)' }}>
                                {t(`landing.epreuve_${epreuve.cle}_texte`)}
                            </p>
                        </li>
                    ))}
                </ul>

                <p className="mx-auto mt-6 max-w-3xl text-center text-xs leading-relaxed" style={{ color: 'var(--texte-doux)' }}>
                    {t('landing.epreuves_note_ia')}
                </p>
            </div>
        </section>
    );
}
