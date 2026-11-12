import { Link } from '@inertiajs/react';
import { useState } from 'react';
import KidsLayout, { Drapeau, type ProfilEnfant, RENARD, Renard, TXT, Visuel } from '@/layouts/kids-layout';

interface Theme { slug: string; nom: string; icone: string; illustration?: string | null; couleur: string; etoiles: number; max: number; ouvert: boolean; verrou: 'etoiles' | 'plus' | null }

/**
 * La carte : les douze themes sur un chemin qui serpente. Le renard attend sur
 * le premier theme ouvert qui n'est pas encore reussi.
 */
export default function Carte({ enfant, themes }: { enfant: ProfilEnfant; themes: Theme[] }) {
    const t = TXT[enfant.aide];
    const [bulle, setBulle] = useState<string | null>(null);
    const ici = themes.findIndex((th) => th.ouvert && th.etoiles < 4);
    const position = ici === -1 ? themes.filter((th) => th.ouvert).length - 1 : ici;

    return (
        <KidsLayout titre={t.carte} enfant={enfant}>
            <div className="mb-6 flex items-center gap-3">
                <Renard pose="carte" className="h-20 w-auto object-contain" />
                <div className="rounded-3xl rounded-bl-none bg-white px-5 py-3 shadow-md">
                    <p className="text-2xl font-black">{t.bonjour} {enfant.prenom} ! <Drapeau langue={enfant.langue} className="h-5 w-7" /></p>
                    {bulle && <p className="font-semibold text-slate-500">{bulle}</p>}
                </div>
            </div>

            <ol className="relative mx-auto max-w-md space-y-3 pb-10">
                {themes.map((th, i) => {
                    const decalage = ['translate-x-2', 'translate-x-10 sm:translate-x-16', 'translate-x-16 sm:translate-x-24', 'translate-x-10 sm:translate-x-16', 'translate-x-2', 'translate-x-0 sm:-translate-x-12'][i % 6];
                    const contenu = (
                        <div className={`flex items-center gap-4 transition ${decalage}`}>
                            <span
                                className={`relative flex h-24 w-24 shrink-0 items-center justify-center rounded-full text-5xl shadow-[0_6px_0_0_rgba(0,0,0,0.15)] ${th.ouvert ? '' : 'grayscale opacity-60'}`}
                                style={{ background: th.ouvert ? th.couleur : '#CBD5E1' }}
                            >
                                {th.ouvert ? <Visuel image={th.icone} fichier={th.illustration} className="h-20 w-20" texte="text-5xl" /> : '🔒'}
                                {i === position && <img src={RENARD} alt="" className="absolute -right-8 -top-10 h-16 w-auto object-contain" />}
                            </span>
                            <span>
                                <span className="block text-lg font-black leading-tight">{th.nom}</span>
                                <span className="text-sm font-bold text-amber-600">
                                    {'⭐'.repeat(Math.min(3, Math.floor(th.etoiles / 4)))}
                                    <span className="text-slate-400"> {th.etoiles}/{th.max}</span>
                                </span>
                            </span>
                        </div>
                    );
                    return (
                        <li key={th.slug}>
                            {th.ouvert ? (
                                <Link href={route('kids.theme', th.slug)} className="block rounded-3xl p-1 focus-visible:outline-4 focus-visible:outline-sky-400">{contenu}</Link>
                            ) : (
                                <button type="button" className="block w-full rounded-3xl p-1 text-left" onClick={() => setBulle(th.verrou === 'plus' ? t.verrouPlus : t.verrouEtoiles)}>
                                    {contenu}
                                </button>
                            )}
                        </li>
                    );
                })}
            </ol>
        </KidsLayout>
    );
}
