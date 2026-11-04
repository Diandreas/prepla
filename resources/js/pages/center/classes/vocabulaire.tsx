import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ilYA } from '@/lib/suivi';
import { BilanVocabulaire, Etapes, RepartitionEtapes } from '@/lib/vocabulaire';

interface Classroom { id: number; name: string; level: string | null }
interface Eleve extends BilanVocabulaire { id: number; nom: string }
interface MotDifficile { mot: string | null; traduction: string | null; niveau: string | null; eleves: number; a_reviser: number }

/**
 * Le vocabulaire d'une classe : qui apprend ses mots, qui les laisse de cote,
 * et les mots que plusieurs eleves n'arrivent pas a fixer.
 */
export default function VocabulaireClasse({
    classroom,
    etapes,
    eleves,
    mots_difficiles,
}: {
    classroom: Classroom;
    etapes: Etapes;
    eleves: Eleve[];
    mots_difficiles: MotDifficile[];
}) {
    const sansMot = eleves.filter((e) => e.total === 0).length;
    const enRetard = eleves.filter((e) => e.laisses_de_cote > 0).length;

    return (
        <AppLayout>
            <Head title={`Vocabulaire — ${classroom.name}`} />
            <div className="mx-auto w-full max-w-5xl space-y-5 p-4 md:p-6">
                <div>
                    <Link href={route('center.classes.show', classroom.id)} className="text-sm text-muted-foreground hover:text-foreground">
                        ← {classroom.name}
                    </Link>
                    <h1 className="mt-1 text-2xl font-bold tracking-tight">Vocabulaire de la classe</h1>
                    <p className="text-sm text-muted-foreground">
                        {eleves.length} élève{eleves.length > 1 ? 's' : ''}
                        {sansMot > 0 && <> · {sansMot} sans aucun mot</>}
                        {enRetard > 0 && <> · {enRetard} avec des révisions en retard de plus d'une semaine</>}
                    </p>
                </div>

                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Par élève</CardTitle>
                    </CardHeader>
                    <CardContent className="overflow-x-auto">
                        {eleves.length === 0 ? (
                            <p className="text-sm text-muted-foreground">Aucun élève dans cette classe.</p>
                        ) : (
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-border text-left text-xs text-muted-foreground">
                                        <th className="py-2 pr-3 font-medium">Élève</th>
                                        <th className="py-2 pr-3 text-right font-medium">Mots</th>
                                        <th className="w-56 py-2 pr-3 font-medium">Étapes</th>
                                        <th className="py-2 pr-3 text-right font-medium">À réviser</th>
                                        <th className="py-2 pr-3 text-right font-medium">Révisés (7 j)</th>
                                        <th className="py-2 font-medium">Dernière révision</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {eleves.map((e) => (
                                        <tr key={e.id} className="border-b border-border/60 last:border-0">
                                            <td className="py-2 pr-3">
                                                <Link href={route('center.students.vocabulaire', e.id)} className="font-medium hover:underline">
                                                    {e.nom}
                                                </Link>
                                            </td>
                                            <td className="py-2 pr-3 text-right tabular-nums">
                                                {e.total}
                                                {e.ajoutes_7_jours > 0 && <span className="ml-1 text-xs text-emerald-600 dark:text-emerald-400">+{e.ajoutes_7_jours}</span>}
                                            </td>
                                            <td className="py-2 pr-3">
                                                <RepartitionEtapes etapes={etapes} parEtape={e.par_etape} total={e.total} />
                                            </td>
                                            <td
                                                className={`py-2 pr-3 text-right tabular-nums ${e.laisses_de_cote > 0 ? 'font-semibold text-rose-600 dark:text-rose-400' : ''}`}
                                                title={e.laisses_de_cote > 0 ? `${e.laisses_de_cote} en retard de plus d'une semaine` : undefined}
                                            >
                                                {e.a_reviser}
                                            </td>
                                            <td className="py-2 pr-3 text-right tabular-nums">{e.revises_7_jours}</td>
                                            <td className="py-2 text-muted-foreground">{e.derniere_revision ? ilYA(e.derniere_revision) : 'Jamais'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Mots qui résistent à plusieurs élèves</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {mots_difficiles.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Aucun mot n'est encore « en cours » chez au moins deux élèves.
                            </p>
                        ) : (
                            <div className="divide-y divide-border/60">
                                {mots_difficiles.map((m, i) => (
                                    <div key={`${m.mot}-${i}`} className="flex flex-wrap items-baseline justify-between gap-2 py-2 text-sm">
                                        <div className="min-w-0">
                                            <span className="font-medium">{m.mot}</span>
                                            {m.niveau && <span className="ml-2 text-xs text-muted-foreground">{m.niveau}</span>}
                                            {m.traduction && <span className="ml-2 text-xs text-muted-foreground">— {m.traduction}</span>}
                                        </div>
                                        <span className="text-xs text-muted-foreground tabular-nums">
                                            en cours chez {m.eleves} élèves
                                            {m.a_reviser > 0 && <> · {m.a_reviser} à réviser</>}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
