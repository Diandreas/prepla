import { Head, Link } from '@inertiajs/react';
import { AlarmClock, CalendarClock, CheckCircle2, ClipboardList, Plus, Users } from 'lucide-react';
import { useMemo } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { type Column } from '@/components/ui/data-table';
import { BarreClassement, EnTeteGroupe, useVueDansAdresse } from '@/components/center/barre-classement';
import {
    AvatarInitiales,
    BadgeLangue,
    BadgeNiveau,
    BadgeStatut,
    BarreProgression,
    CarteKpi,
    EnTetePage,
    EtatVide,
    ListeAdaptative,
    type Ton,
} from '@/components/center/liste-centre';
import { filtrer, optionsDe, regrouper, type Critere } from '@/lib/classement-centre';
import { useT } from '@/lib/i18n/traduire';
import { echeance, STATUTS_DEVOIR, syntheseDevoirs, tauxFini, trierDevoirs, type StatutDevoir, type TonEcheance, type TriDevoirs } from '@/lib/devoirs-centre';

interface Langue { cle: string; nom: string; drapeau: string | null }
interface AssignmentRow {
    id: number;
    title: string;
    classroom: { id: number; name: string; level: string | null; langue: Langue | null } | null;
    creator: string | null;
    items_count: number;
    exercises_count: number;
    due_at: string | null;
    due_date: string | null;
    days_left: number | null;
    overdue: boolean;
    published: boolean;
    created_at: string | null;
    statut: StatutDevoir;
    en_retard: boolean;
    progress: { students: number; finished: number; started: number };
}

const TON_STATUT: Record<StatutDevoir, Ton> = { brouillon: 'neutre', publie: 'primaire', termine: 'succes' };
const TON_ECHEANCE: Record<TonEcheance, string> = {
    aucune: 'text-muted-foreground',
    loin: 'text-foreground',
    proche: 'text-amber-700 dark:text-amber-400',
    jour: 'text-amber-700 dark:text-amber-400',
    retard: 'text-rose-700 dark:text-rose-400',
    passee: 'text-muted-foreground',
};

type T = ReturnType<typeof useT>['t'];

/** « 1 devoir », « 3 devoirs » : la phrase entiere passe par t, l'anglais choisit son ordre. */
function compte(t: T, n: number, singulier: string, plurielFr: string) {
    return t(Math.abs(n) > 1 ? plurielFr : singulier, { n });
}

/** Un devoir echu et pas rendu par tous figure aussi sous « En retard ». */
function criteres(t: T): Record<'classe' | 'statut', { libelle: string; sans: string; etiquettes: Critere<AssignmentRow> }> {
    return {
        classe: {
            libelle: t('Classe'),
            sans: t('Sans classe'),
            etiquettes: (a) => (a.classroom ? [{ cle: a.classroom.id, libelle: a.classroom.name }] : []),
        },
        statut: {
            libelle: t('Statut'),
            sans: t('Sans statut'),
            etiquettes: (a) => [{ cle: a.statut, libelle: t(STATUTS_DEVOIR[a.statut]) }, ...(a.en_retard ? [{ cle: 'en_retard', libelle: t('En retard') }] : [])],
        },
    };
}
const FILTRES = ['classe', 'statut'] as const;
const VUE = ['q', 'groupe', 'tri', ...FILTRES] as const;
const TRIS: { cle: TriDevoirs; libelle: string }[] = [
    { cle: 'echeance', libelle: 'Échéance' },
    { cle: 'recent', libelle: 'Plus récents' },
    { cle: 'progression', libelle: 'Moins avancés' },
];

/**
 * L'echeance lisible (lib/devoirs-centre), dans la langue de l'interface. En
 * francais, exactement celle de la bibliotheque ; en anglais, les memes regles
 * avec les phrases traduites et la date au format local.
 */
