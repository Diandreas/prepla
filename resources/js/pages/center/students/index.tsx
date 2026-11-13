import { Head, Link } from '@inertiajs/react';
import { BellRing, CalendarCheck, Download, Flame, GraduationCap, MoonStar, TrendingUp, Trophy, UserCheck, Zap } from 'lucide-react';
import { useMemo } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { type Column } from '@/components/ui/data-table';
import { BarreClassement, Drapeau, EnTeteGroupe, useVueDansAdresse } from '@/components/center/barre-classement';
import { AvatarInitiales, BadgeNiveau, BadgeStatut as Pastille, CarteKpi, EnTetePage, EtatVide, ListeAdaptative, type Ton } from '@/components/center/liste-centre';
import { PastilleRang, Podium } from '@/components/center/podium';
import { ilYALangue } from '@/components/center/statistiques';
import { useT } from '@/lib/i18n/traduire';
import {
    compterStatuts,
    activiteCoherente,
    filtrer,
    optionsDe,
    regrouper,
    SANS,
    STATUTS,
    versCsv,
    type Critere,
    type ModeTri,
    type Statut,
} from '@/lib/classement-centre';
import {
    DISTINCTIONS,
    distinctions as calculerDistinctions,
    PERIODES,
    podium as calculerPodium,
    rangs as calculerRangs,
    SEUIL_EN_FEU,
    type Distinction,
    type Periode,
    type StatsPeriode,
} from '@/lib/podium-centre';
import { dateHeure } from '@/lib/suivi';

interface Langue { cle: string; nom: string; drapeau: string | null }
interface ClasseEleve { id: number; name: string; room: string | null; level: string | null; langue: Langue | null }
interface StudentRow {
    id: number;
    name: string;
    email: string;
    classes: ClasseEleve[];
    niveaux: string[];
    /** Aucun niveau de classe : c'est le niveau declare par l'eleve. */
    niveau_du_profil: boolean;
    langues: Langue[];
    examen_vise: string | null;
    exercices: number;
    score_moyen: number | null;
    derniere_activite: string | null;
    statut: Statut;
    periodes: Record<Periode, StatsPeriode>;
    serie: number;
    jours_actifs: number;
    progression: number | null;
}

const unique = <T,>(valeurs: T[]) => [...new Set(valeurs)];

const CRITERES: Record<string, { libelle: string; sans: string; mode?: ModeTri; etiquettes: Critere<StudentRow> }> = {
    classe: { libelle: 'Classe', sans: 'Sans classe', etiquettes: (s) => s.classes.map((c) => ({ cle: c.id, libelle: c.name })) },
    salle: {
        libelle: 'Salle',
        sans: 'Sans salle',
        etiquettes: (s) => unique(s.classes.map((c) => c.room).filter((r): r is string => !!r)).map((r) => ({ cle: r, libelle: r })),
    },
    langue: { libelle: 'Langue', sans: 'Sans langue', etiquettes: (s) => s.langues.map((l) => ({ cle: l.cle, libelle: l.nom })) },
    niveau: { libelle: 'Niveau', sans: 'Sans niveau', mode: 'niveau', etiquettes: (s) => s.niveaux.map((n) => ({ cle: n, libelle: n })) },
    statut: { libelle: 'Statut', sans: 'Sans statut', etiquettes: (s) => [{ cle: s.statut, libelle: STATUTS[s.statut] }] },
};
const FILTRES = ['classe', 'salle', 'langue', 'niveau', 'statut'] as const;
const REGROUPEMENTS = ['classe', 'salle', 'niveau', 'langue', 'statut'] as const;
const VUE = ['q', 'groupe', 'periode', ...FILTRES] as const;
const TOUS: Record<(typeof FILTRES)[number], string> = {
    classe: 'Toutes les classes',
    salle: 'Toutes les salles',
    langue: 'Toutes les langues',
    niveau: 'Tous les niveaux',
    statut: 'Tous les statuts',
};
/** Les statuts dans un ordre utile : qui relancer d'abord. */
const ORDRE_STATUTS: Statut[] = ['a_relancer', 'jamais', 'actif'];
const TON_STATUT: Record<Statut, Ton> = { actif: 'succes', a_relancer: 'alerte', jamais: 'neutre' };

