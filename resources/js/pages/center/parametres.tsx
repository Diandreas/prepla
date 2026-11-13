import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { ArrowRight, BookOpen, Check, Copy, Gift, Globe, KeyRound, Palette, Settings, Users, Wallet } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { LogoDeCentre } from '@/components/app-logo';
import OngletsParametresCentre from '@/components/onglets-parametres-centre';
import { Badge } from '@/components/ui/badge';
import { SUPPORT_WHATSAPP_URL } from '@/lib/contact';
import { texteSur } from '@/lib/marque-centre.js';
import { formaterMontant } from '@/lib/montant-stripe';
import { useT } from '@/lib/i18n/traduire';

type Formule = 'paliers' | 'revente' | 'libre_service';

interface Props {
    centre: { id: number; name: string };
    identite: {
        nom_affiche: string;
        nom_personnalise: boolean;
        logo_url: string | null;
        primaire: string;
        accent: string;
        couleurs_personnalisees: boolean;
        masquer_prepla: boolean;
    };
    examens: {
        tous: boolean;
        total_proposables: number;
        liste: { id: number; name: string; langue: string | null }[];
        contenu_eleves?: 'libre' | 'centre';
    };
    adresse: {
        commune: string;
        domaines: { id: number; host: string; type: 'subdomain' | 'custom'; verifie: boolean; principal: boolean }[];
    };
    formule: {
        formule: Formule;
        sieges_utilises: number;
        sieges_limite: number;
        eleves_actifs: number;
        palier: string;
        mois: string;
    };
    acces: { en_cours: number; prix: number | null; duree_jours: number; devise: string } | null;
    parrainage: {
        lien: string;
        comptes: number;
        taux: number;
        periode: string;
        totaux: { devise: string; commission: number }[];
    } | null;
    equipe: { responsables: number; enseignants: number; classes: number; invitations: number };
}

const FORMULES: Record<Formule, { titre: string; texte: string }> = {
    paliers: {
        titre: 'Abonnement par paliers',
        texte: 'Vos élèves travaillent sans limite ; le prix suit le nombre d’élèves actifs du mois.',
    },
    revente: {
        titre: 'Revente d’accès',
        texte: 'Vous encaissez vous-même l’accès de vos élèves et l’ouvrez dans PrePla.',
    },
    libre_service: {
        titre: 'Espace libre-service',
        texte: 'Espace ouvert par un enseignant : outils complets, entraînement personnel limité.',
    },
};

const PALIERS: Record<string, string> = {
    A1: 'Palier A1 · jusqu’à 50 actifs',
    A2: 'Palier A2 · jusqu’à 150 actifs',
    A3: 'Palier A3 · jusqu’à 300 actifs',
    reseau: 'Palier Réseau · plus de 300 actifs',
};

function nomDuMois(mois: string, locale: string): string {
    const [a, m] = mois.split('-').map(Number);
    if (!a || !m) return mois;
    return new Date(a, m - 1, 1).toLocaleDateString(locale, { month: 'long', year: 'numeric' });
}

/** Une carte de reglage : son etat actuel en resume, et le chemin vers la page. */
function Carte({
    icone,
    titre,
    etat,
    children,
    lien,
}: {
    icone: ReactNode;
    titre: string;
    etat?: ReactNode;
    children: ReactNode;
    lien: { libelle: string; href: string; externe?: boolean };
}) {
    const classesLien =
        'mt-auto inline-flex items-center gap-1.5 self-start rounded-lg border border-border bg-background px-3 py-1.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted';

    return (
        <section className="flex min-w-0 flex-col gap-3 rounded-2xl border border-border bg-card p-4 shadow-sm md:p-5">
            <header className="flex items-start justify-between gap-2">
                <div className="flex min-w-0 items-center gap-2.5">
                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">{icone}</span>
                    <h2 className="truncate text-base font-bold tracking-tight">{titre}</h2>
                </div>
                {etat}
            </header>
            <div className="min-w-0 space-y-2 text-sm">{children}</div>
            {lien.externe ? (
                <a href={lien.href} target="_blank" rel="noopener noreferrer" className={classesLien}>
                    {lien.libelle}
                    <ArrowRight className="h-4 w-4" aria-hidden="true" />
                </a>
            ) : (
                <Link href={lien.href} className={classesLien}>
                    {lien.libelle}
                    <ArrowRight className="h-4 w-4" aria-hidden="true" />
                </Link>
            )}
        </section>
    );
}

function EtatOk({ children }: { children: ReactNode }) {
    return (
        <Badge variant="outline" className="shrink-0 border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
            {children}
        </Badge>
    );
}

function EtatNeutre({ children }: { children: ReactNode }) {
    return (
        <Badge variant="outline" className="shrink-0 text-muted-foreground">
            {children}
        </Badge>
    );
}

