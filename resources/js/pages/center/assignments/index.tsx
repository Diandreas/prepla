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
import { filtrer, optionsDe, pluriel, regrouper, type Critere } from '@/lib/classement-centre';
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

/** Un devoir echu et pas rendu par tous figure aussi sous « En retard ». */
const CRITERES: Record<'classe' | 'statut', { libelle: string; sans: string; etiquettes: Critere<AssignmentRow> }> = {
    classe: {
        libelle: 'Classe',
        sans: 'Sans classe',
        etiquettes: (a) => (a.classroom ? [{ cle: a.classroom.id, libelle: a.classroom.name }] : []),
    },
    statut: {
        libelle: 'Statut',
        sans: 'Sans statut',
        etiquettes: (a) => [{ cle: a.statut, libelle: STATUTS_DEVOIR[a.statut] }, ...(a.en_retard ? [{ cle: 'en_retard', libelle: 'En retard' }] : [])],
    },
};
const FILTRES = ['classe', 'statut'] as const;
const VUE = ['q', 'groupe', 'tri', ...FILTRES] as const;
const TRIS: { cle: TriDevoirs; libelle: string }[] = [
    { cle: 'echeance', libelle: 'Échéance' },
    { cle: 'recent', libelle: 'Plus récents' },
    { cle: 'progression', libelle: 'Moins avancés' },
];

function Echeance({ a, empile = true }: { a: AssignmentRow; empile?: boolean }) {
    const e = echeance(a);
    return (
        <span className={empile ? 'block whitespace-nowrap' : 'inline-flex items-center gap-1.5 whitespace-nowrap'}>
            <span className={`font-medium ${TON_ECHEANCE[e.ton]} ${empile ? 'block' : ''}`}>{e.texte}</span>
            {e.detail && <span className={`text-xs text-muted-foreground ${empile ? 'block' : ''}`}>{empile ? e.detail : `· ${e.detail}`}</span>}
        </span>
    );
}

function Classe({ a }: { a: AssignmentRow }) {
    if (!a.classroom) return <span className="text-muted-foreground">—</span>;
    return (
        <span className="block min-w-0">
            <span className="block font-medium">{a.classroom.name}</span>
            <span className="mt-1 flex flex-wrap items-center gap-1">
                <BadgeNiveau niveau={a.classroom.level} />
                <BadgeLangue langue={a.classroom.langue} />
            </span>
        </span>
    );
}

function Progression({ a }: { a: AssignmentRow }) {
    const { students, finished, started } = a.progress;
    if (a.statut === 'brouillon') return <span className="text-sm text-muted-foreground">Pas encore publié</span>;
    if (!students) return <span className="text-sm text-muted-foreground">Aucun élève dans la classe</span>;
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
                    ont fini{started > 0 ? ` · ${started} en cours` : ''}
                </>
            }
        />
    );
}

function Statut({ a }: { a: AssignmentRow }) {
    return (
        <span className="flex flex-wrap gap-1">
            <BadgeStatut libelle={STATUTS_DEVOIR[a.statut]} ton={TON_STATUT[a.statut]} />
            {a.en_retard && <BadgeStatut libelle="En retard" ton="danger" />}
        </span>
    );
}

