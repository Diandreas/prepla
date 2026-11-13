import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { useT } from '@/lib/i18n/traduire';
import { dateHeure, ilYA } from '@/lib/suivi';
import { BilanVocabulaire, ETAPE_COULEUR, Etape, Etapes, RepartitionEtapes } from '@/lib/vocabulaire';

interface Student { id: number; name: string; level: string | null }

interface Mot {
    id: number;
    mot: string | null;
    langue: string | null;
    traduction: string | null;
    definition: string | null;
    niveau: string | null;
    etape: Etape;
    ajoute: string | null;
    derniere_revision: string | null;
    prochaine_revision: string | null;
    reconnaissances: number;
    rappels: number;
    a_reviser: boolean;
    laisse_de_cote: boolean;
}

interface Exemple { contexte: string | null; reponse: string | null; correction: string | null; explication: string | null }
interface Faute { libelle: string; nombre: number; derniere: string | null; exemples: Exemple[] }

type Filtre = 'tous' | 'a_reviser' | Etape;

/**
 * Le vocabulaire d'un eleve, cote centre : ou en est chacun de ses mots, ce
 * qu'il laisse de cote, et les fautes de vocabulaire de ses exercices.
 */
export default function VocabulaireEleve({
    student,
    etapes,
    bilan,
    mots,
    fautes,
}: {
    student: Student;
    etapes: Etapes;
    bilan: BilanVocabulaire;
    mots: Mot[];
    fautes: Faute[];
}) {
    const { t } = useT();
    // « Vocabulaire · faux amis » : chaque morceau est un libelle fixe du serveur.
    const libelleFaute = (libelle: string) => libelle.split(' · ').map((morceau) => t(morceau)).join(' · ');
    const [filtre, setFiltre] = useState<Filtre>('tous');
    const [recherche, setRecherche] = useState('');

    const visibles = useMemo(() => {
        const q = recherche.trim().toLowerCase();
        return mots.filter((m) => {
            if (filtre === 'a_reviser' && !m.a_reviser) return false;
            if (filtre !== 'tous' && filtre !== 'a_reviser' && m.etape !== filtre) return false;
            if (q && !`${m.mot ?? ''} ${m.traduction ?? ''}`.toLowerCase().includes(q)) return false;
            return true;
        });
    }, [mots, filtre, recherche]);

    const filtres: { cle: Filtre; libelle: string; nombre: number }[] = [
        { cle: 'tous', libelle: t('Tous'), nombre: bilan.total },
        { cle: 'a_reviser', libelle: t('À réviser'), nombre: bilan.a_reviser },
        ...(Object.keys(etapes) as Etape[]).map((e) => ({ cle: e, libelle: t(etapes[e]), nombre: bilan.par_etape[e] ?? 0 })),
    ];

    return (
        <AppLayout>
            <Head title={t('Vocabulaire — {nom}', { nom: student.name })} />
            <div className="mx-auto w-full max-w-4xl space-y-5 p-4 md:p-6">
                <div>
                    <Link href={route('center.students.show', student.id)} className="text-sm text-muted-foreground hover:text-foreground">
                        ← {student.name}
                    </Link>
                    <h1 className="mt-1 text-2xl font-bold tracking-tight">{t('Vocabulaire')}</h1>
                    <p className="text-sm text-muted-foreground">
                        {student.name} · {t('Niveau {niveau}', { niveau: student.level ?? '—' })}
                    </p>
                </div>

                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardContent className="p-4">
                            <p className="text-2xl font-bold">{bilan.total}</p>
                            <p className="text-sm text-muted-foreground">
                                {t('Mots dans son lexique')}
                                {bilan.ajoutes_7_jours > 0 && <> · {t('+{n} cette semaine', { n: bilan.ajoutes_7_jours })}</>}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-4">
                            <p className="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{bilan.par_etape.mastered ?? 0}</p>
                            <p className="text-sm text-muted-foreground">{t('Mots maîtrisés')}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-4">
                            <p className={`text-2xl font-bold ${bilan.laisses_de_cote > 0 ? 'text-rose-600 dark:text-rose-400' : ''}`}>{bilan.a_reviser}</p>
                            <p className="text-sm text-muted-foreground">
                                {t('À réviser maintenant')}
                                {bilan.laisses_de_cote > 0 && <> · {t("dont {n} en retard de plus d'une semaine", { n: bilan.laisses_de_cote })}</>}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-4">
                            <p className="text-lg font-bold leading-tight">{ilYA(bilan.derniere_revision)}</p>
                            <p className="mt-0.5 text-xs text-muted-foreground">{dateHeure(bilan.derniere_revision)}</p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {t('Dernière révision')} ·{' '}
                                {bilan.revises_7_jours > 1
                                    ? t('{n} mots révisés en 7 jours', { n: bilan.revises_7_jours })
                                    : t('{n} mot révisé en 7 jours', { n: bilan.revises_7_jours })}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {bilan.total > 0 && (
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">{t('Où en sont ses mots')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <RepartitionEtapes etapes={etapes} parEtape={bilan.par_etape} total={bilan.total} />
                            {Object.keys(bilan.par_niveau).length > 0 && (
                                <p className="text-xs text-muted-foreground">
                                    {t('Par niveau :')}{' '}
                                    {Object.entries(bilan.par_niveau)
                                        .map(([niveau, n]) => `${niveau} ${n}`)
                                        .join(' · ')}
                                </p>
                            )}
                        </CardContent>
                    </Card>
                )}

                {fautes.length > 0 && (
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">{t('Fautes de vocabulaire dans les exercices')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {fautes.map((f) => (
                                <div key={f.libelle} className="rounded-lg border border-border p-3 text-sm">
                                    <div className="flex flex-wrap items-baseline justify-between gap-2">
                                        <p className="font-medium">{libelleFaute(f.libelle)}</p>
                                        <span className="text-xs text-muted-foreground">
                                            {f.nombre > 1 ? t('{n} fautes', { n: f.nombre }) : t('{n} faute', { n: f.nombre })} · {ilYA(f.derniere)}
                                        </span>
                                    </div>
                                    {f.exemples.map((ex, i) => (
                                        <div key={i} className="mt-2 border-l-2 border-muted pl-3 text-xs">
                                            {ex.contexte && <p className="text-muted-foreground">{ex.contexte}</p>}
                                            <p>
                                                {ex.reponse && <span className="text-rose-600 line-through dark:text-rose-400">{ex.reponse}</span>}
                                                {ex.reponse && ex.correction && ' → '}
                                                {ex.correction && <span className="font-medium text-emerald-700 dark:text-emerald-400">{ex.correction}</span>}
                                            </p>
                                            {ex.explication && <p className="mt-0.5 text-muted-foreground">{ex.explication}</p>}
                                        </div>
                                    ))}
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader className="space-y-3 pb-2">
                        <CardTitle className="text-base">{t('Ses mots')}</CardTitle>
                        <div className="flex flex-wrap gap-2">
                            {filtres.map((f) => (
                                <button
                                    key={f.cle}
                                    type="button"
                                    onClick={() => setFiltre(f.cle)}
                                    className={`rounded-full border px-3 py-1 text-xs transition ${
                                        filtre === f.cle ? 'border-primary bg-primary text-primary-foreground' : 'border-border hover:bg-muted'
                                    }`}
                                >
                                    {f.libelle} <span className="opacity-70">{f.nombre}</span>
                                </button>
                            ))}
                            <input
                                type="search"
                                value={recherche}
                                onChange={(e) => setRecherche(e.target.value)}
                                placeholder={t('Chercher un mot')}
                                className="ml-auto h-7 w-40 rounded-md border border-border bg-background px-2 text-xs"
                            />
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {mots.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                {t("Aucun mot pour le moment : l'élève n'a encore rien ajouté à « Mes mots ».")}
                            </p>
                        )}
                        {mots.length > 0 && visibles.length === 0 && <p className="text-sm text-muted-foreground">{t('Aucun mot dans ce filtre.')}</p>}
                        {visibles.map((m) => (
                            <div key={m.id} className="flex flex-wrap items-center gap-x-4 gap-y-1 rounded-lg border border-border p-3 text-sm">
                                <div className="min-w-0 flex-1">
                                    <p className="font-medium">
                                        {m.mot}
                                        {m.niveau && <span className="ml-2 text-xs font-normal text-muted-foreground">{m.niveau}</span>}
                                    </p>
                                    {(m.traduction || m.definition) && (
                                        <p className="truncate text-xs text-muted-foreground">{m.traduction || m.definition}</p>
                                    )}
                                </div>
                                <div className="flex flex-wrap items-center gap-3 text-xs text-muted-foreground tabular-nums">
                                    <span title={t('Reconnaissances réussies · rappels réussis (le mot est maîtrisé après 1 reconnaissance et 2 rappels)')}>
                                        {t('{n} reconn.', { n: m.reconnaissances })} · {m.rappels > 1 ? t('{n} rappels', { n: m.rappels }) : t('{n} rappel', { n: m.rappels })}
                                    </span>
                                    <span title={t('Dernière révision : {date}', { date: dateHeure(m.derniere_revision) })}>
                                        {m.derniere_revision ? t('révisé {quand}', { quand: ilYA(m.derniere_revision) }) : t('jamais révisé')}
                                    </span>
                                    {m.a_reviser && (
                                        <Badge variant="outline" className={m.laisse_de_cote ? 'border-rose-300 text-rose-700 dark:text-rose-400' : 'border-amber-300 text-amber-700 dark:text-amber-400'}>
                                            {m.laisse_de_cote ? t('En retard') : t('À réviser')}
                                        </Badge>
                                    )}
                                    <Badge variant="outline" className={ETAPE_COULEUR[m.etape]}>
                                        {t(etapes[m.etape])}
                                    </Badge>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