function Ligne({ libelle, children }: { libelle: string; children: ReactNode }) {
    return (
        <div className="flex items-baseline justify-between gap-3">
            <span className="text-muted-foreground">{libelle}</span>
            <span className="min-w-0 truncate text-right font-medium">{children}</span>
        </div>
    );
}

/**
 * Les parametres du centre, pour son responsable : une carte par reglage, qui
 * resume l'etat actuel et mene a la page ou on le change.
 */
export default function ParametresDuCentre({ centre, identite, examens, adresse, formule, acces, parrainage, equipe }: Props) {
    const { t, locale } = useT();
    const [copie, setCopie] = useState(false);
    const nombre = (n: number) => n.toLocaleString(locale);

    const f =FORMULES[formule.formule] ?? FORMULES.paliers;
    const limite = Math.max(0, formule.sieges_limite);
    const part = limite > 0 ? Math.min(100, Math.round((formule.sieges_utilises / limite) * 100)) : 0;
    const presquePlein = limite > 0 && part >= 90;

    const domainesActifs = adresse.domaines.filter((d) => d.verifie);
    const principal = adresse.domaines.find((d) => d.principal && d.verifie) ?? domainesActifs[0];

    // Les examens retenus, groupes par langue.
    const parLangue = examens.liste.reduce<Record<string, string[]>>((acc, e) => {
        const l = e.langue ?? '—';
        (acc[l] ??= []).push(e.name);
        return acc;
    }, {});

    async function copier() {
        if (!parrainage) return;
        try {
            await navigator.clipboard.writeText(parrainage.lien);
            setCopie(true);
            setTimeout(() => setCopie(false), 2000);
        } catch {
            // Presse-papiers refuse : le lien reste lisible et selectionnable.
        }
    }

    return (
        <AppLayout>
            <Head title={t('Paramètres du centre — {nom}', { nom: centre.name })} />
            <div className="mx-auto w-full max-w-5xl space-y-5 p-4 md:p-6">
                <div className="flex items-start gap-3">
                    <span className="mt-0.5 flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-sm">
                        <Settings className="h-6 w-6" aria-hidden="true" />
                    </span>
                    <div className="min-w-0">
                        <p className="truncate text-sm text-muted-foreground">{centre.name}</p>
                        <h1 className="text-2xl font-bold tracking-tight">{t('Paramètres du centre')}</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {t('Tout ce qui règle votre centre, au même endroit : votre image, vos examens, votre adresse, votre formule et votre équipe.')}
                        </p>
                    </div>
                </div>

                <OngletsParametresCentre />

                <div className="grid gap-4 md:grid-cols-2">
                    {/* Identite et marque */}
                    <Carte
                        icone={<Palette className="h-5 w-5" aria-hidden="true" />}
                        titre={t('Identité et marque')}
                        etat={identite.couleurs_personnalisees || identite.logo_url || identite.nom_personnalise
                            ? <EtatOk>{t('Personnalisée')}</EtatOk>
                            : <EtatNeutre>{t('Par défaut')}</EtatNeutre>}
                        lien={{ libelle: t('Modifier l’identité'), href: '/center/personnalisation' }}
                    >
                        <div className="flex items-center rounded-xl border border-border bg-background p-3">
                            <div className="flex min-w-0 flex-1 items-center">
                                <LogoDeCentre
                                    nom={identite.nom_affiche}
                                    logoUrl={identite.logo_url}
                                    masquerPrepla={identite.masquer_prepla}
                                    primaire={identite.primaire}
                                    texteSurPrimaire={texteSur(identite.primaire)}
                                    accent={identite.accent}
                                />
                            </div>
                        </div>
                        <div className="flex flex-wrap items-center gap-3">
                            <span className="inline-flex items-center gap-1.5 text-muted-foreground">
                                <span className="h-4 w-4 rounded-full ring-1 ring-black/10" style={{ background: identite.primaire }} aria-hidden="true" />
                                {t('Principale')} <span className="font-mono text-xs text-foreground">{identite.primaire}</span>
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-muted-foreground">
                                <span className="h-4 w-4 rounded-full ring-1 ring-black/10" style={{ background: identite.accent }} aria-hidden="true" />
                                {t('Accent')} <span className="font-mono text-xs text-foreground">{identite.accent}</span>
                            </span>
                        </div>
                        <p className="text-muted-foreground">
                            {identite.masquer_prepla ? t('Marque blanche : PrePla n’apparaît pas à vos élèves.') : t('Affiché « avec PrePla » sous votre nom.')}
                        </p>
                    </Carte>

                    {/* Examens proposes */}
                    <Carte
                        icone={<BookOpen className="h-5 w-5" aria-hidden="true" />}
                        titre={t('Examens proposés')}
                        etat={<EtatNeutre>{examens.tous ? t('Tous') : t('{n} sur {total}', { n: examens.liste.length, total: examens.total_proposables })}</EtatNeutre>}
                        lien={{ libelle: t('Choisir les examens'), href: '/center/personnalisation#examens' }}
                    >
                        <p className="text-muted-foreground">
                            {examens.tous
                                ? t('Vos élèves voient tous les examens de PrePla, y compris ceux ajoutés plus tard.')
                                : t('Vos élèves ne voient que les examens que vous avez retenus.')}
                        </p>
                        {examens.contenu_eleves && (
                            <p className="text-muted-foreground">
                                {t('Parcours des élèves :')}{' '}
                                <span className="font-medium text-foreground">
                                    {examens.contenu_eleves === 'centre' ? t('parcours créé par le centre') : t('parcours PrePla par défaut')}
                                </span>
                            </p>
                        )}
                        {examens.liste.length === 0 ? (
                            <p className="text-muted-foreground">{t('Aucun examen disponible pour l’instant.')}</p>
                        ) : (
                            <ul className="space-y-1.5">
                                {Object.entries(parLangue).map(([langue, noms]) => (
                                    <li key={langue} className="flex flex-wrap items-baseline gap-1.5">
                                        <span className="w-20 shrink-0 text-xs font-semibold text-muted-foreground">{t(langue)}</span>
                                        {noms.map((n) => (
                                            <span key={n} className="rounded-md bg-muted px-2 py-0.5 text-xs font-medium">{n}</span>
                                        ))}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Carte>

                    {/* Adresse web */}
                    <Carte
                        icone={<Globe className="h-5 w-5" aria-hidden="true" />}
                        titre={t('Adresse web')}
                        etat={domainesActifs.length > 0 ? <EtatOk>{t('Adresse propre active')}</EtatOk> : <EtatNeutre>{t('Adresse PrePla')}</EtatNeutre>}
                        lien={{
                            libelle: adresse.domaines.length > 0 ? t('Demander un changement') : t('Demander une adresse à votre nom'),
                            href: SUPPORT_WHATSAPP_URL,
                            externe: true,
                        }}
                    >
                        <Ligne libelle={t('Adresse PrePla')}>{adresse.commune}</Ligne>
                        {principal && <Ligne libelle={t('Votre adresse')}>{principal.host}</Ligne>}
                        {adresse.domaines.length > 0 ? (
                            <ul className="space-y-1.5">
                                {adresse.domaines.map((d) => (
                                    <li key={d.id} className="flex items-center justify-between gap-2 rounded-lg border border-border px-3 py-2">
                                        <span className="min-w-0">
                                            <span className="block truncate font-medium">{d.host}</span>
                                            <span className="text-xs text-muted-foreground">
                                                {d.type === 'subdomain' ? t('Sous-domaine PrePla') : t('Domaine propre')}
                                                {d.principal ? t(' · principale') : ''}
                                            </span>
                                        </span>
                                        {d.verifie ? <EtatOk>{t('Active')}</EtatOk> : <EtatNeutre>{t('En attente')}</EtatNeutre>}
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="text-muted-foreground">
                                {t('Vos élèves peuvent entrer par une adresse à votre nom (sous-domaine ou domaine propre). L’équipe PrePla la met en place pour vous.')}
                            </p>
                        )}
                    </Carte>

                    {/* Formule et sieges */}
                    <Carte
                        icone={<Wallet className="h-5 w-5" aria-hidden="true" />}
                        titre={t('Formule et sièges')}
                        etat={<EtatNeutre>{t(f.titre)}</EtatNeutre>}
                        lien={{ libelle: t('Changer de formule'), href: SUPPORT_WHATSAPP_URL, externe: true }}
                    >
                        <p className="text-muted-foreground">{t(f.texte)}</p>
                        <div>
                            <div className="flex items-baseline justify-between gap-3">
                                <span className="text-muted-foreground">{t('Sièges utilisés')}</span>
                                <span className="font-semibold tabular-nums">
                                    {nombre(formule.sieges_utilises)} / {nombre(limite)}
                                </span>
                            </div>
                            <div
                                className="mt-1.5 h-2 overflow-hidden rounded-full bg-muted"
                                role="progressbar"
                                aria-label={t('Sièges utilisés')}
                                aria-valuemin={0}
                                aria-valuemax={limite}
                                aria-valuenow={formule.sieges_utilises}
                            >
                                <div className={`h-full rounded-full ${presquePlein ? 'bg-amber-500' : 'bg-primary'}`} style={{ width: `${part}%` }} />
                            </div>
                            {presquePlein && <p className="mt-1 text-xs text-amber-700 dark:text-amber-300">{t('Presque plein : demandez des sièges en plus.')}</p>}
                        </div>
                        <Ligne libelle={t('Élèves actifs ({mois})', { mois: nomDuMois(formule.mois, locale) })}>
                            <span className="tabular-nums">{nombre(formule.eleves_actifs)}</span>
                        </Ligne>
                        {formule.formule === 'paliers' && <Ligne libelle={t('Palier du mois')}>{PALIERS[formule.palier] ? t(PALIERS[formule.palier]) : formule.palier}</Ligne>}
                    </Carte>

                    {/* Acces vendus : en formule revente seulement */}
                    {acces && (
                        <Carte
                            icone={<KeyRound className="h-5 w-5" aria-hidden="true" />}
                            titre={t('Accès vendus')}
                            etat={acces.en_cours > 0 ? <EtatOk>{t('{n} en cours', { n: nombre(acces.en_cours) })}</EtatOk> : <EtatNeutre>{t('Aucun en cours')}</EtatNeutre>}
                            lien={{ libelle: t('Gérer les accès'), href: '/center/acces' }}
                        >
                            <Ligne libelle={t('Élèves avec un accès')}>{nombre(acces.en_cours)}</Ligne>
                            <Ligne libelle={t('Prix d’un accès')}>
                                {acces.prix !== null
                                    ? `${nombre(acces.prix)} ${acces.devise === 'XAF' ? 'FCFA' : acces.devise}`
                                    : t('Non fixé')}
                            </Ligne>
                            <Ligne libelle={t('Durée')}>
                                {acces.duree_jours > 1 ? t('{n} jours', { n: nombre(acces.duree_jours) }) : t('{n} jour', { n: nombre(acces.duree_jours) })}
                            </Ligne>
                        </Carte>
                    )}

                    {/* Parrainage */}
                    {parrainage && (
                        <Carte
                            icone={<Gift className="h-5 w-5" aria-hidden="true" />}
                            titre={t('Parrainage')}
                            etat={<EtatNeutre>{t('{taux} % reversés', { taux: parrainage.taux })}</EtatNeutre>}
                            lien={{ libelle: t('Voir le parrainage'), href: '/center/parrainage' }}
                        >
                            <div className="flex items-center gap-2 rounded-lg border border-border bg-background p-2">
                                <span className="min-w-0 flex-1 truncate font-mono text-xs" title={parrainage.lien}>{parrainage.lien}</span>
                                <button
                                    type="button"
                                    onClick={copier}
                                    className="inline-flex shrink-0 items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold text-primary hover:bg-primary/10"
                                >
                                    {copie ? <Check className="h-3.5 w-3.5" aria-hidden="true" /> : <Copy className="h-3.5 w-3.5" aria-hidden="true" />}
                                    {copie ? t('Copié') : t('Copier')}
                                </button>
                            </div>
                            <Ligne libelle={t('Comptes parrainés')}>{nombre(parrainage.comptes)}</Ligne>
                            <Ligne libelle={t('Gains ({mois})', { mois: nomDuMois(parrainage.periode, locale) })}>
                                {/* Les gains arrivent en euros ou en francs selon le paiement : sans
                                    commission, on ne devine pas une devise, on le dit. */}
                                {parrainage.totaux.length === 0
                                    ? t('Aucun pour l’instant')
                                    : parrainage.totaux.map((tot) => formaterMontant(tot.commission, tot.devise)).join(' + ')}
                            </Ligne>
                        </Carte>
                    )}

                    {/* Equipe */}
                    <Carte
                        icone={<Users className="h-5 w-5" aria-hidden="true" />}
                        titre={t('Équipe')}
                        etat={equipe.invitations > 0 ? (
                            <EtatNeutre>
                                {equipe.invitations > 1
                                    ? t('{n} invitations', { n: nombre(equipe.invitations) })
                                    : t('{n} invitation', { n: nombre(equipe.invitations) })}
                            </EtatNeutre>
                        ) : undefined}
                        lien={{ libelle: t('Gérer l’équipe'), href: '/center/equipe' }}
                    >
                        <Ligne libelle={t('Responsables')}>{nombre(equipe.responsables)}</Ligne>
                        <Ligne libelle={t('Enseignants')}>{nombre(equipe.enseignants)}</Ligne>
                        <Ligne libelle={t('Classes ouvertes')}>{nombre(equipe.classes)}</Ligne>
                    </Carte>
                </div>

                <p className="text-center text-xs text-muted-foreground">
                    {t('Votre compte personnel (nom, mot de passe, langue) se règle dans')}{' '}
                    <Link href="/settings/profile" className="font-medium text-foreground underline underline-offset-2">{t('Mon compte')}</Link>.
                </p>
            </div>
        </AppLayout>
    );
}
