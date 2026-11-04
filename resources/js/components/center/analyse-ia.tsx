import { Link } from '@inertiajs/react';
import axios from 'axios';
import { AlertTriangle, CalendarClock, Lightbulb, RefreshCw, Sparkles, Star, Target, TrendingUp } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { dateHeure } from '@/lib/suivi';

/**
 * La carte « Analyse IA » : le probleme principal, le levier de progression et
 * ce que l'enseignant peut faire cette semaine — pour un eleve, une classe ou
 * le centre entier.
 *
 * Elle se charge APRES la page : l'attente de l'IA ne retarde jamais la fiche.
 * 1. GET sur `url` : l'analyse gardee, sans jamais appeler l'IA.
 * 2. Si elle manque ou n'est plus a jour, un POST (protege par CSRF) la genere ;
 *    l'ancienne reste affichee pendant ce temps. Le serveur borne ces appels.
 * 3. « Actualiser » : POST avec `actualiser`, une fois par heure et par sujet
 *    (app/Http/Controllers/Center/AnalyseController.php).
 */

type Sujet = 'eleve' | 'classe' | 'centre';
type Risque = 'faible' | 'moyen' | 'eleve';

interface Commun {
    probleme: { titre: string; explication: string; preuve: string };
    levier: { titre: string; actions: string[] };
    point_fort: string;
    risque: { niveau: Risque; raison: string };
    prochain_devoir: { competence: string; format: string; pourquoi: string };
    eleves_a_surveiller?: { id: number; nom: string; raison: string }[];
}

interface DuCentre {
    constats: { titre: string; explication: string }[];
    leviers: { titre: string; action: string }[];
    point_fort: string;
    classes_qui_decrochent: { id: number; nom: string; raison: string }[];
}

interface Analyse {
    sujet: Sujet;
    etat: 'pret' | 'insuffisant' | 'indisponible' | 'a_generer' | 'en_cours';
    a_jour: boolean;
    source: 'ia' | 'regles' | null;
    generee_le: string | null;
    message: string | null;
    contenu: Commun | DuCentre | null;
    actualisable_dans: number;
}

const RISQUES: Record<Risque, { libelle: string; classe: string }> = {
    faible: { libelle: 'Risque faible', classe: 'border-emerald-300 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300' },
    moyen: { libelle: 'Risque moyen', classe: 'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-300' },
    eleve: { libelle: 'Risque élevé', classe: 'border-rose-300 bg-rose-50 text-rose-800 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-300' },
};

const INDISPONIBLE: Analyse = {
    sujet: 'eleve',
    etat: 'indisponible',
    a_jour: false,
    source: null,
    generee_le: null,
    message: 'L’analyse n’est pas disponible pour le moment. Réessayez dans quelques minutes.',
    contenu: null,
    actualisable_dans: 0,
};

