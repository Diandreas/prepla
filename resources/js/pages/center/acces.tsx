import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import OngletsParametresCentre from '@/components/onglets-parametres-centre';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { KeyRound } from 'lucide-react';
import { useT, type Vars } from '@/lib/i18n/traduire';

interface Acces { id: number; du: string; au: string; en_cours: boolean }
interface Eleve { id: number; name: string; email: string; acces: Acces[] }
interface Ligne {
    acces_id: number;
    eleve: string | null;
    ouvert_le: string | null;
    au: string | null;
    montant: number;
    moyen: string;
    reference: string | null;
    ouvert_par: string | null;
    coupe_le: string | null;
}
interface Centre {
    id: number;
    name: string;
    formule: 'paliers' | 'revente' | 'libre_service';
    prix_acces: number | null;
    duree_acces_jours: number;
    devise: string;
}
interface Mois { periode: string; total: number; part_prepla: number; taux: number; lignes: Ligne[] }

const FUSEAU = 'Africa/Douala';

const MOYENS: Record<string, string> = { mobile_money: 'Mobile money', cash: 'Espèces', autre: 'Autre' };

/** Le franc CFA n'a pas de centimes : des montants entiers, groupés à la française. */
function montant(n: number, devise: string, locale = 'fr-FR'): string {
    return `${n.toLocaleString(locale)} ${devise === 'XAF' ? 'FCFA' : devise}`;
}

function jour(iso: string | null, locale = 'fr-FR'): string {
    return iso ? new Date(iso).toLocaleDateString(locale, { day: 'numeric', month: 'long', year: 'numeric', timeZone: FUSEAU }) : '—';
}

function nomDuMois(periode: string, locale = 'fr-FR'): string {
    const [a, m] = periode.split('-').map(Number);
    return new Date(Date.UTC(a, m - 1, 15)).toLocaleDateString(locale, { month: 'long', year: 'numeric', timeZone: 'UTC' });
}

/** Les messages du serveur (AccesController) : fixes, ou avec un nom et une date. */
function messageTraduit(message: string, t: (fr: string, vars?: Vars) => string): string {
    const ouvert = /^Accès ouvert pour (.+) jusqu'au (\d{2}\/\d{2}\/\d{4})\.$/.exec(message);
    if (ouvert) return t("Accès ouvert pour {nom} jusqu'au {date}.", { nom: ouvert[1], date: ouvert[2] });
    return t(message);
}

const inputClass =
    'mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary';

function FormulaireOuverture({ eleve, centre, moyens, onFermer }: { eleve: Eleve; centre: Centre; moyens: string[]; onFermer: () => void }) {
    const { t, locale } = useT();
    const form = useForm({
        user_id: eleve.id,
        duree_jours: centre.duree_acces_jours,
        montant: centre.prix_acces ?? 0,
        moyen: moyens[0] ?? 'mobile_money',
        reference: '',
    });

    function envoyer(e: React.FormEvent) {
        e.preventDefault();
        form.post(route('center.acces.store'), { preserveScroll: true, onSuccess: onFermer });
    }

    const prolonge = eleve.acces.length > 0;

    return (
        <form onSubmit={envoyer} className="mt-3 space-y-3 rounded-lg border border-dashed p-3">
            {prolonge && (
                <p className="text-xs text-muted-foreground">
                    {t("Cet élève a déjà un accès jusqu'au {date} : le nouvel accès commencera à cette date.", { date: jour(eleve.acces[eleve.acces.length - 1].au, locale) })}
                </p>
            )}
            <div className="grid gap-3 sm:grid-cols-2">
                <div>
                    <label className="text-sm font-medium">{t('Durée (jours)')}</label>
                    <input type="number" min={1} max={366} className={inputClass} value={form.data.duree_jours}
                        onChange={(e) => form.setData('duree_jours', Number(e.target.value))} />
                    {form.errors.duree_jours && <p className="mt-1 text-xs text-red-600">{form.errors.duree_jours}</p>}
                </div>
                <div>
                    <label className="text-sm font-medium">{t('Montant encaissé ({devise})', { devise: centre.devise === 'XAF' ? 'FCFA' : centre.devise })}</label>
                    <input type="number" min={0} step={1} className={inputClass} value={form.data.montant}
                        onChange={(e) => form.setData('montant', Number(e.target.value))} />
                    {form.errors.montant && <p className="mt-1 text-xs text-red-600">{form.errors.montant}</p>}
                </div>
                <div>
                    <label className="text-sm font-medium">{t('Moyen de paiement')}</label>
                    <select className={inputClass} value={form.data.moyen} onChange={(e) => form.setData('moyen', e.target.value)}>
                        {moyens.map((m) => <option key={m} value={m}>{MOYENS[m] ? t(MOYENS[m]) : m}</option>)}
                    </select>
                </div>
                <div>
                    <label className="text-sm font-medium">{t('Référence')} <span className="font-normal text-muted-foreground">{t('(facultatif)')}</span></label>
                    <input className={inputClass} value={form.data.reference} maxLength={120} placeholder={t('N° de transaction, reçu…')}
                        onChange={(e) => form.setData('reference', e.target.value)} />
                </div>
            </div>
            {form.errors.user_id && <p className="text-xs text-red-600">{t(form.errors.user_id)}</p>}
            <div className="flex flex-wrap gap-2">
                <Button type="submit" size="sm" disabled={form.processing}>{form.processing ? t('Ouverture…') : t("Ouvrir l'accès")}</Button>
                <Button type="button" size="sm" variant="ghost" onClick={onFermer}>{t('Annuler')}</Button>
            </div>
        </form>
    );
}

