import { Head, router, useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { LogoDeCentre } from '@/components/app-logo';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { contraste, couleurLisible, estHexa, texteSur } from '@/lib/marque-centre.js';

interface Centre {
    id: number;
    name: string;
    display_name: string | null;
    logo_url: string | null;
    brand_primary: string | null;
    brand_accent: string | null;
    hide_prepla_branding: boolean;
}
interface GroupeDExamens { langue: string; examens: { id: number; name: string }[] }

/** Une couleur de marque : pastille, saisie hexadecimale et contraste annonce avant d'enregistrer. */
function ChampCouleur({
    id,
    libelle,
    aide,
    valeur,
    parDefaut,
    minimum,
    erreur,
    onChange,
}: {
    id: string;
    libelle: string;
    aide: string;
    valeur: string | null;
    parDefaut: string;
    minimum: number;
    erreur?: string;
    onChange: (v: string | null) => void;
}) {
    const affichee = valeur ?? parDefaut;
    const valide = estHexa(affichee);
    const rapport = valide ? contraste(affichee, '#FFFFFF') : 0;
    const lisible = couleurLisible(affichee);

    return (
        <div className="space-y-1.5">
            <Label htmlFor={id}>{libelle}</Label>
            <div className="flex flex-wrap items-center gap-2">
                <input
                    type="color"
                    aria-label={`${libelle} : choisir dans la palette`}
                    className="h-9 w-12 cursor-pointer rounded-md border bg-background p-1"
                    value={valide ? affichee.toLowerCase() : '#000000'}
                    onChange={(e) => onChange(e.target.value.toUpperCase())}
                />
                <Input
                    id={id}
                    className="w-32 font-mono uppercase"
                    maxLength={7}
                    value={valeur ?? ''}
                    placeholder={parDefaut}
                    onChange={(e) => {
                        const v = e.target.value.trim();
                        onChange(v === '' ? null : (v.startsWith('#') ? v : `#${v}`).toUpperCase());
                    }}
                />
                {valeur !== null && (
                    <Button type="button" variant="ghost" size="sm" onClick={() => onChange(null)}>
                        Couleur PrePla
                    </Button>
                )}
            </div>
            <p className="text-xs text-muted-foreground">{aide}</p>
            {valide ? (
                <p className={`text-xs ${lisible ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'}`}>
                    Contraste sur fond blanc : {rapport.toFixed(1).replace('.', ',')}:1
                    {lisible ? ' — lisible.' : ` — trop clair (minimum ${String(minimum).replace('.', ',')}:1). Choisissez une teinte plus foncée.`}
                </p>
            ) : (
                <p className="text-xs text-rose-600 dark:text-rose-400">Format attendu : #RRGGBB.</p>
            )}
            <InputError message={erreur} />
        </div>
    );
}

/**
 * La personnalisation d'un centre, pour son responsable : nom affiché, logo,
 * couleurs, marque blanche et examens proposés. Un aperçu de l'en-tête montre
 * le résultat avant d'enregistrer.
 */
export default function Personnalisation({
    centre,
    examens,
    retenus,
    defauts,
    contrasteMinimal,
    logoKoMax,
}: {
    centre: Centre;
    examens: GroupeDExamens[];
    retenus: number[];
    defauts: { primaire: string; accent: string };
    contrasteMinimal: number;
    logoKoMax: number;
}) {
    const tous = examens.flatMap((g) => g.examens.map((e) => e.id));

    const form = useForm<{
        display_name: string;
        brand_primary: string | null;
        brand_accent: string | null;
        hide_prepla_branding: boolean;
        examens: number[];
    }>({
        display_name: centre.display_name ?? '',
        brand_primary: centre.brand_primary,
        brand_accent: centre.brand_accent,
        hide_prepla_branding: centre.hide_prepla_branding,
        // Aucune sélection enregistrée : le centre propose tout, tout est coché.
        examens: retenus.length > 0 ? retenus : tous,
    });

    const [envoiLogo, setEnvoiLogo] = useState(false);
    const [erreurLogo, setErreurLogo] = useState<string | undefined>();
    const champFichier = useRef<HTMLInputElement>(null);

    const nomApercu = form.data.display_name.trim() || centre.name;
    const primaire = estHexa(form.data.brand_primary) ? form.data.brand_primary : defauts.primaire;
    const accent = estHexa(form.data.brand_accent) ? form.data.brand_accent : defauts.accent;

    function basculer(id: number, coche: boolean) {
        form.setData('examens', coche ? [...new Set([...form.data.examens, id])] : form.data.examens.filter((e) => e !== id));
    }

    function enregistrer(e: React.FormEvent) {
        e.preventDefault();
        form.patch(route('center.personnalisation.update'), { preserveScroll: true });
    }

    function envoyerLogo(fichier: File | undefined) {
        if (!fichier) return;
        setErreurLogo(undefined);
        // Vérifié aussi par le serveur : ici, on évite seulement un envoi inutile.
        if (fichier.size > logoKoMax * 1024) {
            setErreurLogo('Le logo ne doit pas dépasser 1 Mo.');
            return;
        }
        router.post(route('center.personnalisation.logo.store'), { logo: fichier }, {
            forceFormData: true,
            preserveScroll: true,
            onStart: () => setEnvoiLogo(true),
            onFinish: () => {
                setEnvoiLogo(false);
                if (champFichier.current) champFichier.current.value = '';
            },
            onError: (erreurs) => setErreurLogo(erreurs.logo),
        });
    }

    function retirerLogo() {
        router.delete(route('center.personnalisation.logo.destroy'), { preserveScroll: true });
    }

    return (
        <AppLayout>
            <Head title={`Personnalisation — ${centre.name}`} />
            <div className="mx-auto w-full max-w-4xl space-y-5 p-4 md:p-6">
                <div>
                    <p className="text-sm text-muted-foreground">{centre.name}</p>
                    <h1 className="text-2xl font-bold tracking-tight">Personnalisation</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Vos élèves et votre équipe voient votre nom, votre logo et vos couleurs dans l'application.
                    </p>
                </div>

                {/* Aperçu de l'en-tête, avec les valeurs en cours de saisie */}
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Aperçu</CardTitle>
                        <p className="text-xs text-muted-foreground">Ce que verront vos élèves en haut du menu. Rien n'est appliqué avant d'enregistrer.</p>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-background p-3">
                            <div className="flex min-w-0 max-w-xs flex-1 items-center">
                                <LogoDeCentre
                                    nom={nomApercu}
                                    logoUrl={centre.logo_url}
                                    masquerPrepla={form.data.hide_prepla_branding}
                                    primaire={primaire}
                                    texteSurPrimaire={texteSur(primaire)}
                                    accent={accent}
                                />
                            </div>
                            <span
                                className="rounded-md px-3 py-1.5 text-sm font-semibold"
                                style={{ background: primaire, color: texteSur(primaire) }}
                            >
                                Commencer l'exercice
                            </span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Logo</CardTitle>
                        <p className="text-xs text-muted-foreground">Image PNG, JPG ou WebP, 1 Mo au plus. De préférence carrée, sur fond clair. Le SVG n'est pas accepté.</p>
                    </CardHeader>
                    <CardContent className="flex flex-wrap items-center gap-3">
                        <div className="flex size-16 items-center justify-center overflow-hidden rounded-lg border bg-white">
                            {centre.logo_url ? (
                                <img src={centre.logo_url} alt={`Logo actuel de ${nomApercu}`} className="h-14 w-14 object-contain" />
                            ) : (
                                <span className="text-xs text-muted-foreground">Aucun</span>
                            )}
                        </div>
                        <input
                            ref={champFichier}
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            className="hidden"
                            onChange={(e) => envoyerLogo(e.target.files?.[0])}
                        />
                        <Button type="button" size="sm" disabled={envoiLogo} onClick={() => champFichier.current?.click()}>
                            {envoiLogo ? 'Envoi…' : centre.logo_url ? 'Remplacer le logo' : 'Ajouter un logo'}
                        </Button>
                        {centre.logo_url && (
                            <Button type="button" variant="outline" size="sm" disabled={envoiLogo} onClick={retirerLogo}>
                                Retirer
                            </Button>
                        )}
                        <InputError message={erreurLogo} className="w-full" />
                    </CardContent>
                </Card>

                <form onSubmit={enregistrer} className="space-y-5">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">Nom et couleurs</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <div className="space-y-1.5">
                                <Label htmlFor="display_name">Nom affiché</Label>
                                <Input
                                    id="display_name"
                                    maxLength={80}
                                    value={form.data.display_name}
                                    placeholder={centre.name}
                                    onChange={(e) => form.setData('display_name', e.target.value)}
                                />
                                <p className="text-xs text-muted-foreground">Laissé vide, c'est le nom du centre qui s'affiche : « {centre.name} ».</p>
                                <InputError message={form.errors.display_name} />
                            </div>

                            <div className="grid gap-5 md:grid-cols-2">
                                <ChampCouleur
                                    id="brand_primary"
                                    libelle="Couleur principale"
                                    aide="Boutons, liens et éléments actifs."
                                    valeur={form.data.brand_primary}
                                    parDefaut={defauts.primaire}
                                    minimum={contrasteMinimal}
                                    erreur={form.errors.brand_primary}
                                    onChange={(v) => form.setData('brand_primary', v)}
                                />
                                <ChampCouleur
                                    id="brand_accent"
                                    libelle="Couleur d'accent"
                                    aide="Le nom du centre dans le menu."
                                    valeur={form.data.brand_accent}
                                    parDefaut={defauts.accent}
                                    minimum={contrasteMinimal}
                                    erreur={form.errors.brand_accent}
                                    onChange={(v) => form.setData('brand_accent', v)}
                                />
                            </div>

                            <label className="flex cursor-pointer items-start gap-3 rounded-lg border p-3">
                                <Checkbox
                                    checked={form.data.hide_prepla_branding}
                                    onCheckedChange={(v) => form.setData('hide_prepla_branding', v === true)}
                                    className="mt-0.5"
                                />
                                <span className="text-sm">
                                    <span className="font-medium">Masquer la marque PrePla</span>
                                    <span className="block text-xs text-muted-foreground">
                                        Votre nom remplace « PrePla » dans le logo, le titre des pages et les avis de devoirs envoyés à vos élèves.
                                        L'offre d'abonnement PrePla Plus ne leur est plus présentée.
                                    </span>
                                </span>
                            </label>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">Examens proposés</CardTitle>
                            <p className="text-xs text-muted-foreground">
                                Seuls les examens cochés apparaissent à vos élèves et à votre équipe. Chaque examen garde sa propre structure.
                            </p>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {examens.map((g) => (
                                <fieldset key={g.langue} className="space-y-2">
                                    <legend className="text-sm font-semibold">{g.langue}</legend>
                                    <div className="grid gap-2 sm:grid-cols-2">
                                        {g.examens.map((ex) => (
                                            <label key={ex.id} className="flex cursor-pointer items-center gap-2.5 rounded-md border px-3 py-2 text-sm hover:bg-muted/40">
                                                <Checkbox
                                                    checked={form.data.examens.includes(ex.id)}
                                                    onCheckedChange={(v) => basculer(ex.id, v === true)}
                                                />
                                                {ex.name}
                                            </label>
                                        ))}
                                    </div>
                                </fieldset>
                            ))}
                            <InputError message={form.errors.examens ?? Object.entries(form.errors).find(([k]) => k.startsWith('examens.'))?.[1]} />
                        </CardContent>
                    </Card>

                    <div className="flex items-center justify-end gap-3">
                        {form.recentlySuccessful && <span className="text-sm text-emerald-600 dark:text-emerald-400">Enregistré.</span>}
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Enregistrement…' : 'Enregistrer'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