export default function AnalyseIa({ sujet, url, className = '' }: { sujet: Sujet; url: string; className?: string }) {
    const [analyse, setAnalyse] = useState<Analyse | null>(null);
    const [enCours, setEnCours] = useState(false);
    const [avis, setAvis] = useState<string | null>(null);

    useEffect(() => {
        let actif = true;
        let minuterie: ReturnType<typeof setTimeout> | undefined;
        const entetes = { headers: { Accept: 'application/json' } };
        setAnalyse(null);

        // Une autre ouverture prepare l'analyse : on relit quelques fois, sans appeler l'IA.
        const lire = (essai: number) => {
            axios
                .get<{ analyse: Analyse }>(url, entetes)
                .then((r) => {
                    if (!actif) return;
                    const a = r.data.analyse;
                    if (a.etat === 'en_cours' && essai < 6) {
                        setAnalyse(a);
                        minuterie = setTimeout(() => lire(essai + 1), 5000);
                    } else if (essai === 0 && (a.etat === 'a_generer' || (a.etat === 'pret' && !a.a_jour))) {
                        if (a.etat === 'pret') setAnalyse(a);
                        generer();
                    } else {
                        setAnalyse(a);
                    }
                })
                .catch(() => actif && setAnalyse({ ...INDISPONIBLE, sujet }));
        };

        const generer = () => {
            axios
                .post<{ analyse: Analyse }>(url, {}, entetes)
                .then((r) => {
                    if (!actif) return;
                    if (r.data.analyse.etat === 'en_cours') minuterie = setTimeout(() => lire(1), 5000);
                    else {
                        setAnalyse(r.data.analyse);
                        if (r.data.analyse.etat === 'pret' && r.data.analyse.message) setAvis(r.data.analyse.message);
                    }
                })
                .catch(() => actif && setAnalyse((avant) => (avant?.etat === 'pret' ? avant : { ...INDISPONIBLE, sujet })));
        };

        lire(0);

        return () => {
            actif = false;
            if (minuterie) clearTimeout(minuterie);
        };
    }, [url, sujet]);

    const actualiser = useCallback(() => {
        setEnCours(true);
        setAvis(null);
        axios
            .post<{ analyse: Analyse; message?: string }>(url, { actualiser: true }, { headers: { Accept: 'application/json' } })
            .then((r) => {
                setAnalyse(r.data.analyse);
                if (r.data.analyse.message && r.data.analyse.etat === 'pret') setAvis(r.data.analyse.message);
            })
            .catch((e) => {
                const donnees = e?.response?.data as { analyse?: Analyse; message?: string } | undefined;
                if (donnees?.analyse) setAnalyse(donnees.analyse);
                setAvis(donnees?.message ?? 'L’actualisation n’a pas abouti. Réessayez plus tard.');
            })
            .finally(() => setEnCours(false));
    }, [url]);

    const contenu = analyse?.etat === 'pret' ? analyse.contenu : null;
    const risque = contenu && 'risque' in contenu ? RISQUES[contenu.risque.niveau] : null;
    const bloque = (analyse?.actualisable_dans ?? 0) > 0;

    return (
        <Card className={`overflow-hidden border-primary/25 ${className}`}>
            <CardHeader className="flex-row flex-wrap items-center justify-between gap-2 space-y-0 border-b border-border bg-primary/5 pb-3">
                <CardTitle className="flex items-center gap-2 text-base">
                    <Sparkles className="h-4 w-4 text-primary" aria-hidden />
                    Analyse IA
                    <span className="text-sm font-normal text-muted-foreground">
                        · {sujet === 'eleve' ? 'élève' : sujet === 'classe' ? 'classe' : 'centre'}
                    </span>
                </CardTitle>
                {risque && (
                    <span className={`rounded-full border px-2.5 py-0.5 text-xs font-medium ${risque.classe}`} title={(contenu as Commun).risque.raison}>
                        {risque.libelle}
                    </span>
                )}
            </CardHeader>

            <CardContent className="space-y-4 p-4">
                {(analyse === null || analyse.etat === 'a_generer' || analyse.etat === 'en_cours') && <Squelette />}

                {analyse && analyse.etat !== 'pret' && analyse.etat !== 'a_generer' && (
                    <p className="flex items-start gap-2 text-sm text-muted-foreground">
                        <CalendarClock className="mt-0.5 h-4 w-4 shrink-0" aria-hidden />
                        <span>{analyse.message}</span>
                    </p>
                )}

                {contenu && 'probleme' in contenu && <VueCommune contenu={contenu} />}
                {contenu && 'constats' in contenu && <VueCentre contenu={contenu} />}

                {analyse && analyse.etat === 'pret' && (
                    <div className="flex flex-wrap items-center justify-between gap-2 border-t border-border pt-3">
                        <p className="text-xs text-muted-foreground">
                            {analyse.source === 'ia' ? 'Analyse IA' : 'Analyse automatique (sans IA)'} · générée le {dateHeure(analyse.generee_le)}
                        </p>
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={actualiser}
                            disabled={enCours || bloque}
                            title={bloque ? `Nouvelle actualisation possible dans ${Math.ceil(analyse.actualisable_dans / 60)} min` : 'Refaire l’analyse avec les dernières données'}
                        >
                            <RefreshCw className={`mr-1.5 h-3.5 w-3.5 ${enCours ? 'animate-spin' : ''}`} aria-hidden />
                            {enCours ? 'Analyse en cours…' : 'Actualiser'}
                        </Button>
                    </div>
                )}
                {avis && <p className="text-xs text-amber-700 dark:text-amber-400">{avis}</p>}
            </CardContent>
        </Card>
    );
}

function Squelette() {
    return (
        <div className="space-y-3" aria-busy="true" aria-label="Analyse en cours de chargement">
            <Skeleton className="h-20 w-full" />
            <Skeleton className="h-4 w-2/3" />
            <Skeleton className="h-4 w-1/2" />
            <div className="grid gap-3 sm:grid-cols-2">
                <Skeleton className="h-14" />
                <Skeleton className="h-14" />
            </div>
        </div>
    );
}

