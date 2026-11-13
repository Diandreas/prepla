import { Head, useForm } from '@inertiajs/react';
import { Armchair, Check, Clock, Copy, DoorOpen, Plus, School, UserX, Users, X } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { type Column } from '@/components/ui/data-table';
import { BarreClassement, Drapeau, EnTeteGroupe, useVueDansAdresse } from '@/components/center/barre-classement';
import { BadgeLangue, BadgeNiveau, CarteKpi, EnTetePage, EtatVide, ListeAdaptative, PileAvatars } from '@/components/center/liste-centre';
import { elevesDistincts, filtrer, optionsDe, regrouper, SANS, type Critere, type ModeTri } from '@/lib/classement-centre';
import { useMemo, useState } from 'react';
import { useT } from '@/lib/i18n/traduire';

interface Langue { cle: string; nom: string; drapeau: string | null }
interface ClassroomRow {
    id: number;
    name: string;
    room: string | null;
    schedule: string | null;
    level: string | null;
    exam: string | null;
    langue: Langue | null;
    teachers: { id: number; name: string }[];
    invite_code: string;
    students_count: number;
    student_ids: number[];
}
interface Exam { id: number; name: string }

type T = ReturnType<typeof useT>['t'];

/** « 1 classe », « 3 classes » : la phrase entiere passe par t, l'anglais choisit son ordre. */
function compte(t: T, n: number, singulier: string, plurielFr: string) {
    return t(Math.abs(n) > 1 ? plurielFr : singulier, { n });
}

/** Ce qu'on peut filtrer ou regrouper, et comment le lire sur une classe. */
function criteres(t: T): Record<string, { libelle: string; sans: string; mode?: ModeTri; etiquettes: Critere<ClassroomRow> }> {
    return {
        langue: { libelle: t('Langue'), sans: t('Sans langue'), etiquettes: (c) => (c.langue ? [{ cle: c.langue.cle, libelle: t(c.langue.nom) }] : []) },
        niveau: { libelle: t('Niveau'), sans: t('Sans niveau'), mode: 'niveau', etiquettes: (c) => (c.level ? [{ cle: c.level, libelle: c.level }] : []) },
        salle: { libelle: t('Salle'), sans: t('Sans salle'), etiquettes: (c) => (c.room ? [{ cle: c.room, libelle: c.room }] : []) },
        examen: { libelle: t('Examen'), sans: t('Examen par défaut'), etiquettes: (c) => (c.exam ? [{ cle: c.exam, libelle: c.exam }] : []) },
        enseignant: { libelle: t('Enseignant'), sans: t('Sans enseignant'), etiquettes: (c) => c.teachers.map((p) => ({ cle: p.id, libelle: p.name })) },
    };
}
const FILTRES = ['langue', 'niveau', 'salle', 'enseignant'] as const;
const REGROUPEMENTS = ['langue', 'niveau', 'salle', 'examen'] as const;
const VUE = ['q', 'groupe', ...FILTRES] as const;

/** Le code d'invitation, copiable d'un clic sans ouvrir la classe. */
function CodeInvitation({ code }: { code: string }) {
    const { t } = useT();
    const [copie, setCopie] = useState(false);
    return (
        <button
            type="button"
            title={t("Copier le code d'invitation")}
            onClick={(e) => {
                // Dans une ligne ou une carte cliquable : copier n'ouvre pas la classe.
                e.preventDefault();
                e.stopPropagation();
                navigator.clipboard?.writeText(code).then(
                    () => {
                        setCopie(true);
                        setTimeout(() => setCopie(false), 1500);
                    },
                    () => undefined,
                );
            }}
            className="inline-flex items-center gap-1.5 rounded-md border border-border bg-muted/60 px-2 py-1 font-mono text-xs font-semibold tracking-wider transition-colors hover:border-primary/50 hover:bg-muted"
        >
            {code}
            {copie ? <Check className="h-3.5 w-3.5 text-emerald-600" /> : <Copy className="h-3.5 w-3.5 text-muted-foreground" />}
            <span className="sr-only">{copie ? t('Code copié') : t('Copier')}</span>
        </button>
    );
}

/** Salle et horaire, avec leurs icônes. */
function SalleHoraire({ c }: { c: ClassroomRow }) {
    if (!c.room && !c.schedule) return null;
    return (
        <span className="flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-muted-foreground">
            {c.room && (
                <span className="inline-flex items-center gap-1">
                    <DoorOpen className="h-3.5 w-3.5" /> {c.room}
                </span>
            )}
            {c.schedule && (
                <span className="inline-flex items-center gap-1">
                    <Clock className="h-3.5 w-3.5" /> {c.schedule}
                </span>
            )}
        </span>
    );
}

