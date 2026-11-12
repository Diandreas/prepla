import { Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import KidsLayout, { Drapeau } from '@/layouts/kids-layout';

interface Bilan {
    etoiles: number;
    serie: number;
    mots_appris: number;
    mots_total: number;
    themes_finis: number;
    parties_semaine: number;
    minutes_semaine: number;
    derniere: string | null;
    a_revoir: string[];
}
interface Enfant { id: number; prenom: string; age: number; avatar: string; langue: string; langue_aide: 'fr' | 'en'; bilan: Bilan }
interface Langue { nom: string; drapeau: string }
type Donnees = { prenom: string; age: string; avatar: string; langue: string; langue_aide: 'fr' | 'en' };

function Formulaire({ enfant, avatars, langues, onFini }: { enfant?: Enfant; avatars: string[]; langues: Record<string, Langue>; onFini: () => void }) {
    const form = useForm<Donnees>({
        prenom: enfant?.prenom ?? '', age: String(enfant?.age ?? 7), avatar: enfant?.avatar ?? avatars[0],
        langue: enfant?.langue ?? 'english', langue_aide: enfant?.langue_aide ?? 'fr',
    });
    const envoyer = (e: React.FormEvent) => {
        e.preventDefault();
        const o = { preserveScroll: true, onSuccess: onFini };
        if (enfant) form.put(route('kids.enfants.update', enfant.id), o);
        else form.post(route('kids.enfants.store'), o);
    };
    const pastille = (actif: boolean) => `rounded-2xl border-2 px-3 py-2 font-bold transition ${actif ? 'border-sky-400 bg-sky-50' : 'border-slate-200 bg-white'}`;

    return (
        <form onSubmit={envoyer} className="space-y-4">
            <div className="grid gap-3 sm:grid-cols-2">
                <label className="block">
                    <span className="text-sm font-bold text-slate-600">Prénom</span>
                    <input required maxLength={40} value={form.data.prenom} onChange={(e) => form.setData('prenom', e.target.value)} className="mt-1 h-12 w-full rounded-2xl border-2 border-slate-200 px-3 font-bold" />
                    {form.errors.prenom && <span className="text-xs text-rose-600">{form.errors.prenom}</span>}
                </label>
                <label className="block">
                    <span className="text-sm font-bold text-slate-600">Âge</span>
                    <input required type="number" min={3} max={14} value={form.data.age} onChange={(e) => form.setData('age', e.target.value)} className="mt-1 h-12 w-full rounded-2xl border-2 border-slate-200 px-3 font-bold" />
                </label>
            </div>
            <fieldset>
                <legend className="text-sm font-bold text-slate-600">Son animal</legend>
                <div className="mt-1 flex flex-wrap gap-2">
                    {avatars.map((a) => (
                        <button key={a} type="button" aria-pressed={form.data.avatar === a} onClick={() => form.setData('avatar', a)} className={`${pastille(form.data.avatar === a)} text-3xl`}>{a}</button>
                    ))}
                </div>
            </fieldset>
            <fieldset>
                <legend className="text-sm font-bold text-slate-600">Langue à apprendre</legend>
                <div className="mt-1 flex flex-wrap gap-2">
                    {Object.entries(langues).map(([cle, l]) => (
                        <button key={cle} type="button" aria-pressed={form.data.langue === cle} onClick={() => form.setData('langue', cle)} className={pastille(form.data.langue === cle)}><Drapeau langue={cle} /> {l.nom}</button>
                    ))}
                </div>
            </fieldset>
            <fieldset>
                <legend className="text-sm font-bold text-slate-600">Langue des consignes (celle que l'enfant comprend déjà)</legend>
                <div className="mt-1 flex gap-2">
                    {([['fr', 'Français'], ['en', 'English']] as const).map(([cle, nom]) => (
                        <button key={cle} type="button" aria-pressed={form.data.langue_aide === cle} onClick={() => form.setData('langue_aide', cle)} className={pastille(form.data.langue_aide === cle)}><Drapeau langue={cle} /> {nom}</button>
                    ))}
                </div>
            </fieldset>
            <div className="flex gap-2">
                <button type="button" onClick={onFini} className="h-12 rounded-2xl bg-slate-100 px-5 font-bold text-slate-600">Annuler</button>
                <button type="submit" disabled={form.processing} className="h-12 flex-1 rounded-2xl bg-sky-500 font-black text-white">{enfant ? 'Enregistrer' : 'Ajouter'}</button>
            </div>
        </form>
    );
}

/** L'espace parent : ajouter ses enfants, voir leurs progres, les faire jouer. */
export default function Parents({ enfants, langues, avatars, premium, gratuits, nbThemes }: {
    enfants: Enfant[]; langues: Record<string, Langue>; avatars: string[]; premium: boolean; gratuits: number; nbThemes: number;
}) {
    const flash = (usePage().props as { flash?: { success?: string } }).flash;
    const [ajout, setAjout] = useState(enfants.length === 0);
    const [edition, setEdition] = useState<number | null>(null);
    const supprimer = (e: Enfant) => {
        if (window.confirm(`Supprimer le profil de ${e.prenom} et toutes ses parties ? C'est définitif.`)) router.delete(route('kids.enfants.destroy', e.id), { preserveScroll: true });
    };

    return (
        <KidsLayout titre="Espace parent">
            <div className="space-y-6">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 className="text-3xl font-black tracking-tight">Espace parent</h1>
                        <p className="font-medium text-slate-600">Ajoutez vos enfants, puis laissez-les jouer : vous suivez ici leurs progrès.</p>
                    </div>
                    {enfants.length > 0 && (
                        <Link href={route('kids.accueil')} className="flex h-12 items-center rounded-2xl bg-amber-400 px-5 font-black text-white shadow-[0_4px_0_0_#d97706]">▶ Faire jouer un enfant</Link>
                    )}
                </div>

                {flash?.success && <p role="status" className="rounded-2xl bg-emerald-100 p-3 font-bold text-emerald-800">{flash.success}</p>}

                {!premium && (
                    <div className="flex flex-wrap items-center justify-between gap-3 rounded-3xl bg-white p-4 shadow-sm">
                        <p className="font-medium text-slate-700">
                            Les {gratuits} premiers thèmes sont gratuits. <strong>PrePla Plus</strong> ouvre les {nbThemes} thèmes pour tous vos enfants.
                        </p>
                        <Link href={route('subscription.index')} className="rounded-2xl bg-slate-800 px-4 py-2.5 text-sm font-bold text-white">Débloquer</Link>
                    </div>
                )}

                {enfants.map((e) => (
                    <div key={e.id} className="space-y-4 rounded-3xl bg-white p-5 shadow-md">
                        <div className="flex flex-wrap items-center gap-4">
                            <span className="text-6xl">{e.avatar}</span>
                            <div className="min-w-0 flex-1">
                                <p className="text-2xl font-black">{e.prenom} <span className="text-base font-bold text-slate-400">{e.age} ans</span></p>
                                <p className="font-semibold text-slate-500">Apprend : <Drapeau langue={e.langue} /> {langues[e.langue]?.nom}</p>
                            </div>
                            <div className="flex w-full gap-2 sm:w-auto">
                                <button type="button" onClick={() => router.post(route('kids.jouer', e.id))} className="h-11 flex-1 rounded-2xl bg-sky-500 px-4 font-black text-white sm:flex-none">▶ Jouer</button>
                                <button type="button" onClick={() => setEdition(edition === e.id ? null : e.id)} className="h-11 rounded-2xl bg-slate-100 px-4 font-bold text-slate-600">Modifier</button>
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                            {[
                                ['⭐ Étoiles', String(e.bilan.etoiles)],
                                ['📚 Mots appris', `${e.bilan.mots_appris} / ${e.bilan.mots_total}`],
                                ['🎮 Parties (7 j)', String(e.bilan.parties_semaine)],
                                ['⏱️ Temps (7 j)', `${e.bilan.minutes_semaine} min`],
                            ].map(([l, v]) => (
                                <div key={l} className="rounded-2xl bg-sky-50 p-3">
                                    <p className="text-xs font-bold text-slate-500">{l}</p>
                                    <p className="text-xl font-black">{v}</p>
                                </div>
                            ))}
                        </div>
                        <p className="text-sm text-slate-600">
                            {e.bilan.derniere ? <>Dernière partie : {new Date(e.bilan.derniere).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long' })}. </> : <>Pas encore joué. </>}
                            {e.bilan.serie > 1 && <>🔥 {e.bilan.serie} jours de suite. </>}
                            {e.bilan.themes_finis > 0 && <>{e.bilan.themes_finis} thème(s) réussi(s).</>}
                        </p>
                        {e.bilan.a_revoir.length > 0 && (
                            <p className="text-sm"><span className="font-bold text-slate-700">À revoir ensemble :</span> {e.bilan.a_revoir.join(', ')}</p>
                        )}
                        {edition === e.id && (
                            <div className="space-y-3 rounded-2xl border-2 border-slate-100 p-4">
                                <Formulaire enfant={e} avatars={avatars} langues={langues} onFini={() => setEdition(null)} />
                                <button type="button" onClick={() => supprimer(e)} className="text-sm font-bold text-rose-600">Supprimer ce profil</button>
                            </div>
                        )}
                    </div>
                ))}

                {ajout ? (
                    <div className="rounded-3xl bg-white p-5 shadow-md">
                        <h2 className="mb-3 text-xl font-black">Ajouter un enfant</h2>
                        <Formulaire avatars={avatars} langues={langues} onFini={() => setAjout(false)} />
                    </div>
                ) : (
                    enfants.length < 6 && (
                        <button type="button" onClick={() => setAjout(true)} className="w-full rounded-3xl border-4 border-dashed border-white bg-white/40 p-5 text-lg font-black text-slate-600">
                            + Ajouter un enfant
                        </button>
                    )
                )}

                <p className="text-center text-xs text-slate-500">
                    Nous ne gardons de vos enfants que leur prénom, leur âge et leurs parties. Ils jouent depuis votre compte, sans e-mail ni mot de passe.
                </p>
            </div>
        </KidsLayout>
    );
}
