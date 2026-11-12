import { Link, router } from '@inertiajs/react';
import KidsLayout, { Drapeau, RENARD } from '@/layouts/kids-layout';

interface Enfant { id: number; prenom: string; avatar: string; langue: string; etoiles: number }
interface Langue { nom: string; drapeau: string }

/**
 * /kids : pour un visiteur, la presentation de PrePla Kids ; pour un parent
 * connecte, « Qui joue ? ».
 */
export default function Accueil({ connecte, enfants, langues }: { connecte: boolean; enfants: Enfant[]; langues: Record<string, Langue> }) {
    if (connecte && enfants.length > 0) {
        return (
            <KidsLayout titre="Qui joue ?">
                <div className="space-y-8 py-6 text-center">
                    <img src={RENARD} alt="" className="mx-auto h-36 w-auto object-contain drop-shadow-lg" />
                    <h1 className="text-4xl font-black tracking-tight sm:text-5xl">Qui joue ?</h1>
                    <div className="flex flex-wrap justify-center gap-5">
                        {enfants.map((e) => (
                            <button
                                key={e.id}
                                type="button"
                                onClick={() => router.post(route('kids.jouer', e.id))}
                                className="group flex w-40 flex-col items-center gap-2 rounded-[2rem] bg-white p-5 shadow-lg ring-4 ring-transparent transition hover:-translate-y-1 hover:ring-amber-300 focus-visible:ring-sky-400 focus-visible:outline-none"
                            >
                                <span className="text-7xl transition group-hover:scale-110">{e.avatar}</span>
                                <span className="text-xl font-black">{e.prenom}</span>
                                <span className="text-sm font-bold text-slate-500"><Drapeau langue={e.langue} /> ⭐ {e.etoiles}</span>
                            </button>
                        ))}
                    </div>
                    <Link href={route('kids.parents')} className="inline-block rounded-full bg-white/80 px-5 py-2.5 text-sm font-bold text-slate-600 shadow-sm">
                        👨‍👩‍👧 Espace parent
                    </Link>
                </div>
            </KidsLayout>
        );
    }

    return (
        <KidsLayout titre="Apprendre les langues en jouant">
            <section className="grid items-center gap-8 py-6 md:grid-cols-2">
                <div className="space-y-5 text-center md:text-left">
                    <h1 className="text-4xl font-black leading-tight tracking-tight sm:text-5xl">
                        L'anglais, le français et l'allemand <span className="text-amber-500">en jouant</span>.
                    </h1>
                    <p className="text-lg font-medium text-slate-600">
                        Pour les enfants de 6 à 12 ans : des mots illustrés qui parlent, des jeux d'écoute et de mémoire, des étoiles à gagner. Les parents suivent les progrès.
                    </p>
                    <div className="flex flex-wrap justify-center gap-2 text-2xl md:justify-start">
                        {Object.entries(langues).map(([cle, l]) => (
                            <span key={cle} className="flex items-center gap-2 rounded-full bg-white px-4 py-2 text-base font-bold shadow-sm"><Drapeau langue={cle} /> {l.nom}</span>
                        ))}
                    </div>
                    <div className="flex flex-col gap-3 sm:flex-row sm:justify-center md:justify-start">
                        <Link href={route('kids.inscription')} className="flex h-14 items-center justify-center rounded-2xl bg-amber-400 px-6 text-lg font-black text-white shadow-[0_5px_0_0_#d97706] transition active:translate-y-1 active:shadow-none">
                            {connecte ? 'Ajouter mon enfant' : 'Créer mon compte parent'}
                        </Link>
                        {!connecte && (
                            <Link href={route('kids.parents')} className="flex h-14 items-center justify-center rounded-2xl bg-white px-6 text-lg font-bold text-slate-700 shadow-sm">
                                J'ai déjà un compte
                            </Link>
                        )}
                    </div>
                    <p className="text-sm text-slate-500">Les 3 premiers thèmes sont gratuits. L'enfant joue sans e-mail ni mot de passe.</p>
                </div>
                <img src={RENARD} alt="Le renard de PrePla, un livre sous le bras" className="mx-auto h-80 w-auto object-contain drop-shadow-xl md:h-[26rem]" />
            </section>

            <section className="grid gap-4 py-6 sm:grid-cols-3">
                {[
                    ['🎧', 'Écouter et répéter', 'Chaque mot est prononcé par une vraie voix : l\'enfant entend la bonne prononciation dès le début.'],
                    ['🎮', 'Des jeux, pas des leçons', 'Écoute et touche, lis et trouve, memory : 12 thèmes, de la famille aux transports.'],
                    ['👨‍👩‍👧', 'Les parents suivent', 'Mots appris, temps de jeu, mots à revoir : vous voyez où en est chaque enfant.'],
                ].map(([icone, titre, texte]) => (
                    <div key={titre} className="rounded-3xl bg-white p-6 shadow-md">
                        <div className="text-5xl">{icone}</div>
                        <h2 className="mt-3 text-xl font-black">{titre}</h2>
                        <p className="mt-1 text-slate-600">{texte}</p>
                    </div>
                ))}
            </section>
        </KidsLayout>
    );
}