export default function ClassesIndex({
    classrooms,
    exams,
    seatsAvailable,
    enseignants = [],
}: {
    classrooms: ClassroomRow[];
    exams: Exam[];
    seatsAvailable: number;
    enseignants?: { id: number; name: string; role: string }[];
}) {
    const { t } = useT();
    const CRITERES = useMemo(() => criteres(t), [t]);
    const sansEnseignantTexte = <span className="text-sm text-amber-700 dark:text-amber-400">{t('Aucun enseignant')}</span>;
    const [showForm, setShowForm] = useState(false);
    const form = useForm<{ name: string; level: string; room: string; schedule: string; exam_id: string; teacher_ids: number[] }>({ name: '', level: '', room: '', schedule: '', exam_id: '', teacher_ids: [] });
    const { etat, poser, reinitialiser } = useVueDansAdresse(VUE);

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(route('center.classes.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setShowForm(false);
            },
        });
    }

    const filtrees = useMemo(
        () =>
            filtrer(classrooms, {
                recherche: etat.q,
                champsRecherche: (c) => [c.name, c.room, c.schedule, c.exam, c.langue?.nom, c.invite_code, ...c.teachers.map((p) => p.name)],
                criteres: CRITERES,
                filtres: Object.fromEntries(FILTRES.map((f) => [f, etat[f]])),
            }),
        [classrooms, etat, CRITERES],
    );

    const critereGroupe = (REGROUPEMENTS as readonly string[]).includes(etat.groupe) ? CRITERES[etat.groupe] : null;
    const groupes = useMemo(
        () => (critereGroupe ? regrouper(filtrees, critereGroupe.etiquettes, { mode: critereGroupe.mode, libelleSans: critereGroupe.sans }) : null),
        [filtrees, critereGroupe],
    );

    const inputClass =
        'mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary';

    const columns: Column<ClassroomRow>[] = [
        {
            key: 'name',
            header: t('Classe'),
            cell: (c) => (
                <span className="block min-w-[11rem] space-y-1">
                    <span className="block font-semibold">{c.name}</span>
                    <SalleHoraire c={c} />
                </span>
            ),
        },
        {
            key: 'langue',
            header: t('Langue · niveau'),
            cell: (c) =>
                c.langue || c.level ? (
                    <span className="flex flex-wrap items-center gap-1">
                        <BadgeLangue langue={c.langue && { ...c.langue, nom: t(c.langue.nom) }} />
                        <BadgeNiveau niveau={c.level} />
                    </span>
                ) : (
                    <span className="text-muted-foreground">—</span>
                ),
            sortValue: (c) => `${c.langue?.nom ?? ''} ${c.level ?? ''}`,
        },
        { key: 'exam', header: t('Examen'), cell: (c) => c.exam ?? <span className="text-muted-foreground">{t('Par défaut')}</span>, sortValue: (c) => c.exam ?? '' },
        {
            key: 'teachers',
            header: t('Enseignants'),
            cell: (c) => <PileAvatars noms={c.teachers.map((p) => p.name)} vide={sansEnseignantTexte} />,
            sortValue: (c) => c.teachers.map((p) => p.name).join(', '),
        },
        {
            key: 'students_count',
            header: t('Élèves'),
            cell: (c) => (
                <span className="inline-flex items-center gap-1.5 font-semibold tabular-nums">
                    <Users className="h-4 w-4 text-muted-foreground" /> {c.students_count}
                </span>
            ),
            sortValue: (c) => c.students_count,
        },
        { key: 'invite_code', header: t("Code d'invitation"), cell: (c) => <CodeInvitation code={c.invite_code} /> },
    ];

    const carte = (c: ClassroomRow) => (
        <div className="space-y-2.5">
            <div className="flex items-start justify-between gap-2">
                <p className="font-semibold leading-snug">{c.name}</p>
                <span className="inline-flex shrink-0 items-center gap-1 text-sm font-semibold tabular-nums">
                    <Users className="h-4 w-4 text-muted-foreground" /> {c.students_count}
                </span>
            </div>
            <div className="flex flex-wrap items-center gap-1">
                <BadgeLangue langue={c.langue && { ...c.langue, nom: t(c.langue.nom) }} />
                <BadgeNiveau niveau={c.level} />
                {c.exam && <span className="text-xs text-muted-foreground">· {c.exam}</span>}
            </div>
            <SalleHoraire c={c} />
            <div className="flex flex-wrap items-center justify-between gap-2 pt-1">
                <PileAvatars noms={c.teachers.map((p) => p.name)} vide={sansEnseignantTexte} />
                <CodeInvitation code={c.invite_code} />
            </div>
        </div>
    );

    const tableau = (lignes: ClassroomRow[], vide: React.ReactNode) => (
        <ListeAdaptative columns={columns} rows={lignes} rowKey={(c) => c.id} lien={(c) => route('center.classes.show', c.id)} carte={carte} empty={vide} />
    );

    const elevesTotal = useMemo(() => elevesDistincts(classrooms), [classrooms]);
    const sansEnseignant = classrooms.filter((c) => c.teachers.length === 0).length;

    const videFiltre = (
        <>
            {t('Aucune classe ne correspond à ces critères.')}{' '}
            <button type="button" onClick={reinitialiser} className="font-medium text-primary hover:underline">
                {t('Tout effacer')}
            </button>
        </>
    );

    return (
        <AppLayout>
            <Head title={t('Classes')} />
            <div className="mx-auto w-full max-w-6xl space-y-5 p-4 md:p-6">
                <EnTetePage
                    titre={t('Classes')}
                    sousTitre={`${compte(t, classrooms.length, '{n} classe', '{n} classes')} · ${compte(t, elevesTotal, '{n} élève', '{n} élèves')} · ${compte(t, seatsAvailable, '{n} siège disponible', '{n} sièges disponibles')}`}
                    actions={
                        <Button onClick={() => setShowForm((s) => !s)} variant={showForm ? 'outline' : 'default'}>
                            {showForm ? <X className="mr-1.5 h-4 w-4" /> : <Plus className="mr-1.5 h-4 w-4" />}
                            {showForm ? t('Annuler') : t('Nouvelle classe')}
                        </Button>
                    }
                />

                {showForm && (
                    <Card className="border-primary/30">
                        <CardHeader>
                            <CardTitle className="text-base">{t('Créer une classe')}</CardTitle>
                            <p className="text-sm text-muted-foreground">
                                {t("Un code d'invitation est créé avec la classe : partagez-le à vos élèves pour qu'ils la rejoignent.")}
                            </p>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="grid gap-4 sm:grid-cols-3">
                                <div className="sm:col-span-1">
                                    <label className="text-sm font-medium" htmlFor="classe-nom">{t('Nom')}</label>
                                    <input id="classe-nom" className={inputClass} value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                                    {form.errors.name && <p className="mt-1 text-xs text-rose-500">{form.errors.name}</p>}
                                </div>
                                <div>
                                    <label className="text-sm font-medium" htmlFor="classe-niveau">{t('Niveau')}</label>
                                    <select id="classe-niveau" className={inputClass} value={form.data.level} onChange={(e) => form.setData('level', e.target.value)}>
                                        <option value="">—</option>
                                        {['A1', 'A2', 'B1', 'B2', 'C1', 'C2'].map((l) => (
                                            <option key={l} value={l}>{l}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="text-sm font-medium" htmlFor="classe-examen">{t('Examen')}</label>
                                    <select id="classe-examen" className={inputClass} value={form.data.exam_id} onChange={(e) => form.setData('exam_id', e.target.value)}>
                                        <option value="">{t('Défaut du centre')}</option>
                                        {exams.map((ex) => (
                                            <option key={ex.id} value={ex.id}>{ex.name}</option>
                                        ))}
                                    </select>
                                    {form.errors.exam_id && <p className="mt-1 text-xs text-rose-500">{form.errors.exam_id}</p>}
                                </div>
                                <div>
                                    <label className="text-sm font-medium" htmlFor="classe-salle">{t('Salle')}</label>
                                    <input
                                        id="classe-salle"
                                        className={inputClass}
                                        placeholder={t('Salle 2')}
                                        maxLength={60}
                                        value={form.data.room}
                                        onChange={(e) => form.setData('room', e.target.value)}
                                    />
                                    {form.errors.room && <p className="mt-1 text-xs text-rose-500">{form.errors.room}</p>}
                                </div>
                                <div className="sm:col-span-2">
                                    <label className="text-sm font-medium" htmlFor="classe-horaire">{t('Horaire')}</label>
                                    <input
                                        id="classe-horaire"
                                        className={inputClass}
                                        placeholder={t('Lun et mer 17 h – 19 h')}
                                        maxLength={120}
                                        value={form.data.schedule}
                                        onChange={(e) => form.setData('schedule', e.target.value)}
                                    />
                                    {form.errors.schedule && <p className="mt-1 text-xs text-rose-500">{form.errors.schedule}</p>}
                                </div>
                                {enseignants.length > 0 && (
                                    <fieldset className="sm:col-span-3">
                                        <legend className="text-sm font-medium">{t('Enseignant(s) de la classe')}</legend>
                                        <p className="text-xs text-muted-foreground">{t('Un enseignant peut tenir plusieurs classes. Sans choix, la classe vous est attribuée.')}</p>
                                        <div className="mt-2 flex flex-wrap gap-2">
                                            {enseignants.map((p) => {
                                                const actif = form.data.teacher_ids.includes(p.id);
                                                return (
                                                    <button
                                                        key={p.id}
                                                        type="button"
                                                        aria-pressed={actif}
                                                        onClick={() => form.setData('teacher_ids', actif ? form.data.teacher_ids.filter((x) => x !== p.id) : [...form.data.teacher_ids, p.id])}
                                                        className={`rounded-full border px-3 py-1.5 text-sm font-medium transition ${actif ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-background hover:border-primary/50'}`}
                                                    >
                                                        {actif ? '✓ ' : ''}{p.name}{p.role === 'center_admin' ? t(' (responsable)') : ''}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </fieldset>
                                )}
                                <div className="flex gap-2 sm:col-span-3">
                                    <Button type="submit" disabled={form.processing}>{form.processing ? t('Création…') : t('Créer la classe')}</Button>
                                    <Button type="button" variant="ghost" onClick={() => setShowForm(false)}>
                                        {t('Annuler')}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}

                {classrooms.length === 0 ? (
                    !showForm && (
                        <EtatVide
                            icone={School}
                            titre={t("Aucune classe pour l'instant")}
                            texte={t("Créez votre première classe : vous obtenez un code d'invitation à partager, et vos élèves la rejoignent en quelques secondes.")}
                            action={
                                <Button onClick={() => setShowForm(true)}>
                                    <Plus className="mr-1.5 h-4 w-4" /> {t('Créer ma première classe')}
                                </Button>
                            }
                        />
                    )
                ) : (
                    <>
                        <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                            <CarteKpi icone={School} ton="primaire" libelle={t('Classes')} valeur={classrooms.length} />
                            <CarteKpi icone={Users} ton="succes" libelle={t('Élèves inscrits')} valeur={elevesTotal} detail={t('Comptés une fois chacun')} />
                            <CarteKpi icone={Armchair} ton={seatsAvailable > 0 ? 'neutre' : 'alerte'} libelle={t('Sièges disponibles')} valeur={seatsAvailable} />
                            <CarteKpi
                                icone={UserX}
                                ton={sansEnseignant ? 'alerte' : 'neutre'}
                                libelle={t('Sans enseignant')}
                                valeur={sansEnseignant}
                                detail={sansEnseignant ? t('Cliquez pour les afficher') : t('Toutes ont un enseignant')}
                                actif={etat.enseignant === SANS}
                                onClick={sansEnseignant ? () => poser('enseignant', etat.enseignant === SANS ? '' : SANS) : undefined}
                            />
                        </div>

                        <BarreClassement
                            recherche={etat.q}
                            onRecherche={(v) => poser('q', v)}
                            placeholder={t('Rechercher une classe, une salle, un enseignant…')}
                            filtres={FILTRES.map((nom) => ({
                                nom,
                                libelle: CRITERES[nom].libelle,
                                tous: { langue: t('Toutes les langues'), niveau: t('Tous les niveaux'), salle: t('Toutes les salles'), enseignant: t('Tous les enseignants') }[nom],
                                options: optionsDe(classrooms, CRITERES[nom].etiquettes, { mode: CRITERES[nom].mode, libelleSans: CRITERES[nom].sans }),
                            }))}
                            valeurs={etat}
                            onFiltre={(nom, v) => poser(nom as (typeof VUE)[number], v)}
                            regroupements={REGROUPEMENTS.map((cle) => ({ cle, libelle: CRITERES[cle].libelle }))}
                            regroupement={critereGroupe ? etat.groupe : ''}
                            onRegroupement={(v) => poser('groupe', v)}
                            onReinitialiser={reinitialiser}
                        />

                        {filtrees.length !== classrooms.length && filtrees.length > 0 && (
                            <p className="px-1 text-xs text-muted-foreground">
                                {t(Math.abs(filtrees.length) > 1 ? '{n} classes sur {total}' : '{n} classe sur {total}', { n: filtrees.length, total: classrooms.length })}
                            </p>
                        )}

                        {groupes && filtrees.length > 0 ? (
                            <div className="space-y-6">
                                {groupes.map((g) => {
                                    const premiere = g.lignes[0];
                                    // Des personnes, pas des inscriptions : un eleve de deux classes du groupe compte une fois.
                                    const eleves = elevesDistincts(g.lignes);
                                    return (
                                        <section key={g.cle} className="space-y-2">
                                            <EnTeteGroupe
                                                titre={g.libelle}
                                                icone={etat.groupe === 'langue' && premiere?.langue ? <Drapeau emoji={premiere.langue.drapeau} /> : undefined}
                                                details={[compte(t, g.lignes.length, '{n} classe', '{n} classes'), compte(t, eleves, '{n} élève', '{n} élèves')]}
                                            />
                                            {tableau(g.lignes, null)}
                                        </section>
                                    );
                                })}
                            </div>
                        ) : (
                            tableau(filtrees, videFiltre)
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
