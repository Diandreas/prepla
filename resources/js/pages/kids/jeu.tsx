import { Link } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import KidsLayout, { type ProfilEnfant, RENARD, Renard, TXT, Visuel } from '@/layouts/kids-layout';
import { getCachedTtsUrl, normalizeTtsLang, rememberTtsUrl } from '@/lib/tts-cache';

interface Mot { image: string; fichier?: string | null; mot: string; aide: string | null }
type Activite = 'decouvrir' | 'ecoute' | 'lis' | 'paires';
interface Fin { etoiles: number; gagnees: number; total: number; serie: number }

const ORDRE: Activite[] = ['decouvrir', 'ecoute', 'lis', 'paires'];

function melanger<T>(liste: T[]): T[] {
    const a = [...liste];
    for (let i = a.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
}

function csrf(): string {
    const c = document.cookie.split('; ').find((x) => x.startsWith('XSRF-TOKEN='));
    return c ? decodeURIComponent(c.split('=')[1]) : '';
}

/** Deux petites notes : montante quand c'est juste, basse et douce quand c'est faux. */
function son(juste: boolean) {
    try {
        const Ctx = window.AudioContext || (window as unknown as { webkitAudioContext: typeof AudioContext }).webkitAudioContext;
        const ctx = new Ctx();
        const notes = juste ? [660, 880] : [220, 180];
        notes.forEach((f, i) => {
            const o = ctx.createOscillator(), g = ctx.createGain();
            o.type = juste ? 'triangle' : 'sine';
            o.frequency.value = f;
            g.gain.setValueAtTime(0.0001, ctx.currentTime + i * 0.12);
            g.gain.exponentialRampToValueAtTime(0.25, ctx.currentTime + i * 0.12 + 0.02);
            g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + i * 0.12 + 0.22);
            o.connect(g).connect(ctx.destination);
            o.start(ctx.currentTime + i * 0.12);
            o.stop(ctx.currentTime + i * 0.12 + 0.25);
        });
        setTimeout(() => ctx.close(), 800);
    } catch { /* pas de son : le jeu continue */ }
}

/** Prononce un mot : la voix du serveur (mise en cache), sinon celle du navigateur. */
async function chercherVoix(texte: string, lang: string): Promise<string | null> {
    const deja = getCachedTtsUrl(texte, lang);
    if (deja) return deja;
    try {
        const res = await fetch(route('kids.voix'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ text: texte, lang: normalizeTtsLang(lang) }),
        });
        const data = res.ok ? await res.json() : null;
        if (typeof data?.audio_url === 'string') { rememberTtsUrl(texte, lang, data.audio_url); return data.audio_url; }
    } catch { /* la voix du navigateur prendra le relais */ }
    return null;
}

function useVoix(lang: string, textes: string[]) {
    const audio = useRef<HTMLAudioElement | null>(null);
    // Les mots du theme sont prepares a l'avance, un a un : le premier toucher parle tout de suite.
    useEffect(() => {
        let annule = false;
        (async () => { for (const tx of textes) { if (annule) break; await chercherVoix(tx, lang); } })();
        return () => { annule = true; };
    }, [lang, textes]);

    return useCallback(async (texte: string) => {
        audio.current?.pause();
        const url = await chercherVoix(texte, lang);
        if (url) {
            audio.current = new Audio(url);
            audio.current.play().catch(() => {});
            return;
        }
        if ('speechSynthesis' in window) {
            const u = new SpeechSynthesisUtterance(texte);
            u.lang = { en: 'en-GB', fr: 'fr-FR', de: 'de-DE' }[normalizeTtsLang(lang)] ?? 'en-GB';
            u.rate = 0.85;
            window.speechSynthesis.cancel();
            window.speechSynthesis.speak(u);
        }
    }, [lang]);
}

