import { Head, Link, router } from '@inertiajs/react';
import * as Flags from 'country-flag-icons/react/3x2';
import { type ReactNode, useMemo, useState } from 'react';

export interface ProfilEnfant {
    prenom: string;
    avatar: string;
    langue: string;
    aide: 'fr' | 'en';
    etoiles: number;
    serie: number;
    drapeau: string;
}

export const RENARD = '/illustrations/prepla-guide/welcome.png';

/** Un mot ou un theme : l'illustration dessinee si elle existe, sinon l'emoji. */
export function Visuel({ image, fichier, className = 'h-24 w-24', texte = 'text-6xl' }: { image: string; fichier?: string | null; className?: string; texte?: string }) {
    return fichier
        ? <img src={fichier} alt="" draggable={false} className={`${className} object-contain`} />
        : <span className={`${texte} leading-none`}>{image}</span>;
}

/**
 * Le renard dans une pose (bravo, encore, ecoute, carte...) : /illustrations/prepla-kids/renard/{pose}.webp.
 * Tant que la pose n'a pas ete dessinee, c'est le renard qui salue.
 */
export function Renard({ pose, className }: { pose: string; className?: string }) {
    return (
        <img
            src={`/illustrations/prepla-kids/renard/${pose}.webp`}
            alt=""
            className={className}
            onError={(e) => { if (!e.currentTarget.src.endsWith(RENARD)) e.currentTarget.src = RENARD; }}
        />
    );
}

/** Les emojis drapeaux ne s'affichent pas sous Windows : une vraie image. */
const PAYS: Record<string, string> = { english: 'GB', french: 'FR', german: 'DE', fr: 'FR', en: 'GB' };
export function Drapeau({ langue, className = 'h-4 w-6' }: { langue: string; className?: string }) {
    const Flag = (Flags as Record<string, React.ComponentType<{ className?: string }>>)[PAYS[langue] ?? 'GB'];
    return Flag ? <Flag className={`inline-block rounded-sm align-[-2px] shadow-sm ${className}`} /> : null;
}

/** Les petits textes de l'interface, dans la langue que l'enfant comprend deja. */
export const TXT = {
    fr: {
        parents: 'Parents', bonjour: 'Bonjour', etoiles: 'étoiles', jours: 'jours de suite', retour: 'Retour', carte: 'Ma carte',
        demandeParent: 'Demande à un adulte', combien: 'Combien font', valider: 'Valider', annuler: 'Annuler', faux: "Ce n'est pas ça. Demande à un adulte !",
        bravo: 'Bravo !', superbe: 'Superbe !', encore: 'Encore un effort !', rejouer: 'Rejouer', suivant: 'Jeu suivant', retourTheme: 'Retour au thème',
        nouvelles: 'nouvelle(s) étoile(s) !', ecoute: 'Écoute et touche la bonne image', lis: 'Lis le mot et touche la bonne image',
        paires: "Retourne les cartes et trouve les paires : l'image et son mot", decouvre: 'Touche la carte pour entendre le mot',
        reecouter: 'Réécouter', suivantMot: 'Suivant', fini: "J'ai fini !", verrouEtoiles: 'Gagne des étoiles au thème d’avant pour ouvrir celui-ci',
        verrouPlus: 'Demande à tes parents de débloquer ce thème', choisis: 'Choisis un jeu', mots: 'Les mots du thème', essais: 'essais',
        dis: 'Écoute bien, puis dis le mot au micro', appuie: 'Appuie et parle', jecoute: "Je t'écoute…", reflechis: 'Je réfléchis…',
        reessaie: 'Essaie encore !', entendu: "J'ai entendu", passer: 'Passer', onContinue: 'On continue !',
        micro: "Le micro ne marche pas. Demande à un adulte de l'autoriser, ou passe ce jeu.", panne: "Oups, je n'ai pas pu t'écouter. Réessaie !", silence: "Je n'ai rien entendu. Parle plus fort, tout près du micro !",
    },
    en: {
        parents: 'Parents', bonjour: 'Hello', etoiles: 'stars', jours: 'days in a row', retour: 'Back', carte: 'My map',
        demandeParent: 'Ask a grown-up', combien: 'What is', valider: 'OK', annuler: 'Cancel', faux: 'Not quite. Ask a grown-up!',
        bravo: 'Well done!', superbe: 'Amazing!', encore: 'Keep trying!', rejouer: 'Play again', suivant: 'Next game', retourTheme: 'Back to the topic',
        nouvelles: 'new star(s)!', ecoute: 'Listen and tap the right picture', lis: 'Read the word and tap the right picture',
        paires: 'Turn the cards and find the pairs: the picture and its word', decouvre: 'Tap the card to hear the word',
        reecouter: 'Listen again', suivantMot: 'Next', fini: "I'm done!", verrouEtoiles: 'Win stars in the previous topic to open this one',
        verrouPlus: 'Ask your parents to unlock this topic', choisis: 'Choose a game', mots: 'Words in this topic', essais: 'tries',
        dis: 'Listen carefully, then say the word into the microphone', appuie: 'Tap and speak', jecoute: "I'm listening…", reflechis: 'Thinking…',
        reessaie: 'Try again!', entendu: 'I heard', passer: 'Skip', onContinue: "Let's go on!",
        micro: "The microphone isn't working. Ask a grown-up to allow it, or skip this game.", panne: "Oops, I couldn't hear you. Try again!", silence: "I didn't hear anything. Speak louder, close to the microphone!",
    },
};

