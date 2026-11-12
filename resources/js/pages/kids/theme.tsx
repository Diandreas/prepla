import { Link } from '@inertiajs/react';
import KidsLayout, { type ProfilEnfant, TXT, Visuel } from '@/layouts/kids-layout';

interface Activite { slug: string; nom: string; icone: string; etoiles: number }
interface Mot { image: string; fichier?: string | null; mot: string; aide: string | null }

/** Un theme : ses quatre jeux et leurs etoiles, puis les mots a decouvrir. */
export default function Theme({ enfant, theme, activites, mots }: {
    enfant: ProfilEnfant; theme: { slug: string; nom: string; icone: string; illustration?: string | null; couleur: string }; activites: Activite[]; mots: Mot[];
}) {
    const t = TXT[enfant.aide];

    return (
        <KidsLayout titre={theme.nom} enfant={enfant} retour={route('kids.carte')}>
            <div className="space-y-6">
                <div className="flex items-center gap-4 rounded-[2rem] p-5 text-white shadow-lg" style={{ background: theme.couleur }}>
                    <Visuel image={theme.icone} fichier={theme.illustration} className="h-20 w-20" texte="text-6xl" />
                    <h1 className="text-3xl font-black drop-shadow-sm sm:text-4xl">{theme.nom}</h1>
                </div>

                <p className="text-lg font-black text-slate-600">{t.choisis}</p>
                <div className="grid gap-4 sm:grid-cols-2">
                    {activites.map((a) => (
                        <Link
                            key={a.slug}
                            href={route('kids.jeu', [theme.slug, a.slug])}
                            className="flex items-center gap-4 rounded-3xl bg-white p-5 shadow-[0_6px_0_0_rgba(0,0,0,0.08)] transition hover:-translate-y-0.5 active:translate-y-1 active:shadow-none"
                        >
                            <span className="flex h-16 w-16 items-center justify-center rounded-2xl bg-sky-100 text-4xl">{a.icone}</span>
                            <span className="flex-1">
                                <span className="block text-xl font-black">{a.nom}</span>
                                <span className="text-2xl tracking-wider">
                                    {[0, 1, 2].map((i) => <span key={i} className={i < a.etoiles ? '' : 'opacity-20 grayscale'}>⭐</span>)}
                                </span>
                            </span>
                            <span className="text-3xl text-slate-300">▶</span>
                        </Link>
                    ))}
                </div>

                <p className="text-lg font-black text-slate-600">{t.mots}</p>
                <div className="flex flex-wrap gap-2">
                    {mots.map((m) => (
                        <span key={m.mot} className="flex items-center gap-2 rounded-2xl bg-white/80 px-3 py-2 font-bold shadow-sm">
                            <Visuel image={m.image} fichier={m.fichier} className="h-8 w-8" texte="text-2xl" /> {m.mot}
                        </span>
                    ))}
                </div>
            </div>
        </KidsLayout>
    );
}