function echeanceTraduite(a: AssignmentRow, t: T, langue: string, locale: string): ReturnType<typeof echeance> {
    const e = echeance(a);
    if (langue !== 'en') return e;
    const { due_date: date, days_left: jours, overdue, en_retard: retard } = a;
    if (!date || jours === null || jours === undefined) return { ...e, texte: t('Sans échéance') };
    const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(date);
    const detail = m
        ? new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3])).toLocaleDateString(locale, {
              day: 'numeric',
              month: 'short',
              ...(Number(m[1]) === new Date().getFullYear() ? {} : { year: 'numeric' }),
          })
        : '';

    if (overdue) {
        const depuis = Math.abs(jours);
        const ecart = depuis === 0 ? t("aujourd'hui") : depuis === 1 ? t('hier') : t('il y a {n} jours', { n: depuis });
        return retard
            ? { texte: t('En retard'), detail: t('échu {ecart}', { ecart }), ton: e.ton }
            : { texte: depuis <= 1 ? t('Échu {ecart}', { ecart }) : t('Échu le {date}', { date: detail }), detail: depuis <= 1 ? detail : '', ton: e.ton };
    }
    if (jours <= 0) return { texte: t("Aujourd'hui"), detail, ton: e.ton };
    if (jours === 1) return { texte: t('Demain'), detail, ton: e.ton };
    return { texte: t('Dans {n} jours', { n: jours }), detail, ton: e.ton };
}

function Echeance({ a, empile = true }: { a: AssignmentRow; empile?: boolean }) {
    const { t, langue, locale } = useT();
    const e = echeanceTraduite(a, t, langue, locale);
    return (
        <span className={empile ? 'block whitespace-nowrap' : 'inline-flex items-center gap-1.5 whitespace-nowrap'}>
            <span className={`font-medium ${TON_ECHEANCE[e.ton]} ${empile ? 'block' : ''}`}>{e.texte}</span>
            {e.detail && <span className={`text-xs text-muted-foreground ${empile ? 'block' : ''}`}>{empile ? e.detail : `· ${e.detail}`}</span>}
        </span>
    );
}

function Classe({ a }: { a: AssignmentRow }) {
    const { t } = useT();
    if (!a.classroom) return <span className="text-muted-foreground">—</span>;
    return (
        <span className="block min-w-0">
            <span className="block font-medium">{a.classroom.name}</span>
            <span className="mt-1 flex flex-wrap items-center gap-1">
                <BadgeNiveau niveau={a.classroom.level} />
                <BadgeLangue langue={a.classroom.langue && { ...a.classroom.langue, nom: t(a.classroom.langue.nom) }} />
            </span>
        </span>
    );
}

function Progression({ a }: { a: AssignmentRow }) {
    const { t } = useT();
    const { students, finished, started } = a.progress;
    if (a.statut === 'brouillon') return <span className="text-sm text-muted-foreground">{t('Pas encore publié')}</span>;
    if (!students) return <span className="text-sm text-muted-foreground">{t('Aucun élève dans la classe')}</span>;
    return (
        <BarreProgression
            finis={finished}
            commences={started}
            total={students}
            legende={
                <>
                    <span className="font-semibold text-foreground tabular-nums">
                        {finished}/{students}
                    </span>{' '}
                    {t('ont fini')}{started > 0 ? ` · ${t('{n} en cours', { n: started })}` : ''}
                </>
            }
        />
    );
}

function Statut({ a }: { a: AssignmentRow }) {
    const { t } = useT();
    return (
        <span className="flex flex-wrap gap-1">
            <BadgeStatut libelle={t(STATUTS_DEVOIR[a.statut])} ton={TON_STATUT[a.statut]} />
            {a.en_retard && <BadgeStatut libelle={t('En retard')} ton="danger" />}
        </span>
    );
}