function BadgeStatut({ statut }: { statut: Statut }) {
    const { t } = useT();
    return <Pastille libelle={t(STATUTS[statut])} ton={TON_STATUT[statut]} />;
}

/** La precision, avec une jauge coloree : verte a 70 % et plus, bleue a 50 %, ambre en dessous. */
function Precision({ valeur }: { valeur: number | null }) {
    if (valeur === null) return <span className="text-muted-foreground">—</span>;
    const couleur = valeur >= 70 ? 'bg-emerald-500' : valeur >= 50 ? 'bg-sky-500' : 'bg-amber-500';
    return (
        <span className="flex min-w-[6.5rem] items-center gap-2">
            <span className="h-2 w-14 overflow-hidden rounded-full bg-muted" aria-hidden>
                <span className={`block h-full rounded-full ${couleur}`} style={{ width: `${Math.min(100, Math.max(0, valeur))}%` }} />
            </span>
            <span className="text-sm font-semibold tabular-nums">{valeur} %</span>
        </span>
    );
}

function Serie({ jours }: { jours: number }) {
    const { t } = useT();
    if (!jours) return <span className="text-muted-foreground">—</span>;
    const feu = jours >= SEUIL_EN_FEU;
    return (
        <span
            className={`inline-flex items-center gap-1 whitespace-nowrap text-sm font-semibold tabular-nums ${feu ? 'text-orange-600 dark:text-orange-400' : ''}`}
            title={Math.abs(jours) > 1 ? t("{n} jours d'affilée", { n: jours }) : t("{n} jour d'affilée", { n: jours })}
        >
            <Flame className={`h-4 w-4 ${feu ? 'fill-orange-500/30' : 'text-muted-foreground'}`} /> {t('{n} j', { n: jours })}
        </span>
    );
}