/**
 * Les accès vendus par le centre (formule revente), pour son responsable :
 * qui a un accès et jusqu'à quand, ouvrir ou couper un accès, et ce qui a été
 * encaissé ce mois-ci avec la part reversée à PrePla.
 */
export default function AccesVendus({ centre, eleves, mois, moyens }: { centre: Centre; eleves: Eleve[]; mois: Mois; moyens: string[] }) {
    const { t, locale } = useT();
    const { flash } = usePage().props as { flash?: { success?: string } };
    const [ouvertPour, setOuvertPour] = useState<number | null>(null);
    const enRevente = centre.formule === 'revente';

    function couper(acces: Acces, eleve: Eleve) {
        if (!confirm(t("Couper l'accès de {nom} ? Il retrouvera la limite d'exercices gratuits.", { nom: eleve.name }))) return;
        router.post(route('center.acces.couper', acces.id), {}, { preserveScroll: true });
    }

    return (
        <AppLayout>
            <Head title={t('Accès vendus — {nom}', { nom: centre.name })} />
            <div className="mx-auto w-full max-w-4xl space-y-5 p-4 md:p-6">
                <OngletsParametresCentre />
                <div>
                    <p className="text-sm text-muted-foreground">{centre.name}</p>
                    <h1 className="text-2xl font-bold tracking-tight">{t('Accès vendus')}</h1>
                    <p className="text-sm text-muted-foreground">
                        {t("Vous encaissez vous-même l'accès de vos élèves ; ici, vous l'ouvrez. Un élève avec un accès en cours n'a plus de limite d'exercices.")}
                    </p>
                </div>

                {flash?.success && (
                    <div className="rounded-lg border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300">
                        {messageTraduit(flash.success, t)}
                    </div>
                )}

                {!enRevente && (
                    <Card className="border-amber-300/70 dark:border-amber-900">
                        <CardContent className="pt-6 text-sm">
                            {t("Votre centre n'est pas en formule « revente » : vos élèves travaillent déjà sans limite d'exercices, il n'y a pas d'accès à leur vendre.")}
                            {' '}{t('Pour changer de formule, contactez PrePla.')}
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">{t('Encaissements de {mois}', { mois: nomDuMois(mois.periode, locale) })}</CardTitle>
                        <p className="text-xs text-muted-foreground">
                            {t('Accès ouverts ce mois-ci, coupés ou non. Part PrePla : {taux} %, calculée sur ces montants au relevé du mois.', { taux: mois.taux })}
                        </p>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="rounded-lg border p-3">
                                <p className="text-xs text-muted-foreground">{t('Total encaissé')}</p>
                                <p className="text-xl font-bold tabular-nums">{montant(mois.total, centre.devise, locale)}</p>
                            </div>
                            <div className="rounded-lg border p-3">
                                <p className="text-xs text-muted-foreground">{t('Part PrePla ({taux} %)', { taux: mois.taux })}</p>
                                <p className="text-xl font-bold tabular-nums">{montant(mois.part_prepla, centre.devise, locale)}</p>
                            </div>
                        </div>
                        {mois.lignes.length === 0 ? (
                            <p className="text-sm text-muted-foreground">{t('Aucun accès ouvert ce mois-ci.')}</p>
                        ) : (
                            <div className="space-y-2">
                                {mois.lignes.map((l) => (
                                    <div key={l.acces_id} className="flex flex-wrap items-center justify-between gap-x-3 gap-y-0.5 rounded-md border px-3 py-2 text-sm">
                                        <span>
                                            <span className="font-medium">{l.eleve ?? '—'}</span>
                                            <span className="text-muted-foreground"> · {jour(l.ouvert_le, locale)} · {MOYENS[l.moyen] ? t(MOYENS[l.moyen]) : l.moyen}{l.reference ? ` · ${l.reference}` : ''}</span>
                                            {l.ouvert_par && <span className="text-xs text-muted-foreground">{t(' · par {nom}', { nom: l.ouvert_par })}</span>}
                                        </span>
                                        <span className="flex items-center gap-2 tabular-nums">
                                            {l.coupe_le && <Badge variant="secondary">{t('Coupé le {date}', { date: jour(l.coupe_le, locale) })}</Badge>}
                                            {montant(l.montant, centre.devise, locale)}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">{t('Élèves ({n})', { n: eleves.length })}</CardTitle>
                        {enRevente && centre.prix_acces !== null && (
                            <p className="text-xs text-muted-foreground">
                                {t('Prix de votre centre : {prix} pour {jours} jours.', { prix: montant(centre.prix_acces, centre.devise, locale), jours: centre.duree_acces_jours })}
                            </p>
                        )}
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {eleves.length === 0 && <p className="text-sm text-muted-foreground">{t("Aucun élève n'a encore rejoint le centre.")}</p>}
                        {eleves.map((e) => {
                            const dernier = e.acces[e.acces.length - 1];
                            return (
                                <div key={e.id} className="rounded-md border px-3 py-2 text-sm">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <span>
                                            <span className="font-medium">{e.name}</span>
                                            <span className="text-muted-foreground"> · {e.email}</span>
                                        </span>
                                        <span className="flex flex-wrap items-center gap-2">
                                            {dernier ? (
                                                <Badge>{t("Accès jusqu'au {date}", { date: jour(dernier.au, locale) })}</Badge>
                                            ) : (
                                                <span className="text-xs text-muted-foreground">{t('Limite gratuite')}</span>
                                            )}
                                            {enRevente && ouvertPour !== e.id && (
                                                <Button size="sm" variant="outline" onClick={() => setOuvertPour(e.id)}>
                                                    <KeyRound className="mr-1.5 h-4 w-4" /> {dernier ? t('Prolonger') : t('Ouvrir un accès')}
                                                </Button>
                                            )}
                                        </span>
                                    </div>
                                    {e.acces.length > 0 && (
                                        <div className="mt-2 space-y-1">
                                            {e.acces.map((a) => (
                                                <div key={a.id} className="flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground">
                                                    <span>
                                                        {a.en_cours
                                                            ? t('En cours : du {du} au {au}', { du: jour(a.du, locale), au: jour(a.au, locale) })
                                                            : t('À venir : du {du} au {au}', { du: jour(a.du, locale), au: jour(a.au, locale) })}
                                                    </span>
                                                    <button type="button" className="font-medium text-red-600 hover:underline dark:text-red-400" onClick={() => couper(a, e)}>
                                                        {t('Couper')}
                                                    </button>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                    {ouvertPour === e.id && (
                                        <FormulaireOuverture eleve={e} centre={centre} moyens={moyens} onFermer={() => setOuvertPour(null)} />
                                    )}
                                </div>
                            );
                        })}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