/**
 * Sortir du mode enfant demande un adulte : une multiplication qu'un enfant de
 * 6 ans ne sait pas encore faire. Pas un mot de passe : juste un garde-fou
 * pour que l'enfant ne tombe pas sur l'espace parent en jouant.
 */
function PorteParent({ aide, onFermer }: { aide: 'fr' | 'en'; onFermer: () => void }) {
    const t = TXT[aide];
    const [a, b] = useMemo(() => [6 + Math.floor(Math.random() * 4), 6 + Math.floor(Math.random() * 4)], []);
    const [rep, setRep] = useState('');
    const [erreur, setErreur] = useState(false);
    const valider = (e: React.FormEvent) => {
        e.preventDefault();
        if (Number(rep) === a * b) router.post(route('kids.quitter'));
        else { setErreur(true); setRep(''); }
    };
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" role="dialog" aria-modal="true">
            <form onSubmit={valider} className="w-full max-w-sm space-y-4 rounded-3xl bg-white p-6 text-center shadow-2xl">
                <p className="text-sm font-bold uppercase tracking-wider text-slate-500">{t.demandeParent}</p>
                <p className="text-3xl font-black text-slate-800">{t.combien} {a} × {b} ?</p>
                <input autoFocus inputMode="numeric" value={rep} onChange={(e) => setRep(e.target.value.replace(/\D/g, ''))} aria-label="Réponse"
                    className="h-14 w-full rounded-2xl border-2 border-slate-200 text-center text-2xl font-black focus:border-sky-400 focus:outline-none" />
                {erreur && <p className="text-sm font-semibold text-rose-600">{t.faux}</p>}
                <div className="flex gap-2">
                    <button type="button" onClick={onFermer} className="h-12 flex-1 rounded-2xl bg-slate-100 font-bold text-slate-600">{t.annuler}</button>
                    <button type="submit" className="h-12 flex-1 rounded-2xl bg-sky-500 font-black text-white">{t.valider}</button>
                </div>
            </form>
        </div>
    );
}

export default function KidsLayout({ titre, enfant, children, retour }: { titre: string; enfant?: ProfilEnfant | null; children: ReactNode; retour?: string }) {
    const [porte, setPorte] = useState(false);
    const aide = enfant?.aide ?? 'fr';
    const t = TXT[aide];

    return (
        <div className="min-h-screen bg-gradient-to-b from-sky-200 via-sky-100 to-amber-50 font-sans text-slate-800">
            <Head title={`${titre} · PrePla Kids`} />
            <header className="sticky top-0 z-30 border-b border-white/60 bg-white/70 backdrop-blur">
                <div className="mx-auto flex h-16 max-w-5xl items-center gap-3 px-4">
                    {retour ? (
                        <Link href={retour} className="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-2xl shadow-sm" aria-label={t.retour}>←</Link>
                    ) : (
                        <img src={RENARD} alt="" className="h-12 w-12 object-contain" />
                    )}
                    <span className={`whitespace-nowrap text-lg font-black tracking-tight ${enfant ? 'hidden sm:inline' : ''}`}>
                        PrePla <span className="rounded-lg bg-amber-400 px-1.5 text-white">Kids</span>
                    </span>
                    {enfant && (
                        <div className="ml-auto flex items-center gap-2">
                            <span className="flex items-center gap-1 whitespace-nowrap rounded-full bg-amber-100 px-3 py-1.5 text-sm font-black text-amber-700" title={t.etoiles}>⭐ {enfant.etoiles}</span>
                            {enfant.serie > 0 && <span className="hidden items-center gap-1 rounded-full bg-orange-100 px-3 py-1.5 text-sm font-black text-orange-700 sm:flex" title={t.jours}>🔥 {enfant.serie}</span>}
                            <span className="flex h-10 w-10 items-center justify-center rounded-full bg-white text-2xl shadow-sm" title={enfant.prenom}>{enfant.avatar}</span>
                            <button type="button" onClick={() => setPorte(true)} className="h-10 whitespace-nowrap rounded-full bg-slate-800 px-3 text-xs font-bold text-white" aria-label={t.parents}>🔒<span className="hidden sm:inline"> {t.parents}</span></button>
                        </div>
                    )}
                </div>
            </header>
            <main className="mx-auto max-w-5xl px-4 py-6">{children}</main>
            {porte && <PorteParent aide={aide} onFermer={() => setPorte(false)} />}
        </div>
    );
}