const STYLE_DISTINCTION: Record<Distinction, { icone: React.ComponentType<{ className?: string }>; classe: string }> = {
    en_feu: { icone: Flame, classe: 'border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-900 dark:bg-orange-950/50 dark:text-orange-300' },
    regulier: { icone: CalendarCheck, classe: 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900 dark:bg-sky-950/50 dark:text-sky-300' },
    progression: { icone: TrendingUp, classe: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-300' },
};

function BadgeDistinction({ cle }: { cle: Distinction }) {
    const { t } = useT();
    const { icone: Icone, classe } = STYLE_DISTINCTION[cle];
    return (
        <span className={`inline-flex items-center gap-1 whitespace-nowrap rounded-full border px-1.5 py-0.5 text-[11px] font-semibold ${classe}`}>
            <Icone className="h-3 w-3" /> {t(DISTINCTIONS[cle])}
        </span>
    );
}

const part = (n: number, total: number, t: ReturnType<typeof useT>['t']) => (total ? t('{p} % des élèves', { p: Math.round((100 * n) / total) }) : undefined);

export default function StudentsIndex({ students, joursAvantRelance = 7 }: { students: StudentRow[]; joursAvantRelance?: number }) {
    const { t, langue } = useT();
    const { etat, poser, reinitialiser } = useVueDansAdresse(VUE);
    // Pluriels : « 1 élève », « 3 élèves » — une phrase par forme, pour l'anglais.
    const nbEleves = (n: number) => (Math.abs(n) > 1 ? t('{n} élèves', { n }) : t('{n} élève', { n }));
    const nbExercices = (n: number) => (Math.abs(n) > 1 ? t('{n} exercices', { n }) : t('{n} exercice', { n }));
    const nbActifs = (n: number) => (Math.abs(n) > 1 ? t('{n} actifs', { n }) : t('{n} actif', { n }));
    // Les libelles fixes (langue, statut, « Sans … ») se traduisent ; les noms de classe, de salle et les niveaux restent tels quels.
    const libelleCritere = (nom: string, e: { cle: string | number; libelle: string }) =>
        nom === 'langue' || nom === 'statut' || e.cle === SANS ? t(e.libelle) : e.libelle;
    const champsRecherche = (s: StudentRow) => [s.name, s.email];
    const periode: Periode = etat.periode in PERIODES ? (etat.periode as Periode) : 'semaine';

    // Les compteurs suivent les autres filtres, mais pas le statut : on voit
    // combien relancer dans la classe choisie, et un clic filtre dessus.
    const sansStatut = useMemo(
        () =>
            filtrer(students, {
                recherche: etat.q,
                champsRecherche,
                criteres: CRITERES,
                filtres: Object.fromEntries(FILTRES.filter((f) => f !== 'statut').map((f) => [f, etat[f]])),
            }),
        [students, etat],
    );
    const filtresBruts = useMemo(
        () => (etat.statut ? filtrer(sansStatut, { criteres: CRITERES, filtres: { statut: etat.statut } }) : sansStatut),
        [sansStatut, etat.statut],
    );
    const compte = compterStatuts(sansStatut);

    // Le classement suit le filtre : le podium d'une classe, d'une langue, d'un niveau.
    const rangs = useMemo(() => calculerRangs(filtresBruts, periode), [filtresBruts, periode]);
    const podium = useMemo(() => calculerPodium(filtresBruts, periode), [filtresBruts, periode]);
    const distinctions = useMemo(() => calculerDistinctions(filtresBruts), [filtresBruts]);
    // Les mieux classes en tete, puis les autres par nom.
    const filtres = useMemo(
        () =>
            [...filtresBruts].sort(
                (a, b) => (rangs[a.id] ?? Infinity) - (rangs[b.id] ?? Infinity) || a.name.localeCompare(b.name, 'fr', { sensitivity: 'base' }),
            ),
        [filtresBruts, rangs],
    );

    const groupeChoisi = (REGROUPEMENTS as readonly string[]).includes(etat.groupe) ? etat.groupe : '';
    const groupes = useMemo(() => {
        if (!groupeChoisi) return null;
        const critere = CRITERES[groupeChoisi];
        const liste = regrouper(filtres, critere.etiquettes, { mode: critere.mode, libelleSans: critere.sans });
        if (groupeChoisi === 'statut') liste.sort((a, b) => ORDRE_STATUTS.indexOf(a.cle as Statut) - ORDRE_STATUTS.indexOf(b.cle as Statut));
        return liste;
    }, [filtres, groupeChoisi]);

    const filtrerStatut = (statut: Statut | '') => poser('statut', etat.statut === statut ? '' : statut);
    const filtreActif = etat.q !== '' || FILTRES.some((f) => etat[f]);
    const enFeu = filtres.filter((s) => s.serie >= SEUIL_EN_FEU).length;
    const quiA = (cle: Distinction) => filtres.find((s) => distinctions[s.id]?.includes(cle));

    function exporter() {
        const entetes = [
            t('Élève'), t('E-mail'), t('Classe(s)'), t('Salle(s)'), t('Langue(s)'), t('Niveau'), t('Examen visé'), t('Exercices faits'), t('Score moyen (%)'),
            t('Rang ({periode})', { periode: t(PERIODES[periode]) }), t('XP ({periode})', { periode: t(PERIODES[periode]) }), t('Série (jours)'), t('Dernière activité'), t('Statut'),
        ];
        const lignes = filtres.map((s) => [
            s.name,
            s.email,
            s.classes.map((c) => c.name).join(', '),
            unique(s.classes.map((c) => c.room).filter(Boolean)).join(', '),
            s.langues.map((l) => t(l.nom)).join(', '),
            s.niveaux.join(', '),
            s.examen_vise ?? '',
            s.exercices,
            s.score_moyen ?? '',
            rangs[s.id] ?? '',
            s.periodes[periode].xp,
            s.serie,
            s.derniere_activite ? dateHeure(s.derniere_activite) : t('Jamais'),
            t(STATUTS[s.statut]),
        ]);
        const fichier = new Blob([versCsv(entetes, lignes)], { type: 'text/csv;charset=utf-8' });
        const lien = document.createElement('a');
        lien.href = URL.createObjectURL(fichier);
        lien.download = `eleves-${new Date().toISOString().slice(0, 10)}.csv`;
        document.body.appendChild(lien);
        lien.click();
        lien.remove();
        URL.revokeObjectURL(lien.href);
    }

    const classesDe = (s: StudentRow, groupeCourant: string | null) => {
        if (!s.classes.length) return <span className="text-muted-foreground">—</span>;
        // Regroupe par classe : l'eleve figure dans chacun de ses groupes, et le dit.
        if (groupeChoisi === 'classe' && groupeCourant) {
            const autres = s.classes.filter((c) => String(c.id) !== groupeCourant);
            return autres.length ? <span className="text-xs text-muted-foreground">{t('Aussi : {classes}', { classes: autres.map((c) => c.name).join(', ') })}</span> : null;
        }
        const salles = unique(s.classes.map((c) => c.room).filter(Boolean));
        return (
            <span className="block">
                <span className="block">{s.classes.map((c) => c.name).join(', ')}</span>
                {salles.length > 0 && <span className="block text-xs text-muted-foreground">{salles.join(', ')}</span>}
            </span>
        );
    };

    const niveauDe = (s: StudentRow) =>
        s.niveaux.length ? (
            <span className="flex flex-wrap items-center gap-1" title={s.niveau_du_profil ? t("Niveau déclaré par l'élève (aucun niveau de classe)") : undefined}>
                {s.niveaux.map((n) => (
                    <BadgeNiveau key={n} niveau={n} />
                ))}
                {s.niveau_du_profil && <span className="text-xs text-muted-foreground">{t('déclaré')}</span>}
            </span>
        ) : (
            <span className="text-muted-foreground">—</span>
        );

    const xpDe = (s: StudentRow) => (
        <span className="block whitespace-nowrap">
            <span className="inline-flex items-center gap-1 font-bold tabular-nums text-amber-600 dark:text-amber-400">
                <Zap className="h-3.5 w-3.5 fill-current" /> {s.periodes[periode].xp}
            </span>
            <span className="block text-xs text-muted-foreground">{nbExercices(s.periodes[periode].exercices)}</span>
        </span>
    );

    // La date relative dans la langue de l'interface ; « il y a plus de N jours » traduit aussi.
    const activite = (iso: string, statut: Statut) => {
        const texte = activiteCoherente(iso, statut, joursAvantRelance, (d: string | null) => ilYALangue(d, langue));
        return texte === `il y a plus de ${joursAvantRelance} jours` ? t('il y a plus de {n} jours', { n: joursAvantRelance }) : texte;
    };

    const activiteDe = (s: StudentRow) => (
        <span className="whitespace-nowrap" title={dateHeure(s.derniere_activite)}>
            {s.derniere_activite ? activite(s.derniere_activite, s.statut) : t('Jamais')}
        </span>
    );

    const columns = (groupeCourant: string | null): Column<StudentRow>[] => [
        { key: 'rang', header: t('Rang'), cell: (s) => <PastilleRang rang={rangs[s.id]} />, sortValue: (s) => rangs[s.id] ?? 1e9, className: 'w-14' },
        {
            key: 'name',
            header: t('Élève'),
            cell: (s) => (
                <span className="flex min-w-[13rem] items-center gap-3">
                    <AvatarInitiales nom={s.name} />
                    <span className="min-w-0">
                        <span className="block font-semibold">{s.name}</span>
                        <span className="block truncate text-xs text-muted-foreground">{s.email}</span>
                        {distinctions[s.id] && (
                            <span className="mt-1 flex flex-wrap gap-1">
                                {distinctions[s.id].map((d) => (
                                    <BadgeDistinction key={d} cle={d} />
                                ))}
                            </span>
                        )}
                    </span>
                </span>
            ),
            sortValue: (s) => s.name,
        },
        { key: 'classes', header: t('Classe(s)'), cell: (s) => classesDe(s, groupeCourant), sortValue: (s) => s.classes.map((c) => c.name).join(', ') },
        {
            key: 'langues',
            header: t('Langue · niveau'),
            cell: (s) => (
                <span className="block space-y-1">
                    {s.langues.length > 0 && (
                        <span className="flex flex-wrap gap-x-2 gap-y-0.5">
                            {s.langues.map((l) => (
                                <span key={l.cle} className="inline-flex items-center gap-1.5 whitespace-nowrap text-sm">
                                    <Drapeau emoji={l.drapeau} /> {t(l.nom)}
                                </span>
                            ))}
                        </span>
                    )}
                    {niveauDe(s)}
                </span>
            ),
            sortValue: (s) => `${s.langues.map((l) => l.nom).join(', ')} ${s.niveaux.join(',')}`,
        },
        { key: 'xp', header: 'XP', cell: xpDe, sortValue: (s) => -s.periodes[periode].xp },
        { key: 'serie', header: t('Série'), cell: (s) => <Serie jours={s.serie} />, sortValue: (s) => -s.serie },
        { key: 'precision', header: t('Précision'), cell: (s) => <Precision valeur={s.periodes[periode].precision} />, sortValue: (s) => -(s.periodes[periode].precision ?? -1) },
        { key: 'derniere_activite', header: t('Dernière activité'), cell: activiteDe, sortValue: (s) => s.derniere_activite ?? '' },
        { key: 'statut', header: t('Statut'), cell: (s) => <BadgeStatut statut={s.statut} />, sortValue: (s) => ORDRE_STATUTS.indexOf(s.statut) },
    ];

    const carte = (groupeCourant: string | null) => (s: StudentRow) => (
        <div className="space-y-3">
            <div className="flex items-start gap-3">
                <span className="relative">
                    <AvatarInitiales nom={s.name} />
                    {rangs[s.id] && rangs[s.id] <= 3 && (
                        <span className="absolute -bottom-1 -right-2 scale-75">
                            <PastilleRang rang={rangs[s.id]} />
                        </span>
                    )}
                </span>
                <div className="min-w-0 flex-1">
                    <div className="flex items-start justify-between gap-2">
                        <p className="font-semibold leading-snug">
                            {rangs[s.id] && rangs[s.id] > 3 && <span className="mr-1 text-muted-foreground tabular-nums">#{rangs[s.id]}</span>}
                            {s.name}
                        </p>
                        <BadgeStatut statut={s.statut} />
                    </div>
                    <div className="mt-0.5 text-xs text-muted-foreground">{classesDe(s, groupeCourant)}</div>
                </div>
            </div>
            {distinctions[s.id] && (
                <div className="flex flex-wrap gap-1">
                    {distinctions[s.id].map((d) => (
                        <BadgeDistinction key={d} cle={d} />
                    ))}
                </div>
            )}
            <div className="grid grid-cols-3 gap-2 rounded-lg bg-muted/40 p-2.5">
                <div>
                    <p className="text-[11px] uppercase tracking-wide text-muted-foreground">XP</p>
                    {xpDe(s)}
                </div>
                <div>
                    <p className="text-[11px] uppercase tracking-wide text-muted-foreground">{t('Série')}</p>
                    <Serie jours={s.serie} />
                </div>
                <div className="min-w-0">
                    <p className="text-[11px] uppercase tracking-wide text-muted-foreground">{t('Précision')}</p>
                    <Precision valeur={s.periodes[periode].precision} />
                </div>
            </div>
            <div className="flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground">
                {niveauDe(s)}
                <span>{t('Dernière activité :')} {activiteDe(s)}</span>
            </div>
        </div>
    );

    const tableau = (lignes: StudentRow[], vide: React.ReactNode, groupeCourant: string | null = null) => (
        <ListeAdaptative
            columns={columns(groupeCourant)}
            rows={lignes}
            rowKey={(s) => s.id}
            lien={(s) => route('center.students.show', s.id)}
            carte={carte(groupeCourant)}
            empty={vide}
        />
    );

    const videFiltre = (
        <>
            {t('Aucun élève ne correspond à ces critères.')}{' '}
            <button type="button" onClick={reinitialiser} className="font-medium text-primary hover:underline">
                {t('Tout effacer')}
            </button>
        </>
    );

    const sousTitre = students.length
        ? [nbEleves(compte.total), nbActifs(compte.actif), compte.a_relancer ? t('{n} à relancer', { n: compte.a_relancer }) : null].filter(Boolean).join(' · ')
        : undefined;

    const regulier = quiA('regulier');
    const progres = quiA('progression');

    return (
        <AppLayout>
            <Head title={t('Élèves')} />
            <div className="mx-auto w-full max-w-6xl space-y-5 p-4 md:p-6">
                <EnTetePage
                    titre={t('Élèves')}
                    sousTitre={sousTitre}
                    actions={
                        students.length > 0 && (
                            <Button type="button" variant="outline" onClick={exporter} disabled={filtres.length === 0}>
                                <Download className="mr-1.5 h-4 w-4" /> {t('Exporter (CSV)')}
                            </Button>
                        )
                    }
                />

                {students.length === 0 ? (
                    <EtatVide
                        icone={GraduationCap}
                        titre={t("Aucun élève pour l'instant")}
                        texte={t("Partagez le code d'invitation d'une classe : vos élèves s'inscrivent en quelques secondes et apparaissent ici, avec leur suivi et leur classement.")}
                        action={
                            <Button asChild>
                                <Link href={route('center.classes.index')}>{t('Voir les codes de mes classes')}</Link>
                            </Button>
                        }
                    />
                ) : (
                    <>
                        <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                            <CarteKpi icone={GraduationCap} ton="primaire" libelle={t('Élèves')} valeur={compte.total} actif={etat.statut === ''} onClick={() => poser('statut', '')} />
                            <CarteKpi
                                icone={UserCheck}
                                ton="succes"
                                libelle={t('Actifs')}
                                valeur={compte.actif}
                                detail={part(compte.actif, compte.total, t)}
                                actif={etat.statut === 'actif'}
                                onClick={() => filtrerStatut('actif')}
                            />
                            <CarteKpi
                                icone={BellRing}
                                ton={compte.a_relancer ? 'alerte' : 'neutre'}
                                libelle={t('À relancer')}
                                valeur={compte.a_relancer}
                                detail={t('Rien depuis {n} jours', { n: joursAvantRelance })}
                                actif={etat.statut === 'a_relancer'}
                                onClick={() => filtrerStatut('a_relancer')}
                            />
                            <CarteKpi
                                icone={MoonStar}
                                libelle={t('Jamais venus')}
                                valeur={compte.jamais}
                                detail={part(compte.jamais, compte.total, t)}
                                actif={etat.statut === 'jamais'}
                                onClick={() => filtrerStatut('jamais')}
                            />
                        </div>

                        <section className="overflow-hidden rounded-2xl border border-border bg-card">
                            <div className="flex flex-col gap-3 border-b border-border bg-gradient-to-r from-amber-500/10 via-transparent to-sky-500/10 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                <div className="flex items-center gap-2.5">
                                    <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500/15 text-amber-600 dark:text-amber-400">
                                        <Trophy className="h-5 w-5" />
                                    </span>
                                    <div>
                                        <h2 className="font-bold leading-tight">{t('Classement')}</h2>
                                        <p className="text-xs text-muted-foreground">
                                            {t('Par XP gagnés')} · {filtreActif || etat.statut ? t('{eleves} du filtre actif', { eleves: nbEleves(filtres.length) }) : t('tout le centre')}
                                        </p>
                                    </div>
                                </div>
                                <div role="radiogroup" aria-label={t('Période du classement')} className="inline-flex rounded-lg border border-border bg-background p-0.5">
                                    {(Object.keys(PERIODES) as Periode[]).map((p) => (
                                        <button
                                            key={p}
                                            type="button"
                                            role="radio"
                                            aria-checked={periode === p}
                                            onClick={() => poser('periode', p === 'semaine' ? '' : p)}
                                            className={`rounded-md px-2.5 py-1.5 text-xs font-semibold transition-colors sm:px-3 sm:text-sm ${
                                                periode === p ? 'bg-primary text-primary-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground'
                                            }`}
                                        >
                                            {t(PERIODES[p])}
                                        </button>
                                    ))}
                                </div>
                            </div>
                            <div className="grid gap-4 p-4 lg:grid-cols-[1fr_17rem] lg:items-end">
                                <Podium
                                    marches={podium.map(({ eleve, rang, xp }) => ({
                                        id: eleve.id,
                                        nom: eleve.name,
                                        rang,
                                        valeur: t('{n} XP', { n: xp }),
                                        detail: [nbExercices(eleve.periodes[periode].exercices), eleve.periodes[periode].precision !== null ? `${eleve.periodes[periode].precision} %` : null]
                                            .filter(Boolean)
                                            .join(' · '),
                                        serie: eleve.serie,
                                        lien: route('center.students.show', eleve.id),
                                    }))}
                                    vide={
                                        periode === 'semaine'
                                            ? t("Personne n'a encore gagné d'XP cette semaine. Le podium se remplit dès les premiers exercices.")
                                            : t("Personne n'a encore gagné d'XP sur cette période.")
                                    }
                                />
                                <ul className="space-y-2 text-sm">
                                    <li className="flex items-center gap-2.5 rounded-lg border border-border p-2.5">
                                        <Flame className="h-5 w-5 shrink-0 text-orange-500" />
                                        <span className="min-w-0">
                                            <span className="block font-semibold">{enFeu ? t('{eleves} en feu', { eleves: nbEleves(enFeu) }) : t('Personne en feu')}</span>
                                            <span className="block text-xs text-muted-foreground">{t('Série de {n} jours ou plus', { n: SEUIL_EN_FEU })}</span>
                                        </span>
                                    </li>
                                    <li className="flex items-center gap-2.5 rounded-lg border border-border p-2.5">
                                        <CalendarCheck className="h-5 w-5 shrink-0 text-sky-500" />
                                        <span className="min-w-0">
                                            <span className="block font-semibold">{regulier ? regulier.name : '—'}</span>
                                            <span className="block text-xs text-muted-foreground">
                                                {t('Plus régulier')}
                                                {regulier
                                                    ? ` · ${Math.abs(regulier.jours_actifs) > 1 ? t('{n} jours actifs sur 30', { n: regulier.jours_actifs }) : t('{n} jour actif sur 30', { n: regulier.jours_actifs })}`
                                                    : ''}
                                            </span>
                                        </span>
                                    </li>
                                    <li className="flex items-center gap-2.5 rounded-lg border border-border p-2.5">
                                        <TrendingUp className="h-5 w-5 shrink-0 text-emerald-500" />
                                        <span className="min-w-0">
                                            <span className="block font-semibold">{progres ? progres.name : '—'}</span>
                                            <span className="block text-xs text-muted-foreground">
                                                {t('Meilleure progression')}
                                                {progres ? ` · ${t('+{n} pts de précision en 14 jours', { n: progres.progression })}` : ''}
                                            </span>
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </section>

                        <BarreClassement
                            recherche={etat.q}
                            onRecherche={(v) => poser('q', v)}
                            placeholder={t('Rechercher un nom ou un e-mail…')}
                            filtres={FILTRES.map((nom) => ({
                                nom,
                                libelle: t(CRITERES[nom].libelle),
                                tous: t(TOUS[nom]),
                                options: optionsDe(students, CRITERES[nom].etiquettes, { mode: CRITERES[nom].mode, libelleSans: CRITERES[nom].sans }).map((o) => ({
                                    ...o,
                                    libelle: libelleCritere(nom, o),
                                })),
                            }))}
                            valeurs={etat}
                            onFiltre={(nom, v) => poser(nom as (typeof VUE)[number], v)}
                            regroupements={REGROUPEMENTS.map((cle) => ({ cle, libelle: t(CRITERES[cle].libelle) }))}
                            regroupement={groupeChoisi}
                            onRegroupement={(v) => poser('groupe', v)}
                            onReinitialiser={reinitialiser}
                        />

                        {filtres.length !== students.length && filtres.length > 0 && (
                            <p className="px-1 text-xs text-muted-foreground">
                                {t("{eleves} sur {total} · l'export reprend cette liste", { eleves: nbEleves(filtres.length), total: students.length })}
                            </p>
                        )}

                        {groupes && filtres.length > 0 ? (
                            <div className="space-y-6">
                                {['classe', 'salle', 'langue', 'niveau'].includes(groupeChoisi) && (
                                    <p className="px-1 text-xs text-muted-foreground">
                                        {t('Un élève inscrit dans plusieurs classes apparaît dans chacun de ses groupes.')}
                                    </p>
                                )}
                                {groupes.map((g) => {
                                    const c = compterStatuts(g.lignes);
                                    const langue = groupeChoisi === 'langue' ? g.lignes[0]?.langues.find((l) => l.cle === g.cle) : undefined;
                                    const details = [nbEleves(g.lignes.length)];
                                    if (groupeChoisi !== 'statut') {
                                        if (c.actif) details.push(nbActifs(c.actif));
                                        if (c.a_relancer) details.push(t('{n} à relancer', { n: c.a_relancer }));
                                        if (c.jamais) details.push(c.jamais > 1 ? t('{n} jamais venus', { n: c.jamais }) : t('{n} jamais venu', { n: c.jamais }));
                                    }
                                    return (
                                        <section key={g.cle} className="space-y-2">
                                            <EnTeteGroupe titre={libelleCritere(groupeChoisi, g)} icone={langue ? <Drapeau emoji={langue.drapeau} /> : undefined} details={details} />
                                            {tableau(g.lignes, null, g.cle)}
                                        </section>
                                    );
                                })}
                            </div>
                        ) : (
                            tableau(filtres, videFiltre)
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