export default function AssignmentsIndex({ assignments, classrooms = [] }: { assignments: AssignmentRow[]; classrooms?: { id: number; name: string }[] }) {
    const { t } = useT();
    const CRITERES = useMemo(() => criteres(t), [t]);
    const { etat, poser, reinitialiser } = useVueDansAdresse(VUE);
    const tri = (TRIS.some((o) => o.cle === etat.tri) ? etat.tri : 'echeance') as TriDevoirs;

    const filtres = useMemo(
        () =>
            trierDevoirs(
                filtrer(assignments, {
                    recherche: etat.q,
                    champsRecherche: (a) => [a.title, a.classroom?.name, a.creator, a.classroom?.level],
                    criteres: CRITERES,
                    filtres: { classe: etat.classe, statut: etat.statut },
                }),
                tri,
            ),
        [assignments, etat, tri, CRITERES],
    );
    const synthese = useMemo(() => syntheseDevoirs(assignments), [assignments]);
    const groupes = useMemo(
        () => (etat.groupe === 'classe' ? regrouper(filtres, CRITERES.classe.etiquettes, { libelleSans: CRITERES.classe.sans }) : null),
        [filtres, etat.groupe, CRITERES],
    );
    const basculerStatut = (statut: string) => poser('statut', etat.statut === statut ? '' : statut);

    const nouveau = (
        <Button asChild>
            <Link href={route('center.assignments.create')}>
                <Plus className="mr-1.5 h-4 w-4" /> {t('Nouveau devoir')}
            </Link>
        </Button>
    );

    const columns: Column<AssignmentRow>[] = [
        {
            key: 'title',
            header: t('Devoir'),
            cell: (a) => (
                <span className="block min-w-[12rem]">
                    <span className="block font-semibold">{a.title}</span>
                    <span className="mt-0.5 flex items-center gap-1.5 text-xs text-muted-foreground">
                        {compte(t, a.exercises_count, '{n} exercice', '{n} exercices')}
                        {a.creator && (
                            <>
                                <span aria-hidden>·</span>
                                <AvatarInitiales nom={a.creator} taille="sm" className="!h-5 !w-5 !text-[9px]" />
                                <span className="truncate">{a.creator}</span>
                            </>
                        )}
                    </span>
                </span>
            ),
        },
        { key: 'classroom', header: t('Classe'), cell: (a) => <Classe a={a} />, sortValue: (a) => a.classroom?.name ?? '' },
        { key: 'due', header: t('Échéance'), cell: (a) => <Echeance a={a} />, sortValue: (a) => a.due_date ?? '9999' },
        { key: 'progress', header: t('Progression'), cell: (a) => <Progression a={a} />, sortValue: (a) => tauxFini(a.progress) ?? -1, className: 'w-56' },
        { key: 'statut', header: t('Statut'), cell: (a) => <Statut a={a} />, sortValue: (a) => ['publie', 'brouillon', 'termine'].indexOf(a.statut) },
    ];

    const carte = (a: AssignmentRow) => (
        <div className="space-y-3">
            <div className="flex items-start justify-between gap-2">
                <div className="min-w-0">
                    <p className="font-semibold leading-snug">{a.title}</p>
                    <p className="text-xs text-muted-foreground">
                        {a.classroom?.name ?? t('Sans classe')} · {compte(t, a.exercises_count, '{n} exercice', '{n} exercices')}
                    </p>
                </div>
                <Statut a={a} />
            </div>
            <div className="flex flex-wrap items-center gap-1.5">
                <BadgeNiveau niveau={a.classroom?.level} />
                <BadgeLangue langue={a.classroom?.langue && { ...a.classroom.langue, nom: t(a.classroom.langue.nom) }} />
                <span className="ml-auto text-sm">
                    <Echeance a={a} empile={false} />
                </span>
            </div>
            <Progression a={a} />
        </div>
    );

    const liste = (lignes: AssignmentRow[], vide: React.ReactNode) => (
        <ListeAdaptative columns={columns} rows={lignes} rowKey={(a) => a.id} lien={(a) => route('center.assignments.show', a.id)} carte={carte} empty={vide} />
    );

    const sousTitre =
        assignments.length === 0
            ? t('Donnez des exercices à une classe et suivez qui a fini.')
            : [
                  compte(t, synthese.total, '{n} devoir', '{n} devoirs'),
                  t('{n} en cours', { n: synthese.enCours }),
                  synthese.enRetard ? t('{n} en retard', { n: synthese.enRetard }) : null,
              ]
                  .filter(Boolean)
                  .join(' · ');

    return (
        <AppLayout>
            <Head title={t('Devoirs')} />
            <div className="mx-auto w-full max-w-6xl space-y-5 p-4 md:p-6">
                <EnTetePage titre={t('Devoirs')} sousTitre={sousTitre} actions={nouveau} />

                {assignments.length === 0 ? (
                    <EtatVide
                        icone={ClipboardList}
                        titre={t("Aucun devoir pour l'instant")}
                        texte={
                            classrooms.length
                                ? t('Choisissez une classe, quelques exercices et une date de remise : les élèves sont prévenus, et vous voyez ici qui a fini.')
                                : t("Créez d'abord une classe, puis donnez-lui son premier devoir.")
                        }
                        action={
                            classrooms.length ? (
                                nouveau
                            ) : (
                                <Button asChild>
                                    <Link href={route('center.classes.index')}>{t('Créer une classe')}</Link>
                                </Button>
                            )
                        }
                    />
                ) : (
                    <>
                        <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                            <CarteKpi
                                icone={ClipboardList}
                                ton="primaire"
                                libelle={t('En cours')}
                                valeur={synthese.enCours}
                                detail={compte(t, synthese.total, 'sur {n} devoir', 'sur {n} devoirs')}
                                actif={etat.statut === 'publie'}
                                onClick={() => basculerStatut('publie')}
                            />
                            <CarteKpi
                                icone={CalendarClock}
                                ton="alerte"
                                libelle={t('À rendre sous 7 jours')}
                                valeur={synthese.cetteSemaine}
                                detail={t('Devoirs publiés')}
                            />
                            <CarteKpi
                                icone={AlarmClock}
                                ton={synthese.enRetard ? 'danger' : 'neutre'}
                                libelle={t('En retard')}
                                valeur={synthese.enRetard}
                                detail={t('Échus, pas rendus par tous')}
                                actif={etat.statut === 'en_retard'}
                                onClick={() => basculerStatut('en_retard')}
                            />
                            <CarteKpi
                                icone={synthese.tauxRendu !== null && synthese.tauxRendu >= 70 ? CheckCircle2 : Users}
                                ton="succes"
                                libelle={t('Taux de rendu')}
                                valeur={synthese.tauxRendu === null ? '—' : t('{n} %', { n: synthese.tauxRendu })}
                                detail={t('Élèves ayant tout fini')}
                            />
                        </div>

                        <BarreClassement
                            recherche={etat.q}
                            onRecherche={(v) => poser('q', v)}
                            placeholder={t('Rechercher un devoir, une classe…')}
                            filtres={[
                                {
                                    nom: 'classe',
                                    libelle: t('Classe'),
                                    tous: t('Toutes les classes'),
                                    options: optionsDe(assignments, CRITERES.classe.etiquettes, { libelleSans: CRITERES.classe.sans }),
                                },
                                {
                                    nom: 'statut',
                                    libelle: t('Statut'),
                                    tous: t('Tous les statuts'),
                                    // Dans l'ordre de travail, pas l'ordre alphabetique.
                                    options: ['publie', 'en_retard', 'termine', 'brouillon']
                                        .map((cle) => ({
                                            cle,
                                            libelle: cle === 'en_retard' ? t('En retard') : t(STATUTS_DEVOIR[cle as StatutDevoir]),
                                            nombre: assignments.filter((a) => CRITERES.statut.etiquettes(a)?.some((e) => e.cle === cle)).length,
                                        }))
                                        .filter((o) => o.nombre > 0),
                                },
                            ]}
                            valeurs={etat}
                            onFiltre={(nom, v) => poser(nom as (typeof VUE)[number], v)}
                            regroupements={[{ cle: 'classe', libelle: t('Classe') }]}
                            regroupement={etat.groupe === 'classe' ? 'classe' : ''}
                            onRegroupement={(v) => poser('groupe', v)}
                            onReinitialiser={reinitialiser}
                            actions={
                                <label className="flex min-w-0 items-center gap-2 text-sm">
                                    <span className="shrink-0 text-muted-foreground">{t('Trier par')}</span>
                                    <select
                                        value={tri}
                                        onChange={(e) => poser('tri', e.target.value === 'echeance' ? '' : e.target.value)}
                                        className="h-9 min-w-0 rounded-lg border border-border bg-background px-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                    >
                                        {TRIS.map((o) => (
                                            <option key={o.cle} value={o.cle}>
                                                {t(o.libelle)}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                            }
                        />

                        {filtres.length !== assignments.length && filtres.length > 0 && (
                            <p className="px-1 text-xs text-muted-foreground">
                                {t(Math.abs(filtres.length) > 1 ? '{n} devoirs sur {total}' : '{n} devoir sur {total}', { n: filtres.length, total: assignments.length })}
                            </p>
                        )}

                        {groupes && filtres.length > 0 ? (
                            <div className="space-y-6">
                                {groupes.map((g) => {
                                    const premiere = g.lignes[0]?.classroom;
                                    return (
                                        <section key={g.cle} className="space-y-2">
                                            <EnTeteGroupe
                                                titre={g.libelle}
                                                icone={premiere?.level ? <BadgeNiveau niveau={premiere.level} /> : undefined}
                                                details={[compte(t, g.lignes.length, '{n} devoir', '{n} devoirs'), compte(t, g.lignes[0]?.progress.students ?? 0, '{n} élève', '{n} élèves')]}
                                            />
                                            {liste(g.lignes, null)}
                                        </section>
                                    );
                                })}
                            </div>
                        ) : (
                            liste(
                                filtres,
                                <>
                                    {t('Aucun devoir ne correspond à ces critères.')}{' '}
                                    <button type="button" onClick={reinitialiser} className="font-medium text-primary hover:underline">
                                        {t('Tout effacer')}
                                    </button>
                                </>,
                            )
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