export default function AssignmentsIndex({ assignments, classrooms = [] }: { assignments: AssignmentRow[]; classrooms?: { id: number; name: string }[] }) {
    const { etat, poser, reinitialiser } = useVueDansAdresse(VUE);
    const tri = (TRIS.some((t) => t.cle === etat.tri) ? etat.tri : 'echeance') as TriDevoirs;

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
        [assignments, etat, tri],
    );
    const synthese = useMemo(() => syntheseDevoirs(assignments), [assignments]);
    const groupes = useMemo(
        () => (etat.groupe === 'classe' ? regrouper(filtres, CRITERES.classe.etiquettes, { libelleSans: CRITERES.classe.sans }) : null),
        [filtres, etat.groupe],
    );
    const basculerStatut = (statut: string) => poser('statut', etat.statut === statut ? '' : statut);

    const nouveau = (
        <Button asChild>
            <Link href={route('center.assignments.create')}>
                <Plus className="mr-1.5 h-4 w-4" /> Nouveau devoir
            </Link>
        </Button>
    );

    const columns: Column<AssignmentRow>[] = [
        {
            key: 'title',
            header: 'Devoir',
            cell: (a) => (
                <span className="block min-w-[12rem]">
                    <span className="block font-semibold">{a.title}</span>
                    <span className="mt-0.5 flex items-center gap-1.5 text-xs text-muted-foreground">
                        {pluriel(a.exercises_count, 'exercice')}
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
        { key: 'classroom', header: 'Classe', cell: (a) => <Classe a={a} />, sortValue: (a) => a.classroom?.name ?? '' },
        { key: 'due', header: 'Échéance', cell: (a) => <Echeance a={a} />, sortValue: (a) => a.due_date ?? '9999' },
        { key: 'progress', header: 'Progression', cell: (a) => <Progression a={a} />, sortValue: (a) => tauxFini(a.progress) ?? -1, className: 'w-56' },
        { key: 'statut', header: 'Statut', cell: (a) => <Statut a={a} />, sortValue: (a) => ['publie', 'brouillon', 'termine'].indexOf(a.statut) },
    ];

    const carte = (a: AssignmentRow) => (
        <div className="space-y-3">
            <div className="flex items-start justify-between gap-2">
                <div className="min-w-0">
                    <p className="font-semibold leading-snug">{a.title}</p>
                    <p className="text-xs text-muted-foreground">
                        {a.classroom?.name ?? 'Sans classe'} · {pluriel(a.exercises_count, 'exercice')}
                    </p>
                </div>
                <Statut a={a} />
            </div>
            <div className="flex flex-wrap items-center gap-1.5">
                <BadgeNiveau niveau={a.classroom?.level} />
                <BadgeLangue langue={a.classroom?.langue} />
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
            ? 'Donnez des exercices à une classe et suivez qui a fini.'
            : [
                  pluriel(synthese.total, 'devoir'),
                  `${synthese.enCours} en cours`,
                  synthese.enRetard ? `${synthese.enRetard} en retard` : null,
              ]
                  .filter(Boolean)
                  .join(' · ');

    return (
        <AppLayout>
            <Head title="Devoirs" />
            <div className="mx-auto w-full max-w-6xl space-y-5 p-4 md:p-6">
                <EnTetePage titre="Devoirs" sousTitre={sousTitre} actions={nouveau} />

                {assignments.length === 0 ? (
                    <EtatVide
                        icone={ClipboardList}
                        titre="Aucun devoir pour l'instant"
                        texte={
                            classrooms.length
                                ? 'Choisissez une classe, quelques exercices et une date de remise : les élèves sont prévenus, et vous voyez ici qui a fini.'
                                : "Créez d'abord une classe, puis donnez-lui son premier devoir."
                        }
                        action={
                            classrooms.length ? (
                                nouveau
                            ) : (
                                <Button asChild>
                                    <Link href={route('center.classes.index')}>Créer une classe</Link>
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
                                libelle="En cours"
                                valeur={synthese.enCours}
                                detail={`sur ${pluriel(synthese.total, 'devoir')}`}
                                actif={etat.statut === 'publie'}
                                onClick={() => basculerStatut('publie')}
                            />
                            <CarteKpi
                                icone={CalendarClock}
                                ton="alerte"
                                libelle="À rendre sous 7 jours"
                                valeur={synthese.cetteSemaine}
                                detail="Devoirs publiés"
                            />
                            <CarteKpi
                                icone={AlarmClock}
                                ton={synthese.enRetard ? 'danger' : 'neutre'}
                                libelle="En retard"
                                valeur={synthese.enRetard}
                                detail="Échus, pas rendus par tous"
                                actif={etat.statut === 'en_retard'}
                                onClick={() => basculerStatut('en_retard')}
                            />
                            <CarteKpi
                                icone={synthese.tauxRendu !== null && synthese.tauxRendu >= 70 ? CheckCircle2 : Users}
                                ton="succes"
                                libelle="Taux de rendu"
                                valeur={synthese.tauxRendu === null ? '—' : `${synthese.tauxRendu} %`}
                                detail="Élèves ayant tout fini"
                            />
                        </div>

                        <BarreClassement
                            recherche={etat.q}
                            onRecherche={(v) => poser('q', v)}
                            placeholder="Rechercher un devoir, une classe…"
                            filtres={[
                                {
                                    nom: 'classe',
                                    libelle: 'Classe',
                                    tous: 'Toutes les classes',
                                    options: optionsDe(assignments, CRITERES.classe.etiquettes, { libelleSans: CRITERES.classe.sans }),
                                },
                                {
                                    nom: 'statut',
                                    libelle: 'Statut',
                                    tous: 'Tous les statuts',
                                    // Dans l'ordre de travail, pas l'ordre alphabetique.
                                    options: ['publie', 'en_retard', 'termine', 'brouillon']
                                        .map((cle) => ({
                                            cle,
                                            libelle: cle === 'en_retard' ? 'En retard' : STATUTS_DEVOIR[cle as StatutDevoir],
                                            nombre: assignments.filter((a) => CRITERES.statut.etiquettes(a)?.some((e) => e.cle === cle)).length,
                                        }))
                                        .filter((o) => o.nombre > 0),
                                },
                            ]}
                            valeurs={etat}
                            onFiltre={(nom, v) => poser(nom as (typeof VUE)[number], v)}
                            regroupements={[{ cle: 'classe', libelle: 'Classe' }]}
                            regroupement={etat.groupe === 'classe' ? 'classe' : ''}
                            onRegroupement={(v) => poser('groupe', v)}
                            onReinitialiser={reinitialiser}
                            actions={
                                <label className="flex min-w-0 items-center gap-2 text-sm">
                                    <span className="shrink-0 text-muted-foreground">Trier par</span>
                                    <select
                                        value={tri}
                                        onChange={(e) => poser('tri', e.target.value === 'echeance' ? '' : e.target.value)}
                                        className="h-9 min-w-0 rounded-lg border border-border bg-background px-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                    >
                                        {TRIS.map((t) => (
                                            <option key={t.cle} value={t.cle}>
                                                {t.libelle}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                            }
                        />

                        {filtres.length !== assignments.length && filtres.length > 0 && (
                            <p className="px-1 text-xs text-muted-foreground">
                                {pluriel(filtres.length, 'devoir')} sur {assignments.length}
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
                                                details={[pluriel(g.lignes.length, 'devoir'), pluriel(g.lignes[0]?.progress.students ?? 0, 'élève')]}
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
                                    Aucun devoir ne correspond à ces critères.{' '}
                                    <button type="button" onClick={reinitialiser} className="font-medium text-primary hover:underline">
                                        Tout effacer
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