function Etoiles({ n, grand = false }: { n: number; grand?: boolean }) {
    return (
        <span className={grand ? 'text-6xl' : 'text-2xl'}>
            {[0, 1, 2].map((i) => (
                <span key={i} className={`inline-block transition ${i < n ? 'animate-[bounce_1s_ease-in-out_2]' : 'opacity-20 grayscale'}`} style={{ animationDelay: `${i * 150}ms` }}>⭐</span>
            ))}
        </span>
    );
}

// ---------------------------------------------------------------- Decouvre
function Decouvrir({ mots, dire, t, onFin }: { mots: Mot[]; dire: (s: string) => void; t: typeof TXT.fr; onFin: (score: number, rates: string[]) => void }) {
    const [i, setI] = useState(0);
    const m = mots[i];
    useEffect(() => { dire(m.mot); }, [i]); // eslint-disable-line react-hooks/exhaustive-deps

    return (
        <div className="space-y-5 text-center">
            <p className="text-lg font-bold text-slate-600">{t.decouvre}</p>
            <button type="button" onClick={() => dire(m.mot)} className="mx-auto flex w-full max-w-sm flex-col items-center gap-3 rounded-[2.5rem] bg-white p-8 shadow-xl transition active:scale-95">
                <Visuel image={m.image} fichier={m.fichier} className="h-44 w-44" texte="text-[8rem]" />
                <span className="text-4xl font-black">{m.mot}</span>
                {m.aide && <span className="text-lg font-semibold text-slate-400">{m.aide}</span>}
                <span className="rounded-full bg-sky-100 px-4 py-1.5 text-sm font-bold text-sky-700">🔊 {t.reecouter}</span>
            </button>
            <div className="flex justify-center gap-1.5">
                {mots.map((_, k) => <span key={k} className={`h-2.5 w-2.5 rounded-full ${k <= i ? 'bg-sky-500' : 'bg-white'}`} />)}
            </div>
            <button
                type="button"
                onClick={() => (i < mots.length - 1 ? setI(i + 1) : onFin(100, []))}
                className="h-14 rounded-2xl bg-sky-500 px-10 text-xl font-black text-white shadow-[0_5px_0_0_#0369a1] active:translate-y-1 active:shadow-none"
            >
                {i < mots.length - 1 ? `${t.suivantMot} →` : t.fini}
            </button>
        </div>
    );
}

// ---------------------------------------------------------------- Ecoute / Lis
function Quiz({ mots, dire, t, lecture, onFin }: { mots: Mot[]; dire: (s: string) => void; t: typeof TXT.fr; lecture: boolean; onFin: (score: number, rates: string[]) => void }) {
    const tours = useMemo(() => melanger(mots).map((cible) => ({ cible, choix: melanger([cible, ...melanger(mots.filter((x) => x.mot !== cible.mot)).slice(0, 3)]) })), [mots]);
    const [i, setI] = useState(0);
    const [rates, setRates] = useState<string[]>([]);
    const [faux, setFaux] = useState<string | null>(null);
    const [juste, setJuste] = useState<string | null>(null);
    const tour = tours[i];
    useEffect(() => { if (!lecture) dire(tour.cible.mot); }, [i]); // eslint-disable-line react-hooks/exhaustive-deps

    const choisir = (m: Mot) => {
        if (juste) return;
        if (m.mot === tour.cible.mot) {
            son(true);
            setJuste(m.mot);
            if (lecture) dire(m.mot);
            setTimeout(() => {
                setJuste(null); setFaux(null);
                if (i < tours.length - 1) setI(i + 1);
                else {
                    const manques = rates.length;
                    onFin(Math.round(((tours.length - manques) / tours.length) * 100), rates);
                }
            }, 900);
        } else {
            son(false);
            setFaux(m.mot);
            if (!rates.includes(tour.cible.mot)) setRates([...rates, tour.cible.mot]);
            setTimeout(() => setFaux(null), 600);
        }
    };

    return (
        <div className="space-y-6 text-center">
            <p className="text-lg font-bold text-slate-600">{lecture ? t.lis : t.ecoute}</p>
            <div className="h-3 overflow-hidden rounded-full bg-white">
                <div className="h-full rounded-full bg-emerald-400 transition-all" style={{ width: `${(i / tours.length) * 100}%` }} />
            </div>
            {lecture ? (
                <p className="text-5xl font-black tracking-tight sm:text-6xl">{tour.cible.mot}</p>
            ) : (
                <button type="button" onClick={() => dire(tour.cible.mot)} className="mx-auto flex h-28 w-28 items-center justify-center rounded-full bg-sky-500 text-6xl text-white shadow-[0_6px_0_0_#0369a1] active:translate-y-1 active:shadow-none" aria-label={t.reecouter}>
                    🔊
                </button>
            )}
            <div className="mx-auto grid max-w-lg grid-cols-2 gap-4">
                {tour.choix.map((m) => (
                    <button
                        key={m.mot}
                        type="button"
                        onClick={() => choisir(m)}
                        className={`flex aspect-square items-center justify-center rounded-[2rem] bg-white p-3 shadow-[0_6px_0_0_rgba(0,0,0,0.1)] transition active:scale-95 ${juste === m.mot ? 'scale-105 ring-8 ring-emerald-400' : ''} ${faux === m.mot ? 'animate-[shake_0.4s] ring-8 ring-rose-300' : ''}`}
                    >
                        <Visuel image={m.image} fichier={m.fichier} className="h-full w-full" texte="text-[5.5rem]" />
                    </button>
                ))}
            </div>
        </div>
    );
}

