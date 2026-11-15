import { Head, Link } from '@inertiajs/react';
import axios from 'axios';
import { Download, FileUp, Upload } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useT } from '@/lib/i18n/traduire';

interface Ligne {
    ligne: number;
    statut: 'nouveau' | 'existant' | 'doublon' | 'erreur' | 'valide';
    erreurs: string[];
    [cle: string]: unknown;
}

interface Props {
    classes: string[];
    examens: { slug: string; name: string }[];
    peutImporterExercices: boolean;
    limites: { taille_ko: number; eleves: number; exercices: number };
    places: number;
}

const TON: Record<Ligne['statut'], 'default' | 'secondary' | 'destructive' | 'outline'> = {
    nouveau: 'default',
    existant: 'default',
    valide: 'default',
    doublon: 'secondary',
    erreur: 'destructive',
};

const LIBELLE: Record<Ligne['statut'], string> = {
    nouveau: 'Nouveau compte',
    existant: 'Compte existant',
    valide: 'Valide',
    doublon: 'Doublon ignoré',
    erreur: 'Erreur',
};

/** Un import en deux temps : apercu du fichier, puis validation. */
function BlocImport({
    titre,
    description,
    accept,
    routeModele,
    routeApercu,
    routeImport,
    colonnes,
    bilanTexte,
}: {
    titre: string;
    description: string;
    accept: string;
    routeModele: string;
    routeApercu: string;
    routeImport: string;
    colonnes: { cle: string; titre: string }[];
    bilanTexte: (bilan: Record<string, number>) => string;
}) {
    const { t } = useT();
    const [fichier, setFichier] = useState<File | null>(null);
    const [lignes, setLignes] = useState<Ligne[] | null>(null);
    const [erreur, setErreur] = useState<string | null>(null);
    const [bilan, setBilan] = useState<string | null>(null);
    const [occupe, setOccupe] = useState(false);

    async function envoyer(url: string) {
        if (!fichier) return null;
        const donnees = new FormData();
        donnees.append('fichier', fichier);
        setOccupe(true);
        setErreur(null);
        try {
            const res = await axios.post(url, donnees);
            return res.data as { lignes: Ligne[]; bilan?: Record<string, number> };
        } catch (e) {
            const err = e as { response?: { data?: { errors?: Record<string, string[]>; message?: string } } };
            setErreur(err.response?.data?.errors?.fichier?.[0] ?? err.response?.data?.message ?? t("L'envoi a échoué. Réessayez."));
            return null;
        } finally {
            setOccupe(false);
        }
    }

    async function apercu() {
        setBilan(null);
        const r = await envoyer(routeApercu);
        if (r) setLignes(r.lignes);
    }

    async function importer() {
        const r = await envoyer(routeImport);
        if (r?.bilan) {
            setBilan(bilanTexte(r.bilan));
            setLignes(null);
            setFichier(null);
        }
    }

    const aImporter = lignes?.filter((l) => ['nouveau', 'existant', 'valide'].includes(l.statut)).length ?? 0;

    return (
        <Card>
            <CardHeader className="pb-2">
                <CardTitle className="text-base">{titre}</CardTitle>
                <p className="text-sm text-muted-foreground">{description}</p>
            </CardHeader>
            <CardContent className="space-y-3">
                <div className="flex flex-wrap items-center gap-2">
                    <Button asChild variant="outline" size="sm">
                        <a href={routeModele}>
                            <Download className="mr-1.5 h-4 w-4" aria-hidden="true" /> {t('Télécharger le modèle CSV')}
                        </a>
                    </Button>
                    <label className="inline-flex min-w-0 cursor-pointer items-center gap-2 rounded-md border px-3 py-1.5 text-sm hover:bg-muted">
                        <FileUp className="h-4 w-4 shrink-0" aria-hidden="true" />
                        <span className="truncate">{fichier ? fichier.name : t('Choisir un fichier')}</span>
                        <input
                            type="file"
                            accept={accept}
                            className="sr-only"
                            onChange={(e) => {
                                setFichier(e.target.files?.[0] ?? null);
                                setLignes(null);
                                setBilan(null);
                                setErreur(null);
                            }}
                        />
                    </label>
                    <Button type="button" size="sm" onClick={apercu} disabled={!fichier || occupe}>
                        {t('Voir l’aperçu')}
                    </Button>
                </div>

                {erreur && <p className="text-sm text-destructive" role="alert">{erreur}</p>}
                {bilan && <p className="rounded-md border border-emerald-500/40 bg-emerald-500/10 px-3 py-2 text-sm" role="status">{bilan}</p>}

                {lignes && (
                    <div className="space-y-3">
                        <div className="overflow-x-auto rounded-md border">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/50 text-left text-xs text-muted-foreground">
                                    <tr>
                                        <th className="px-2 py-1.5">{t('Ligne')}</th>
                                        {colonnes.map((c) => (
                                            <th key={c.cle} className="px-2 py-1.5">{c.titre}</th>
                                        ))}
                                        <th className="px-2 py-1.5">{t('Statut')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {lignes.map((l) => (
                                        <tr key={l.ligne} className="border-t align-top">
                                            <td className="px-2 py-1.5 tabular-nums text-muted-foreground">{l.ligne}</td>
                                            {colonnes.map((c) => (
                                                <td key={c.cle} className="max-w-[16rem] break-words px-2 py-1.5">
                                                    {Array.isArray(l[c.cle]) ? (l[c.cle] as string[]).join(' · ') : String(l[c.cle] ?? '')}
                                                </td>
                                            ))}
                                            <td className="px-2 py-1.5">
                                                <Badge variant={TON[l.statut]}>{t(LIBELLE[l.statut])}</Badge>
                                                {l.erreurs.map((e) => (
                                                    <p key={e} className="mt-1 text-xs text-destructive">{e}</p>
                                                ))}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="flex flex-wrap items-center gap-3">
                            <Button type="button" onClick={importer} disabled={occupe || aImporter === 0}>
                                <Upload className="mr-1.5 h-4 w-4" aria-hidden="true" /> {t('Importer {n} lignes', { n: aImporter })}
                            </Button>
                            <p className="text-xs text-muted-foreground">{t('Les lignes en erreur et les doublons sont laissés de côté.')}</p>
                        </div>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

/** Import en masse du centre : liste d'eleves et QCM de la banque. */
export default function Import({ classes, examens, peutImporterExercices, limites, places }: Props) {
    const { t } = useT();

    return (
        <AppLayout>
            <Head title={t('Importer')} />
            <div className="mx-auto w-full max-w-5xl space-y-5 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">{t('Importer')}</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {t('Fichiers de {ko} Ko au plus. Vérifiez l’aperçu avant de valider : rien n’est enregistré avant.', { ko: limites.taille_ko })}
                    </p>
                </div>

                <BlocImport
                    titre={t('Élèves')}
                    description={t(
                        'Colonnes : nom, email, telephone (facultatif), classe. {max} lignes au plus. Chaque élève reçoit une invitation par email. Licences disponibles : {places}.',
                        { max: limites.eleves, places },
                    )}
                    accept=".csv,text/csv"
                    routeModele={route('center.import.eleves.modele')}
                    routeApercu={route('center.import.eleves.apercu')}
                    routeImport={route('center.import.eleves')}
                    colonnes={[
                        { cle: 'nom', titre: t('Nom') },
                        { cle: 'email', titre: t('Email') },
                        { cle: 'telephone', titre: t('Téléphone') },
                        { cle: 'classe', titre: t('Classe') },
                    ]}
                    bilanTexte={(b) =>
                        t('{crees} comptes créés, {rattaches} comptes rattachés, {ignores} doublons ignorés, {erreurs} lignes en erreur.', {
                            crees: b.crees ?? 0,
                            rattaches: b.rattaches ?? 0,
                            ignores: b.ignores ?? 0,
                            erreurs: b.erreurs ?? 0,
                        })
                    }
                />
                {classes.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {t('Aucune classe ouverte que vous encadrez : créez-en une avant d’importer des élèves.')}{' '}
                        <Link href={route('center.classes.index')} className="underline">{t('Classes')}</Link>
                    </p>
                ) : (
                    <p className="text-xs text-muted-foreground">{t('Classes acceptées : {liste}', { liste: classes.join(', ') })}</p>
                )}

                {peutImporterExercices && (
                    <>
                        <BlocImport
                            titre={t('Exercices QCM')}
                            description={t(
                                'CSV ou JSON. Champs : question, options (séparées par « | »), bonne_reponse (lettre ou texte), competence, niveau, examen, explication. {max} questions au plus. Chaque question devient un brouillon dans votre banque.',
                                { max: limites.exercices },
                            )}
                            accept=".csv,.json,text/csv,application/json"
                            routeModele={route('center.import.exercices.modele')}
                            routeApercu={route('center.import.exercices.apercu')}
                            routeImport={route('center.import.exercices')}
                            colonnes={[
                                { cle: 'question', titre: t('Question') },
                                { cle: 'options', titre: t('Options') },
                                { cle: 'bonne_reponse', titre: t('Réponse') },
                                { cle: 'competence', titre: t('Compétence') },
                                { cle: 'niveau', titre: t('Niveau') },
                                { cle: 'examen', titre: t('Examen') },
                            ]}
                            bilanTexte={(b) => t('{crees} brouillons créés, {erreurs} lignes en erreur.', { crees: b.crees ?? 0, erreurs: b.erreurs ?? 0 })}
                        />
                        <p className="text-xs text-muted-foreground">
                            {t('Examens acceptés : {liste}', { liste: examens.map((e) => `${e.slug} (${e.name})`).join(', ') || '—' })}
                        </p>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