function VueCommune({ contenu }: { contenu: Commun }) {
    return (
        <>
            <section className="rounded-lg border border-rose-200 bg-rose-50/70 p-3 dark:border-rose-900/70 dark:bg-rose-950/25">
                <p className="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-400">
                    <AlertTriangle className="h-3.5 w-3.5" aria-hidden /> Problème principal
                </p>
                <p className="mt-1 break-words font-semibold">{contenu.probleme.titre}</p>
                {contenu.probleme.explication && <p className="mt-1 break-words text-sm">{contenu.probleme.explication}</p>}
                {contenu.probleme.preuve && <p className="mt-1.5 break-words text-xs text-muted-foreground">Preuve : {contenu.probleme.preuve}</p>}
            </section>

            <section>
                <p className="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">
                    <TrendingUp className="h-3.5 w-3.5" aria-hidden /> Levier de progression
                </p>
                <p className="mt-1 break-words font-semibold">{contenu.levier.titre}</p>
                <ol className="mt-2 space-y-1.5">
                    {contenu.levier.actions.map((a, i) => (
                        <li key={i} className="flex gap-2 text-sm">
                            <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                {i + 1}
                            </span>
                            <span className="min-w-0 break-words">{a}</span>
                        </li>
                    ))}
                </ol>
            </section>

            <div className="grid gap-3 sm:grid-cols-2">
                <div className="rounded-lg border border-border p-3">
                    <p className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                        <Star className="h-3.5 w-3.5" aria-hidden /> Point fort
                    </p>
                    <p className="mt-1 break-words text-sm">{contenu.point_fort}</p>
                </div>
                <div className="rounded-lg border border-border p-3">
                    <p className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                        <Target className="h-3.5 w-3.5" aria-hidden /> Prochain devoir conseillé
                    </p>
                    <p className="mt-1 break-words text-sm font-medium">
                        {contenu.prochain_devoir.competence} · {contenu.prochain_devoir.format}
                    </p>
                    {contenu.prochain_devoir.pourquoi && <p className="mt-0.5 break-words text-xs text-muted-foreground">{contenu.prochain_devoir.pourquoi}</p>}
                </div>
            </div>

            <p className="break-words text-xs text-muted-foreground">
                <span className="font-medium text-foreground">Risque :</span> {contenu.risque.raison}
            </p>

            {contenu.eleves_a_surveiller && contenu.eleves_a_surveiller.length > 0 && (
                <section>
                    <p className="text-xs font-medium text-muted-foreground">Élèves à surveiller</p>
                    <ul className="mt-1.5 divide-y divide-border rounded-lg border border-border">
                        {contenu.eleves_a_surveiller.map((e) => (
                            <li key={e.id} className="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5 p-2.5 text-sm">
                                <Link href={route('center.students.show', e.id)} className="font-medium hover:underline">
                                    {e.nom}
                                </Link>
                                <span className="min-w-0 break-words text-xs text-muted-foreground">{e.raison}</span>
                            </li>
                        ))}
                    </ul>
                </section>
            )}
        </>
    );
}

function VueCentre({ contenu }: { contenu: DuCentre }) {
    return (
        <>
            <div className="grid gap-4 md:grid-cols-2">
                <section>
                    <p className="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-400">
                        <AlertTriangle className="h-3.5 w-3.5" aria-hidden /> Constats
                    </p>
                    <ul className="mt-2 space-y-2">
                        {contenu.constats.map((c, i) => (
                            <li key={i} className="rounded-lg border border-border p-2.5 text-sm">
                                <p className="break-words font-medium">{c.titre}</p>
                                {c.explication && <p className="mt-0.5 break-words text-xs text-muted-foreground">{c.explication}</p>}
                            </li>
                        ))}
                    </ul>
                </section>
                <section>
                    <p className="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">
                        <Lightbulb className="h-3.5 w-3.5" aria-hidden /> Leviers
                    </p>
                    <ul className="mt-2 space-y-2">
                        {contenu.leviers.map((l, i) => (
                            <li key={i} className="rounded-lg border border-emerald-200 bg-emerald-50/50 p-2.5 text-sm dark:border-emerald-900/70 dark:bg-emerald-950/20">
                                <p className="break-words font-medium">{l.titre}</p>
                                {l.action && <p className="mt-0.5 break-words text-xs text-muted-foreground">{l.action}</p>}
                            </li>
                        ))}
                    </ul>
                </section>
            </div>

            <div className="rounded-lg border border-border p-3">
                <p className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                    <Star className="h-3.5 w-3.5" aria-hidden /> Point fort
                </p>
                <p className="mt-1 break-words text-sm">{contenu.point_fort}</p>
            </div>

            {contenu.classes_qui_decrochent.length > 0 && (
                <section>
                    <p className="text-xs font-medium text-muted-foreground">Classes qui décrochent</p>
                    <ul className="mt-1.5 divide-y divide-border rounded-lg border border-border">
                        {contenu.classes_qui_decrochent.map((c) => (
                            <li key={c.id} className="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5 p-2.5 text-sm">
                                <Link href={route('center.classes.show', c.id)} className="font-medium hover:underline">
                                    {c.nom}
                                </Link>
                                <span className="min-w-0 break-words text-xs text-muted-foreground">{c.raison}</span>
                            </li>
                        ))}
                    </ul>
                </section>
            )}
        </>
    );
}