// ---------------------------------------------------------------- Memory
function Paires({ mots, dire, t, onFin }: { mots: Mot[]; dire: (s: string) => void; t: typeof TXT.fr; onFin: (score: number, rates: string[]) => void }) {
    const choisis = useMemo(() => melanger(mots).slice(0, 6), [mots]);
    const cartes = useMemo(() => melanger(choisis.flatMap((m, k) => [{ id: `i${k}`, cle: m.mot, face: m.image, fichier: m.fichier, image: true }, { id: `m${k}`, cle: m.mot, face: m.mot, fichier: null, image: false }])), [choisis]);
    const [ouvertes, setOuvertes] = useState<string[]>([]);
    const [trouvees, setTrouvees] = useState<string[]>([]);
    const [essais, setEssais] = useState(0);

    const tourner = (c: (typeof cartes)[number]) => {
        if (ouvertes.length === 2 || ouvertes.includes(c.id) || trouvees.includes(c.cle)) return;
        if (!c.image) dire(c.cle);
        const suivantes = [...ouvertes, c.id];
        setOuvertes(suivantes);
        if (suivantes.length === 2) {
            const [a, b] = suivantes.map((id) => cartes.find((x) => x.id === id)!);
            const n = essais + 1;
            setEssais(n);
            if (a.cle === b.cle) {
                son(true);
                dire(a.cle);
                const ok = [...trouvees, a.cle];
                setTrouvees(ok);
                setOuvertes([]);
                if (ok.length === choisis.length) setTimeout(() => onFin(Math.max(0, Math.min(100, 100 - (n - choisis.length) * 8)), []), 900);
            } else {
                setTimeout(() => setOuvertes([]), 1000);
            }
        }
    };

    return (
        <div className="space-y-5 text-center">
            <p className="text-lg font-bold text-slate-600">{t.paires}</p>
            <p className="text-sm font-bold text-slate-500">{essais} {t.essais}</p>
            <div className="mx-auto grid max-w-2xl grid-cols-3 gap-3 sm:grid-cols-4">
                {cartes.map((c) => {
                    const visible = ouvertes.includes(c.id) || trouvees.includes(c.cle);
                    return (
                        <button
                            key={c.id}
                            type="button"
                            onClick={() => tourner(c)}
                            className={`flex aspect-[4/5] items-center justify-center rounded-3xl p-2 shadow-[0_5px_0_0_rgba(0,0,0,0.1)] transition ${visible ? 'bg-white' : 'bg-gradient-to-br from-amber-300 to-orange-400'} ${trouvees.includes(c.cle) ? 'opacity-60 ring-4 ring-emerald-400' : ''}`}
                        >
                            {visible ? (
                                c.image ? <Visuel image={c.face} fichier={c.fichier} className="h-4/5 w-4/5" texte="text-6xl" /> : <span className="text-xl font-black leading-tight sm:text-2xl">{c.face}</span>
                            ) : (
                                <img src={RENARD} alt="" className="h-3/5 w-auto object-contain opacity-90" />
                            )}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

/**
 * Un jeu d'un theme. A la fin, la partie est envoyee au serveur, qui rend les
 * etoiles gagnees : l'ecran de fin les montre sans quitter la page.
 */
export default function Jeu({ enfant, theme, activite, mots, tts }: {
    enfant: ProfilEnfant; theme: { slug: string; nom: string; couleur: string }; activite: Activite; mots: Mot[]; tts: string;
}) {
    const t = TXT[enfant.aide];
    const textes = useMemo(() => mots.map((m) => m.mot), [mots]);
    const dire = useVoix(tts, textes);
    const debut = useRef(Date.now());
    const [partie, setPartie] = useState(0);
    const [fin, setFin] = useState<(Fin & { score: number }) | null>(null);
    const [profil, setProfil] = useState(enfant);
    const suivant = ORDRE[ORDRE.indexOf(activite) + 1];

    const terminer = async (score: number, rates: string[]) => {
        const duree = Math.round((Date.now() - debut.current) / 1000);
        try {
            const res = await fetch(route('kids.resultat'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ theme: theme.slug, activite, score, duree, mots_rates: rates }),
            });
            const data: Fin = await res.json();
            setFin({ ...data, score });
            setProfil((p) => ({ ...p, etoiles: data.total, serie: data.serie }));
        } catch {
            setFin({ etoiles: score >= 90 ? 3 : score >= 70 ? 2 : score >= 40 ? 1 : 0, gagnees: 0, total: profil.etoiles, serie: profil.serie, score });
        }
    };
    const rejouer = () => { setFin(null); debut.current = Date.now(); setPartie((n) => n + 1); };

    return (
        <KidsLayout titre={theme.nom} enfant={profil} retour={route('kids.theme', theme.slug)}>
            {fin ? (
                <div className="mx-auto max-w-md space-y-5 rounded-[2.5rem] bg-white p-8 text-center shadow-xl">
                    <Renard pose={fin.etoiles > 0 ? 'bravo' : 'encore'} className="mx-auto h-32 w-auto object-contain" />
                    <p className="text-4xl font-black">{fin.etoiles === 3 ? t.superbe : fin.etoiles > 0 ? t.bravo : t.encore}</p>
                    <Etoiles n={fin.etoiles} grand />
                    {fin.gagnees > 0 && <p className="text-lg font-black text-amber-600">+{fin.gagnees} {t.nouvelles}</p>}
                    <div className="flex flex-col gap-3">
                        {suivant && (
                            <Link href={route('kids.jeu', [theme.slug, suivant])} className="flex h-14 items-center justify-center rounded-2xl bg-amber-400 text-xl font-black text-white shadow-[0_5px_0_0_#d97706]">
                                {t.suivant} →
                            </Link>
                        )}
                        <button type="button" onClick={rejouer} className="h-14 rounded-2xl bg-sky-500 text-xl font-black text-white shadow-[0_5px_0_0_#0369a1]">↻ {t.rejouer}</button>
                        <Link href={route('kids.theme', theme.slug)} className="font-bold text-slate-500">{t.retourTheme}</Link>
                    </div>
                </div>
            ) : (
                <div key={partie}>
                    {activite === 'decouvrir' && <Decouvrir mots={mots} dire={dire} t={t} onFin={terminer} />}
                    {activite === 'ecoute' && <Quiz mots={mots} dire={dire} t={t} lecture={false} onFin={terminer} />}
                    {activite === 'lis' && <Quiz mots={mots} dire={dire} t={t} lecture onFin={terminer} />}
                    {activite === 'paires' && <Paires mots={mots} dire={dire} t={t} onFin={terminer} />}
                </div>
            )}
        </KidsLayout>
    );
}
