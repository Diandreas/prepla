import { Deferred, Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import type { Tarif } from '@/data/languages';
import type { SharedData } from '@/types';

/**
 * Les tarifs, tels que Stripe les debite (montant et monnaie), et la seule
 * difference reelle entre les deux formules : la limite de 3 exercices par jour.
 *
 * La page annoncait « 9.99€ » alors que l'abonnement est facture en dollars, et
 * presentait comme payants des outils ouverts a tous (correction de l'ecrit et
 * de l'oral, explications, generateur d'exercices).
 */
function prix(tarif: Tarif, langue: string, montant = tarif.amount): string {
    return new Intl.NumberFormat(langue, {
        style: 'currency',
        currency: tarif.currency.toUpperCase(),
        currencyDisplay: 'code',
        // « 0 USD », pas « 0,00 USD ».
        ...(montant === 0 ? { minimumFractionDigits: 0, maximumFractionDigits: 0 } : {}),
    }).format(montant);
}

function Puce({ children }: { children: React.ReactNode }) {
    return (
        <li className="flex gap-2.5 text-sm leading-relaxed" style={{ color: 'var(--texte-moyen)' }}>
            <span aria-hidden="true" style={{ color: 'var(--officiel)', fontWeight: 900 }}>✓</span>
            <span>{children}</span>
        </li>
    );
}

function Tarifs() {
    const { t, i18n } = useTranslation();
    const { tarifs, auth } = usePage<SharedData & { tarifs: { mensuel: Tarif; annuel: Tarif } }>().props;
    const langue = (i18n.resolvedLanguage ?? i18n.language ?? 'en').slice(0, 2);
    const versLAbonnement = auth?.user ? '/settings/subscription' : '/register';

    return (
        <div className="accueil-grille deux mx-auto max-w-4xl">
            <div className="accueil-carte flex flex-col p-6">
                <h3 className="text-lg font-bold" style={{ color: 'var(--texte)' }}>{t('landing.tarif_gratuit')}</h3>
                <p className="mt-3 text-4xl font-extrabold" style={{ color: 'var(--texte)' }}>{prix(tarifs.mensuel, langue, 0)}</p>
                <ul className="mt-5 space-y-2.5">
                    <Puce>{t('landing.tarif_gratuit_limite')}</Puce>
                    <Puce>{t('landing.tarif_tout_inclus')}</Puce>
                </ul>
                <div className="mt-auto pt-6">
                    <Link href="/register" className="bouton-compte w-full">{t('landing.cta_compte')}</Link>
                    <p className="mt-3 text-center text-sm">
                        <Link href="/essayer" className="accueil-lien">{t('landing.tarif_pas_sur')} →</Link>
                    </p>
                </div>
            </div>

            <div className="accueil-carte flex flex-col p-6" style={{ borderColor: 'color-mix(in srgb, var(--ciel) 55%, transparent)' }}>
                <h3 className="text-lg font-bold" style={{ color: 'var(--texte)' }}>PrePla Plus</h3>
                <p className="mt-3 text-4xl font-extrabold" style={{ color: 'var(--texte)' }}>
                    {prix(tarifs.mensuel, langue)}
                    <span className="text-base font-semibold" style={{ color: 'var(--texte-moyen)' }}> / {t('landing.tarif_mois')}</span>
                </p>
                <p className="mt-1 text-sm" style={{ color: 'var(--texte-moyen)' }}>
                    {t('landing.tarif_ou_an', {
                        annuel: prix(tarifs.annuel, langue),
                        parMois: prix(tarifs.annuel, langue, Math.round((tarifs.annuel.amount / 12) * 100) / 100),
                    })}
                </p>
                <ul className="mt-5 space-y-2.5">
                    <Puce>{t('landing.tarif_plus_illimite')}</Puce>
                    <Puce>{t('landing.tarif_plus_memes')}</Puce>
                </ul>
                <div className="mt-auto pt-6">
                    <Link href={versLAbonnement} className="bouton-compte w-full" style={{ background: 'color-mix(in srgb, var(--ciel) 18%, transparent)' }}>
                        {t('landing.tarif_plus_cta')}
                    </Link>
                </div>
            </div>
        </div>
    );
}

export function PricingSection() {
    const { t } = useTranslation();

    return (
        <section id="tarifs" className="accueil-section" aria-labelledby="tarifs-titre">
            <div className="accueil-section-interieur">
                <h2 id="tarifs-titre">
                    {t('landing.tarifs_titre')} <em>{t('landing.tarifs_titre_accent')}</em>
                </h2>
                <p className="accueil-section-chapo">{t('landing.tarifs_chapo')}</p>

                {/* Lus chez Stripe, en differe : la page ne les attend pas. */}
                <Deferred data="tarifs" fallback={<div className="accueil-grille deux mx-auto max-w-4xl" aria-busy="true" style={{ minHeight: '22rem' }} />}>
                    <Tarifs />
                </Deferred>

                <p className="mx-auto mt-6 max-w-3xl text-center text-xs leading-relaxed" style={{ color: 'var(--texte-doux)' }}>
                    {t('landing.tarifs_note')}
                </p>
            </div>
        </section>
    );
}
