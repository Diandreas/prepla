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

function nomDuMois(mois: string): string {
    const [a, m] = mois.split('-').map(Number);
    if (!a || !m) return mois;
    return new Date(a, m - 1, 1).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
}

function pluriel(n: number, un: string, plusieurs: string): string {
    return `${n.toLocaleString('fr-FR')} ${n > 1 ? plusieurs : un}`;
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
    const [copie, setCopie] = useState(false);

    const f = FORMULES[formule.formule] ?? FORMULES.paliers;
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
            <Head title={`Paramètres du centre — ${centre.name}`} />
            <div className="mx-auto w-full max-w-5xl space-y-5 p-4 md:p-6">
                <div className="flex items-start gap-3">
                    <span className="mt-0.5 flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-sm">
                        <Settings className="h-6 w-6" aria-hidden="true" />
                    </span>
                    <div className="min-w-0">
                        <p className="truncate text-sm text-muted-foreground">{centre.name}</p>
                        <h1 className="text-2xl font-bold tracking-tight">Paramètres du centre</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Tout ce qui règle votre centre, au même endroit : votre image, vos examens, votre adresse, votre formule et votre équipe.
                        </p>
                    </div>
                </div>

                <OngletsParametresCentre />

                <div className="grid gap-4 md:grid-cols-2">
                    {/* Identite et marque */}
                    <Carte
                        icone={<Palette className="h-5 w-5" aria-hidden="true" />}
                        titre="Identité et marque"
                        etat={identite.couleurs_personnalisees || identite.logo_url || identite.nom_personnalise
                            ? <EtatOk>Personnalisée</EtatOk>
                            : <EtatNeutre>Par défaut</EtatNeutre>}
                        lien={{ libelle: 'Modifier l’identité', href: '/center/personnalisation' }}
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
                                Principale <span className="font-mono text-xs text-foreground">{identite.primaire}</span>
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-muted-foreground">
                                <span className="h-4 w-4 rounded-full ring-1 ring-black/10" style={{ background: identite.accent }} aria-hidden="true" />
                                Accent <span className="font-mono text-xs text-foreground">{identite.accent}</span>
                            </span>
                        </div>
                        <p className="text-muted-foreground">
                            {identite.masquer_prepla ? 'Marque blanche : PrePla n’apparaît pas à vos élèves.' : 'Affiché « avec PrePla » sous votre nom.'}
                        </p>
                    </Carte>

                    {/* Examens proposes */}
                    <Carte
                        icone={<BookOpen className="h-5 w-5" aria-hidden="true" />}
                        titre="Examens proposés"
                        etat={<EtatNeutre>{examens.tous ? 'Tous' : `${examens.liste.length} sur ${examens.total_proposables}`}</EtatNeutre>}
                        lien={{ libelle: 'Choisir les examens', href: '/center/personnalisation#examens' }}
                    >
                        <p className="text-muted-foreground">
                            {examens.tous
                                ? 'Vos élèves voient tous les examens de PrePla, y compris ceux ajoutés plus tard.'
                                : 'Vos élèves ne voient que les examens que vous avez retenus.'}
                        </p>
                        {examens.contenu_eleves && (
                            <p className="text-muted-foreground">
                                Parcours des élèves :{' '}
                                <span className="font-medium text-foreground">
                                    {examens.contenu_eleves === 'centre' ? 'parcours créé par le centre' : 'parcours PrePla par défaut'}
                                </span>
                            </p>
                        )}
                        {examens.liste.length === 0 ? (
                            <p className="text-muted-foreground">Aucun examen disponible pour l’instant.</p>
                        ) : (
                            <ul className="space-y-1.5">
                                {Object.entries(parLangue).map(([langue, noms]) => (
                                    <li key={langue} className="flex flex-wrap items-baseline gap-1.5">
                                        <span className="w-20 shrink-0 text-xs font-semibold text-muted-foreground">{langue}</span>
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
                        titre="Adresse web"
                        etat={domainesActifs.length > 0 ? <EtatOk>Adresse propre active</EtatOk> : <EtatNeutre>Adresse PrePla</EtatNeutre>}
                        lien={{
                            libelle: adresse.domaines.length > 0 ? 'Demander un changement' : 'Demander une adresse à votre nom',
                            href: SUPPORT_WHATSAPP_URL,
                            externe: true,
                        }}
                    >
                        <Ligne libelle="Adresse PrePla">{adresse.commune}</Ligne>
                        {principal && <Ligne libelle="Votre adresse">{principal.host}</Ligne>}
                        {adresse.domaines.length > 0 ? (
                            <ul className="space-y-1.5">
                                {adresse.domaines.map((d) => (
                                    <li key={d.id} className="flex items-center justify-between gap-2 rounded-lg border border-border px-3 py-2">
                                        <span className="min-w-0">
                                            <span className="block truncate font-medium">{d.host}</span>
                                            <span className="text-xs text-muted-foreground">
                                                {d.type === 'subdomain' ? 'Sous-domaine PrePla' : 'Domaine propre'}
                                                {d.principal ? ' · principale' : ''}
                                            </span>
                                        </span>
                                        {d.verifie ? <EtatOk>Active</EtatOk> : <EtatNeutre>En attente</EtatNeutre>}
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="text-muted-foreground">
                                Vos élèves peuvent entrer par une adresse à votre nom (sous-domaine ou domaine propre). L’équipe PrePla la met en place pour vous.
                            </p>
                        )}
                    </Carte>

                    {/* Formule et sieges */}
                    <Carte
                        icone={<Wallet className="h-5 w-5" aria-hidden="true" />}
                        titre="Formule et sièges"
                        etat={<EtatNeutre>{f.titre}</EtatNeutre>}
                        lien={{ libelle: 'Changer de formule', href: SUPPORT_WHATSAPP_URL, externe: true }}
                    >
                        <p className="text-muted-foreground">{f.texte}</p>
                        <div>
                            <div className="flex items-baseline justify-between gap-3">
                                <span className="text-muted-foreground">Sièges utilisés</span>
                                <span className="font-semibold tabular-nums">
                                    {formule.sieges_utilises.toLocaleString('fr-FR')} / {limite.toLocaleString('fr-FR')}
                                </span>
                            </div>
                            <div
                                className="mt-1.5 h-2 overflow-hidden rounded-full bg-muted"
                                role="progressbar"
                                aria-label="Sièges utilisés"
                                aria-valuemin={0}
                                aria-valuemax={limite}
                                aria-valuenow={formule.sieges_utilises}
                            >
                                <div className={`h-full rounded-full ${presquePlein ? 'bg-amber-500' : 'bg-primary'}`} style={{ width: `${part}%` }} />
                            </div>
                            {presquePlein && <p className="mt-1 text-xs text-amber-700 dark:text-amber-300">Presque plein : demandez des sièges en plus.</p>}
                        </div>
                        <Ligne libelle={`Élèves actifs (${nomDuMois(formule.mois)})`}>
                            <span className="tabular-nums">{formule.eleves_actifs.toLocaleString('fr-FR')}</span>
                        </Ligne>
                        {formule.formule === 'paliers' && <Ligne libelle="Palier du mois">{PALIERS[formule.palier] ?? formule.palier}</Ligne>}
                    </Carte>

                    {/* Acces vendus : en formule revente seulement */}
                    {acces && (
                        <Carte
                            icone={<KeyRound className="h-5 w-5" aria-hidden="true" />}
                            titre="Accès vendus"
                            etat={acces.en_cours > 0 ? <EtatOk>{pluriel(acces.en_cours, 'en cours', 'en cours')}</EtatOk> : <EtatNeutre>Aucun en cours</EtatNeutre>}
                            lien={{ libelle: 'Gérer les accès', href: '/center/acces' }}
                        >
                            <Ligne libelle="Élèves avec un accès">{acces.en_cours.toLocaleString('fr-FR')}</Ligne>
                            <Ligne libelle="Prix d’un accès">
                                {acces.prix !== null
                                    ? `${acces.prix.toLocaleString('fr-FR')} ${acces.devise === 'XAF' ? 'FCFA' : acces.devise}`
                                    : 'Non fixé'}
                            </Ligne>
                            <Ligne libelle="Durée">{pluriel(acces.duree_jours, 'jour', 'jours')}</Ligne>
                        </Carte>
                    )}

                    {/* Parrainage */}
                    {parrainage && (
                        <Carte
                            icone={<Gift className="h-5 w-5" aria-hidden="true" />}
                            titre="Parrainage"
                            etat={<EtatNeutre>{parrainage.taux} % reversés</EtatNeutre>}
                            lien={{ libelle: 'Voir le parrainage', href: '/center/parrainage' }}
                        >
                            <div className="flex items-center gap-2 rounded-lg border border-border bg-background p-2">
                                <span className="min-w-0 flex-1 truncate font-mono text-xs" title={parrainage.lien}>{parrainage.lien}</span>
                                <button
                                    type="button"
                                    onClick={copier}
                                    className="inline-flex shrink-0 items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold text-primary hover:bg-primary/10"
                                >
                                    {copie ? <Check className="h-3.5 w-3.5" aria-hidden="true" /> : <Copy className="h-3.5 w-3.5" aria-hidden="true" />}
                                    {copie ? 'Copié' : 'Copier'}
                                </button>
                            </div>
                            <Ligne libelle="Comptes parrainés">{parrainage.comptes.toLocaleString('fr-FR')}</Ligne>
                            <Ligne libelle={`Gains (${nomDuMois(parrainage.periode)})`}>
                                {/* Les gains arrivent en euros ou en francs selon le paiement : sans
                                    commission, on ne devine pas une devise, on le dit. */}
                                {parrainage.totaux.length === 0
                                    ? 'Aucun pour l’instant'
                                    : parrainage.totaux.map((t) => formaterMontant(t.commission, t.devise)).join(' + ')}
                            </Ligne>
                        </Carte>
                    )}

                    {/* Equipe */}
                    <Carte
                        icone={<Users className="h-5 w-5" aria-hidden="true" />}
                        titre="Équipe"
                        etat={equipe.invitations > 0 ? <EtatNeutre>{pluriel(equipe.invitations, 'invitation', 'invitations')}</EtatNeutre> : undefined}
                        lien={{ libelle: 'Gérer l’équipe', href: '/center/equipe' }}
                    >
                        <Ligne libelle="Responsables">{equipe.responsables.toLocaleString('fr-FR')}</Ligne>
                        <Ligne libelle="Enseignants">{equipe.enseignants.toLocaleString('fr-FR')}</Ligne>
                        <Ligne libelle="Classes ouvertes">{equipe.classes.toLocaleString('fr-FR')}</Ligne>
                    </Carte>
                </div>

                <p className="text-center text-xs text-muted-foreground">
                    Votre compte personnel (nom, mot de passe, langue) se règle dans{' '}
                    <Link href="/settings/profile" className="font-medium text-foreground underline underline-offset-2">Mon compte</Link>.
                </p>
            </div>
        </AppLayout>
    );
}
