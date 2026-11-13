import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { useT } from '@/lib/i18n/traduire';
import { dateHeure, duree, ilYA } from '@/lib/suivi';
import TableauDeClasse from '@/components/center/tableau-de-classe';

interface Classroom {
    id: number;
    name: string;
    level: string | null;
    room: string | null;
    schedule: string | null;
    exam: string | null;
    exam_id: number | null;
    invite_code: string;
}
interface ExamOption { id: number; name: string; language?: string | null }
interface Student { id: number; name: string; email: string }
interface Weakness { category: string; subcategory: string | null; libelle: string; count: number }
interface SuiviEleve {
    user_id: number;
    attempts: number;
    avg_accuracy: number | null;
    last_active: string | null;
    temps_total_secondes: number;
}
interface Stats {
    student_count: number;
    class_avg_accuracy: number | null;
    dropouts: { user_id: number; name: string; attempts: number }[];
    common_weaknesses: Weakness[];
    per_student: SuiviEleve[];
}

export default function ClassShow({
    classroom,
    students,
    stats,
    exams,
    parcoursEtapes = 0,
    contenuCentre = false,
}: {
    classroom: Classroom;
    students: Student[];
    stats: Stats;
    exams: ExamOption[];
    parcoursEtapes?: number;
    contenuCentre?: boolean;
}) {
    const { t } = useT();
    const page = usePage().props as any;
    const { flash } = page;
    // Archiver est reserve au responsable de l'espace : le bouton etait aussi
    // montre aux enseignants, chez qui il ne pouvait que renvoyer un refus.
    const peutArchiver = page.auth?.center?.role === 'center_admin' || page.auth?.role === 'super_admin';

    // Renommer une classe, corriger son niveau ou changer son examen etait possible
    // cote serveur depuis le debut, mais aucun bouton n'y menait.
    const [editing, setEditing] = useState(false);
    const edit = useForm({
        name: classroom.name,
        level: classroom.level ?? '',
        room: classroom.room ?? '',
        schedule: classroom.schedule ?? '',
        exam_id: classroom.exam_id ? String(classroom.exam_id) : '',
    });

    function saveClass(event: React.FormEvent) {
        event.preventDefault();
        edit.patch(route('center.classes.update', classroom.id), {
            preserveScroll: true,
            onSuccess: () => setEditing(false),
        });
    }

    function regenerate() {
        router.post(route('center.classes.regenerate-code', classroom.id), {}, { preserveScroll: true });
    }

    function removeStudent(studentId: number) {
        router.delete(route('center.classes.students.remove', [classroom.id, studentId]), { preserveScroll: true });
    }

    function archiveClass() {
        if (confirm(t("Archiver « {nom} » ? Les élèves n'y auront plus accès. L'historique des devoirs et des tentatives est conservé.", { nom: classroom.name }))) {
            router.delete(route('center.classes.archive', classroom.id));
        }
    }

    return (
        <AppLayout>
            <Head title={classroom.name} />
            <div className="mx-auto w-full max-w-3xl space-y-5 p-4 md:p-6">
                <div className="flex items-start justify-between gap-3">
                    <div>
                        <Link href={route('center.classes.index')} className="text-sm text-muted-foreground hover:text-foreground">
                            ← {t('Classes')}
                        </Link>
                        <h1 className="mt-1 text-2xl font-bold tracking-tight">{classroom.name}</h1>
                        <p className="text-sm text-muted-foreground">
                            {classroom.level ?? t('Niveau libre')} · {classroom.exam ?? t('Examen par défaut')}
                        </p>
                        {(classroom.room || classroom.schedule) && (
                            <p className="mt-0.5 flex flex-wrap gap-x-3 text-sm text-muted-foreground">
                                {classroom.room && <span>{t('Salle :')} <span className="font-medium text-foreground">{classroom.room}</span></span>}
                                {classroom.schedule && <span>{t('Horaire :')} <span className="font-medium text-foreground">{classroom.schedule}</span></span>}
                            </p>
                        )}
                    </div>
                    <div className="flex shrink-0 items-center gap-1">
                        <Button variant="ghost" onClick={() => setEditing((open) => !open)} aria-expanded={editing}>
                            {editing ? t('Annuler') : t('Modifier')}
                        </Button>
                        {peutArchiver && (
                            <Button variant="ghost" className="text-rose-500 hover:text-rose-600" onClick={archiveClass}>
                                {t('Archiver')}
                            </Button>
                        )}
                    </div>
                </div>

                {editing && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">{t('Modifier la classe')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={saveClass} className="space-y-4">
                                <div className="grid gap-2">
                                    <label htmlFor="class-edit-name" className="text-sm font-semibold">{t('Nom')}</label>
                                    <input
                                        id="class-edit-name"
                                        className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                        value={edit.data.name}
                                        onChange={(event) => edit.setData('name', event.target.value)}
                                    />
                                    {edit.errors.name && <p className="text-xs text-rose-500">{edit.errors.name}</p>}
                                </div>

                                <div className="grid gap-4 sm:grid-cols-[7rem_1fr]">
                                    <div className="grid gap-2">
                                        <label htmlFor="class-edit-level" className="text-sm font-semibold">{t('Niveau')}</label>
                                        <input
                                            id="class-edit-level"
                                            placeholder="A2"
                                            className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm uppercase focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                            value={edit.data.level}
                                            onChange={(event) => edit.setData('level', event.target.value.toUpperCase())}
                                            maxLength={10}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <label htmlFor="class-edit-exam" className="text-sm font-semibold">{t('Examen')}</label>
                                        <select
                                            id="class-edit-exam"
                                            className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                            value={edit.data.exam_id}
                                            onChange={(event) => edit.setData('exam_id', event.target.value)}
                                        >
                                            <option value="">{t("Examen par défaut de l'espace")}</option>
                                            {exams.map((exam) => (
                                                <option key={exam.id} value={exam.id}>
                                                    {exam.language ? `${exam.language} — ${exam.name}` : exam.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>

                                <div className="grid gap-4 sm:grid-cols-[12rem_1fr]">
                                    <div className="grid gap-2">
                                        <label htmlFor="class-edit-room" className="text-sm font-semibold">{t('Salle')}</label>
                                        <input
                                            id="class-edit-room"
                                            placeholder={t('Salle 2')}
                                            maxLength={60}
                                            className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                            value={edit.data.room}
                                            onChange={(event) => edit.setData('room', event.target.value)}
                                        />
                                        {edit.errors.room && <p className="text-xs text-rose-500">{edit.errors.room}</p>}
                                    </div>
                                    <div className="grid gap-2">
                                        <label htmlFor="class-edit-schedule" className="text-sm font-semibold">{t('Horaire')}</label>
                                        <input
                                            id="class-edit-schedule"
                                            placeholder={t('Lun et mer 17 h – 19 h')}
                                            maxLength={120}
                                            className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                            value={edit.data.schedule}
                                            onChange={(event) => edit.setData('schedule', event.target.value)}
                                        />
                                        {edit.errors.schedule && <p className="text-xs text-rose-500">{edit.errors.schedule}</p>}
                                    </div>
                                </div>

                                <Button type="submit" disabled={edit.processing || !edit.data.name.trim()}>
                                    {edit.processing ? t('Enregistrement…') : t('Enregistrer')}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                )}

                {flash?.success && (
                    <div className="rounded-lg border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300">
                        {flash.success}
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">{t("Code d'invitation")}</CardTitle>
                    </CardHeader>
                    <CardContent className="flex items-center justify-between gap-3">
                        <div>
                            <code className="rounded bg-muted px-3 py-1.5 text-lg font-bold tracking-widest">{classroom.invite_code}</code>
                            <p className="mt-1 text-xs text-muted-foreground">{t('Vos élèves saisissent ce code sur /join pour rejoindre la classe.')}</p>
                        </div>
                        <Button variant="outline" onClick={regenerate}>{t('Régénérer')}</Button>
                    </CardContent>
                </Card>

                {/* Le parcours que le centre construit pour la classe : sa page à lui. */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">{t('Parcours de la classe')}</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-wrap items-center justify-between gap-3">
                        <div className="min-w-0 text-sm">
                            <p className="font-medium">
                                {parcoursEtapes === 0 ? t('Aucune étape pour le moment') : parcoursEtapes > 1 ? t('{n} étapes', { n: parcoursEtapes }) : t('{n} étape', { n: parcoursEtapes })}
                            </p>
                            <p className="mt-0.5 text-xs text-muted-foreground">
                                {contenuCentre
                                    ? t('Vos élèves suivent ce parcours, en plus de leurs devoirs.')
                                    : t('Votre espace utilise le parcours PrePla par défaut : vos élèves ne voient ce parcours que si vous passez au « Parcours créé par le centre ».')}
                            </p>
                        </div>
                        <Link href={route('center.classes.parcours', classroom.id)}>
                            <Button variant="outline">{parcoursEtapes === 0 ? t('Créer le parcours') : t('Gérer le parcours')}</Button>
                        </Link>
                    </CardContent>
                </Card>

                {/* Tableau de bord de la classe : chiffres, activité, compétences, classement. */}
                <TableauDeClasse classroomId={classroom.id} />

                {stats.common_weaknesses.length > 0 && (
                    <Card>
                        <CardHeader><CardTitle className="text-base">{t('Faiblesses communes de la classe')}</CardTitle></CardHeader>
                        <CardContent className="space-y-2">
                            {stats.common_weaknesses.map((w, i) => (
                                <div key={i} className="flex items-center justify-between rounded-lg border border-border p-2.5 text-sm">
                                    <span>{w.libelle.split(' · ').map((morceau) => t(morceau)).join(' · ')}</span>
                                    <Badge variant="secondary">{w.count}</Badge>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                {stats.dropouts.length > 0 && (
                    <Card>
                        <CardHeader><CardTitle className="text-base">{t('À relancer ({n})', { n: stats.dropouts.length })}</CardTitle></CardHeader>
                        <CardContent className="space-y-2">
                            {stats.dropouts.map((d) => (
                                <div key={d.user_id} className="flex items-center justify-between rounded-lg border border-border p-2.5 text-sm">
                                    <Link href={route('center.students.show', d.user_id)} className="font-medium hover:underline">{d.name}</Link>
                                    <span className="text-xs text-muted-foreground">{d.attempts === 0 ? t('Aucune activité') : t('Inactif ≥ 7 j')}</span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                <div className="flex flex-wrap justify-end gap-2">
                    <Button asChild variant="outline">
                        <Link href={route('center.classes.vocabulaire', classroom.id)}>{t('Vocabulaire de la classe')}</Link>
                    </Button>
                    <a href={route('center.classes.export', classroom.id)}>
                        <Button variant="outline">{t('Exporter en CSV')}</Button>
                    </a>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">{t('Élèves ({n})', { n: students.length })}</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {students.length === 0 && <p className="text-sm text-muted-foreground">{t("Aucun élève n'a encore rejoint cette classe.")}</p>}
                        {students.map((s) => {
                            // Le suivi de chaque eleve etait deja calcule et envoye ;
                            // la liste n'en montrait que le nom.
                            const suivi = stats.per_student?.find((p) => p.user_id === s.id);
                            return (
                                <div key={s.id} className="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 rounded-lg border border-border p-2.5 text-sm">
                                    <Link href={route('center.students.show', s.id)} className="min-w-0 flex-1 hover:underline">
                                        <p className="truncate font-medium">{s.name}</p>
                                        <p className="truncate text-xs text-muted-foreground">{s.email}</p>
                                    </Link>
                                    {suivi && (
                                        <div className="flex items-center gap-4 text-xs text-muted-foreground tabular-nums">
                                            <span title={dateHeure(suivi.last_active)}>{suivi.last_active ? t('Actif {quand}', { quand: ilYA(suivi.last_active) }) : t('Jamais actif')}</span>
                                            <span title={t('Exercices réalisés')}>{t('{n} ex.', { n: suivi.attempts })}</span>
                                            <span title={t('Temps de travail total')}>{duree(suivi.temps_total_secondes)}</span>
                                            {suivi.avg_accuracy !== null && <span title={t('Réussite moyenne')}>{Math.round(suivi.avg_accuracy)}%</span>}
                                        </div>
                                    )}
                                    <button onClick={() => removeStudent(s.id)} className="shrink-0 text-xs text-rose-500 hover:underline">
                                        {t('Retirer')}
                                    </button>
                                </div>
                            );
                        })}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
