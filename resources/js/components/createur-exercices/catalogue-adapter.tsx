import axios from 'axios';
import { Copy } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { libelleDuType, libelleExercice, NIVEAUX, type LigneExercice } from '@/lib/createur-exercices';
import { useT } from '@/lib/i18n/traduire';

/**
 * Le catalogue PrePla, dans la banque de sujets du centre : on choisit un
 * examen (et au besoin un niveau, une partie), puis on copie un exercice pour
 * l'adapter. La copie part en brouillon ; l'original ne bouge pas.
 */
const champ = 'min-h-11 rounded-lg border border-border bg-background px-3 text-base sm:text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary';

export function CatalogueAdapter({ exams, onDupliquer }: { exams: { id: number; name: string }[]; onDupliquer: (e: { id: number }) => void }) {
    const { t } = useT();
    const [examen, setExamen] = useState<string>(exams[0] ? String(exams[0].id) : '');
    const [niveau, setNiveau] = useState('');
    const [type, setType] = useState('');
    const [lignes, setLignes] = useState<LigneExercice[] | null>(null);
    const [erreur, setErreur] = useState<string | null>(null);

    useEffect(() => {
        if (examen === '') return;
        let annule = false;
        setLignes(null);
        setErreur(null);
        axios.get(route('center.exercises.catalogue'), { params: { exam_id: examen, difficulty: niveau || undefined } })
            .then((r) => { if (!annule) setLignes(r.data.exercises ?? []); })
            .catch(() => { if (!annule) setErreur(t('Impossible de charger le catalogue.')); });
        return () => { annule = true; };
        // t n'est pas une dependance : une nouvelle fonction a chaque rendu relancerait la requete.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [examen, niveau]);

    const types = useMemo(() => {
        const vus = new Map<string, string>();
        for (const l of lignes ?? []) {
            const cle = String(l.type_slug ?? l.component_key ?? '');
            if (cle && !vus.has(cle)) vus.set(cle, libelleDuType({ slug: l.type_slug, component_key: l.component_key, name: l.type }));
        }
        return [...vus.entries()];
    }, [lignes]);
    const visibles = (lignes ?? []).filter((l) => type === '' || String(l.type_slug ?? l.component_key ?? '') === type);

    return (
        <section aria-label={t('Catalogue PrePla')} className="space-y-3 rounded-2xl border border-border bg-card p-4">
            <div>
                <h2 className="font-semibold">{t('Catalogue PrePla')}</h2>
                <p className="text-sm text-muted-foreground">{t('Copiez un exercice PrePla dans votre banque pour l’adapter. La copie part en brouillon.')}</p>
            </div>
            <div className="grid gap-2 sm:grid-cols-3">
                <select aria-label={t('Examen du catalogue')} className={champ} value={examen} onChange={(e) => { setExamen(e.target.value); setType(''); }}>
                    {exams.map((e) => <option key={e.id} value={e.id}>{e.name}</option>)}
                </select>
                <select aria-label={t('Niveau du catalogue')} className={champ} value={niveau} onChange={(e) => setNiveau(e.target.value)}>
                    <option value="">{t('Tous les niveaux')}</option>
                    {NIVEAUX.map((n) => <option key={n} value={n}>{n}</option>)}
                </select>
                <select aria-label={t('Partie du catalogue')} className={champ} value={type} onChange={(e) => setType(e.target.value)}>
                    <option value="">{t('Toutes les parties')}</option>
                    {types.map(([cle, nom]) => <option key={cle} value={cle}>{t(nom)}</option>)}
                </select>
            </div>
            {erreur && <p role="alert" className="text-sm text-rose-600 dark:text-rose-400">{erreur}</p>}
            {lignes === null && !erreur && <p className="text-sm text-muted-foreground">{t('Chargement…')}</p>}
            {lignes !== null && visibles.length === 0 && <p className="text-sm text-muted-foreground">{t('Aucun exercice du catalogue pour ces filtres.')}</p>}
            {visibles.length > 0 && (
                <ul className="max-h-96 divide-y divide-border overflow-y-auto rounded-xl border border-border">
                    {visibles.map((l) => (
                        <li key={l.id} className="flex items-center gap-2 p-2.5">
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-sm font-semibold">{String(l.titre ?? '').trim() !== '' ? libelleExercice(l) : t(libelleExercice(l))}</p>
                                <p className="truncate text-xs text-muted-foreground">
                                    {t(libelleDuType({ slug: l.type_slug, component_key: l.component_key, name: l.type }))}
                                    {l.difficulty ? ` · ${l.difficulty}` : ''}
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => onDupliquer(l)}
                                className="inline-flex min-h-9 shrink-0 items-center gap-1.5 rounded-lg px-2.5 text-xs font-semibold text-primary hover:bg-primary/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            >
                                <Copy className="h-3.5 w-3.5" aria-hidden="true" /> {t('Copier et adapter')}
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
