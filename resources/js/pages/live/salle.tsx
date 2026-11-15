import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Compteur from '@/components/live/compteur';

/*
 * La salle PrePla d'un live : la video est dans Google Meet (onglet a part),
 * ici tout ce qui rend le cours interactif. L'etat est relu toutes les 3 s
 * (sondage HTTP court, rien de lourd pour le serveur).
 */

interface Live {
    id: number;
    title: string;
    agenda: string | null;
    classe: string;
    starts_at: string;
    duration_minutes: number;
    statut: string;
    summary: string | null;
    recording_url: string | null;
    assignment_id: number | null;
}
interface Question { id: number; body: string; votes: number; repondue: boolean; aVote: boolean; moi: boolean; auteur: string | null }
interface Sondage { id: number; question: string; options: string[]; ferme: boolean; maReponse: number | null; quiz: boolean; correct: number | null; resultats: number[] | null; total: number }
interface Etat {
    statut: string;
    meeting: boolean;
    chat: { id: number; auteur: string | null; moi: boolean; body: string; at: string }[];
    questions: Question[];
    ressources: { id: number; titre: string; type: string; url: string }[];
    sondage: Sondage | null;
    maMain: boolean;
    mains?: { user_id: number; name: string | null }[];
    reactions?: Record<string, number>;
    connectes?: number;
    rejoints?: number;
}

const REACTIONS: [string, string, string][] = [
    ['compris', '👍', "J'ai compris"],
    ['trop_vite', '🐢', 'Trop vite'],
    ['repete', '🔁', 'Tu peux répéter ?'],
];

export default function LiveSalle({
    live,
    role,
    notes: notesInitiales,
    exercices,
    medias,
}: {
    live: Live;
    role: 'animateur' | 'eleve';
    notes: string | null;
    exercices: { id: number; titre: string }[];
    medias: { id: number; titre: string; type: string }[];
}) {
    const { t } = useTranslation();
    const L = useCallback((cle: string, fr: string, vars?: Record<string, unknown>) => t(`live.${cle}`, { defaultValue: fr, ...vars }) as string, [t]);
    const prof = role === 'animateur';

    const [etat, setEtat] = useState<Etat | null>(null);
    const [chat, setChat] = useState<Etat['chat']>([]);
    const dernier = useRef(0);

    const relire = useCallback(async () => {
        try {
            const { data } = await axios.get<Etat>(route('live.etat', live.id), { params: { depuis: dernier.current } });
            if (data.chat.length) {
                dernier.current = data.chat[data.chat.length - 1].id;
                setChat((c) => [...c, ...data.chat].slice(-200));
            }
            setEtat(data);
        } catch {
            /* reseau coupe : on reessaie au prochain tour */
        }
    }, [live.id]);

    useEffect(() => {
        relire();
        const id = window.setInterval(() => {
            if (document.visibilityState === 'visible') relire();
        }, 3000);
        return () => window.clearInterval(id);
    }, [relire]);

    const envoyer = async (url: string, donnees: object = {}) => {
        try {
            await axios.post(url, donnees);
        } finally {
            relire();
        }
    };

    const termine = etat ? ['termine', 'annule'].includes(etat.statut) : ['termine', 'annule'].includes(live.statut);
    const enCours = (etat?.statut ?? live.statut) === 'en_cours';

    // --- champs ---
    const [message, setMessage] = useState('');
    const [question, setQuestion] = useState('');
    const [notes, setNotes] = useState(notesInitiales ?? '');
    const [notesEtat, setNotesEtat] = useState<'' | 'saving' | 'saved'>('');
    const notesTimer = useRef<number | undefined>(undefined);
    function changerNotes(v: string) {
        setNotes(v);
        setNotesEtat('saving');
        window.clearTimeout(notesTimer.current);
        notesTimer.current = window.setTimeout(async () => {
            await axios.put(route('live.notes', live.id), { notes: v });
            setNotesEtat('saved');
        }, 800);
    }

    // --- sondage (enseignant) ---
    const [sq, setSq] = useState('');
    const [so, setSo] = useState<string[]>(['', '']);
    const [correct, setCorrect] = useState<number | null>(null);
    async function lancerSondage(e: React.FormEvent) {
        e.preventDefault();
        const options = so.map((o) => o.trim()).filter(Boolean);
        if (!sq.trim() || options.length < 2) return;
        await envoyer(route('live.sondage', live.id), { question: sq, options, correct: correct !== null && correct < options.length ? correct : null });
        setSq('');
        setSo(['', '']);
        setCorrect(null);
    }

    // --- ressource (enseignant) ---
    const [rType, setRType] = useState<'lien' | 'document' | 'exercice'>('lien');
    const [rUrl, setRUrl] = useState('');
    const [rTitre, setRTitre] = useState('');
    const [rId, setRId] = useState('');
    async function partager(e: React.FormEvent) {
        e.preventDefault();
        await envoyer(route('live.ressource', live.id), { type: rType, url: rUrl || null, titre: rTitre || null, id: rId ? Number(rId) : null });
        setRUrl('');
        setRTitre('');
        setRId('');
    }

    const champ = 'w-full rounded-md border border-input bg-background p-2 text-sm';

    return (
        <AppLayout>
            <Head title={live.title} />
            <div className="mx-auto w-full max-w-5xl space-y-4 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="min-w-0">
                        <p className="text-xs font-bold uppercase tracking-widest text-rose-600">● {L('titre', 'Live class')} · {live.classe}</p>
                        <h1 className="text-2xl font-bold tracking-tight">{live.title}</h1>
                        {!enCours && !termine && <Compteur debut={live.starts_at} />}
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {!termine && (etat?.meeting ?? enCours) && (
                            <Button asChild className="bg-rose-600 text-white hover:bg-rose-700">
                                <a href={route('live.rejoindre', live.id)} target="_blank" rel="noopener">{L('rejoindre', 'Rejoindre sur Google Meet')}</a>
                            </Button>
                        )}
                        {!termine && (
                            <Button variant="outline" asChild>
                                <a href={route('live.ics', live.id)}>{L('calendrier', 'Ajouter au calendrier')}</a>
                            </Button>
                        )}
                        {prof && !termine && (
                            <Button variant="ghost" className="text-rose-600" onClick={() => confirm(L('confirmer_fin', 'Terminer le live pour tout le monde ?')) && router.post(route('center.lives.terminer', live.id))}>
                                {L('terminer', 'Terminer le live')}
                            </Button>
                        )}
                        {prof && termine && (
                            <Button variant="outline" asChild><a href={route('center.lives.show', live.id)}>{L('bilan', 'Bilan du live')}</a></Button>
                        )}
                    </div>
                </div>

                {live.agenda && (
                    <Card><CardContent className="whitespace-pre-line py-3 text-sm"><span className="font-semibold">{L('agenda', 'Ordre du jour')} : </span>{live.agenda}</CardContent></Card>
                )}

                {termine && (
                    <Card>
                        <CardContent className="space-y-2 py-4 text-sm">
                            <p className="font-semibold">{L('fini', 'Ce live est terminé.')}</p>
                            {live.summary && <p className="whitespace-pre-line">{live.summary}</p>}
                            {live.recording_url && <a className="font-medium text-primary hover:underline" href={live.recording_url} target="_blank" rel="noopener">{L('replay', 'Voir le replay')}</a>}
                            {live.assignment_id && !prof && <p><a className="font-medium text-primary hover:underline" href={route('devoirs.show', live.assignment_id)}>{L('devoir', 'Faire le devoir de la séance')}</a></p>}
                        </CardContent>
                    </Card>
                )}

                {prof && etat && !termine && (
                    <div className="grid gap-3 sm:grid-cols-4">
                        <Card><CardContent className="py-3 text-sm"><p className="text-2xl font-bold">{etat.connectes ?? 0}</p>{L('connectes', 'élèves sur la page')}</CardContent></Card>
                        <Card><CardContent className="py-3 text-sm"><p className="text-2xl font-bold">{etat.rejoints ?? 0}</p>{L('rejoints', 'ont rejoint Meet')}</CardContent></Card>
                        <Card className="sm:col-span-2">
                            <CardContent className="flex justify-around py-3 text-center text-sm">
                                {REACTIONS.map(([k, e, fr]) => (
                                    <div key={k}><p className="text-2xl">{e} <span className="font-bold">{etat.reactions?.[k] ?? 0}</span></p><p className="text-xs text-muted-foreground">{L(`r_${k}`, fr)}</p></div>
                                ))}
                            </CardContent>
                        </Card>
                    </div>
                )}

                <div className="grid gap-4 lg:grid-cols-3">
                    <div className="space-y-4 lg:col-span-2">
                        {/* Sondage / quiz eclair */}
                        {etat?.sondage && (
                            <Card className="border-primary/40">
                                <CardHeader><CardTitle className="text-base">{etat.sondage.quiz ? L('quiz', 'Quiz éclair') : L('sondage', 'Sondage')} : {etat.sondage.question}</CardTitle></CardHeader>
                                <CardContent className="space-y-2">
                                    {etat.sondage.options.map((o, i) => {
                                        const n = etat.sondage!.resultats?.[i];
                                        const pct = n !== undefined && etat.sondage!.total ? Math.round((n / etat.sondage!.total) * 100) : 0;
                                        const choisi = etat.sondage!.maReponse === i;
                                        const bon = etat.sondage!.correct === i;
                                        return (
                                            <button key={i} type="button" disabled={prof || etat.sondage!.ferme}
                                                onClick={() => envoyer(route('live.sondage.repondre', [live.id, etat.sondage!.id]), { option: i })}
                                                className={`relative block w-full overflow-hidden rounded-lg border p-2 text-left text-sm ${choisi ? 'border-primary' : 'border-border'} ${bon ? 'ring-2 ring-emerald-500' : ''}`}>
                                                {n !== undefined && <span className="absolute inset-y-0 left-0 bg-primary/10" style={{ width: `${pct}%` }} />}
                                                <span className="relative">{o}{n !== undefined && ` · ${n} (${pct}%)`}</span>
                                            </button>
                                        );
                                    })}
                                    <p className="text-xs text-muted-foreground">{L('reponses', '{{n}} réponses', { n: etat.sondage.total })}{etat.sondage.ferme ? ` · ${L('ferme', 'fermé')}` : ''}</p>
                                    {prof && !etat.sondage.ferme && (
                                        <Button size="sm" variant="outline" onClick={() => envoyer(route('live.sondage.fermer', [live.id, etat.sondage!.id]))}>{L('fermer_sondage', 'Fermer et montrer les résultats')}</Button>
                                    )}
                                </CardContent>
                            </Card>
                        )}

                        {/* Questions votees */}
                        <Card>
                            <CardHeader><CardTitle className="text-base">{L('questions', 'Questions')}</CardTitle></CardHeader>
                            <CardContent className="space-y-2">
                                {!prof && !termine && (
                                    <form className="flex gap-2" onSubmit={(e) => { e.preventDefault(); if (question.trim()) { envoyer(route('live.message', live.id), { kind: 'question', body: question }); setQuestion(''); } }}>
                                        <input className={champ} value={question} onChange={(e) => setQuestion(e.target.value)} maxLength={1000} placeholder={L('poser', 'Poser une question au professeur')} />
                                        <Button type="submit" size="sm">{L('envoyer', 'Envoyer')}</Button>
                                    </form>
                                )}
                                {(etat?.questions ?? []).length === 0 && <p className="text-sm text-muted-foreground">{L('aucune_question', 'Aucune question pour le moment.')}</p>}
                                {(etat?.questions ?? []).map((q) => (
                                    <div key={q.id} className={`flex items-start gap-2 rounded-lg border border-border p-2 text-sm ${q.repondue ? 'opacity-60' : ''}`}>
                                        <button type="button" disabled={prof || q.moi || termine} onClick={() => envoyer(route('live.voter', [live.id, q.id]))}
                                            className={`shrink-0 rounded-md border px-2 py-1 text-xs font-bold ${q.aVote ? 'border-primary bg-primary text-primary-foreground' : 'border-border'}`} aria-pressed={q.aVote}>
                                            ▲ {q.votes}
                                        </button>
                                        <p className="min-w-0 flex-1">{q.body}{q.auteur && <span className="block text-xs text-muted-foreground">{q.auteur}</span>}</p>
                                        {prof && (
                                            <Button size="sm" variant="ghost" onClick={() => envoyer(route('live.question.repondue', [live.id, q.id]))}>
                                                {q.repondue ? L('rouvrir', 'Rouvrir') : L('repondue', 'Répondue')}
                                            </Button>
                                        )}
                                    </div>
                                ))}
                            </CardContent>
                        </Card>

                        {/* Ressources partagees */}
                        <Card>
                            <CardHeader><CardTitle className="text-base">{L('ressources', 'Ressources de la séance')}</CardTitle></CardHeader>
                            <CardContent className="space-y-2 text-sm">
                                {prof && !termine && (
                                    <form onSubmit={partager} className="space-y-2 rounded-lg bg-muted/40 p-2">
                                        <div className="flex gap-2">
                                            <select className={champ} value={rType} onChange={(e) => { setRType(e.target.value as typeof rType); setRId(''); }}>
                                                <option value="lien">{L('lien', 'Lien')}</option>
                                                <option value="document">{L('document', 'Document de la médiathèque')}</option>
                                                <option value="exercice">{L('exercice', 'Exercice du centre')}</option>
                                            </select>
                                            {rType === 'lien' && <input type="url" className={champ} placeholder="https://" value={rUrl} onChange={(e) => setRUrl(e.target.value)} required />}
                                            {rType !== 'lien' && (
                                                <select className={champ} value={rId} onChange={(e) => setRId(e.target.value)} required>
                                                    <option value="">—</option>
                                                    {(rType === 'document' ? medias : exercices).map((x) => <option key={x.id} value={x.id}>{x.titre}</option>)}
                                                </select>
                                            )}
                                        </div>
                                        <div className="flex gap-2">
                                            <input className={champ} placeholder={L('titre_ressource', 'Titre (facultatif)')} value={rTitre} onChange={(e) => setRTitre(e.target.value)} maxLength={200} />
                                            <Button type="submit" size="sm">{L('partager', 'Partager')}</Button>
                                        </div>
                                    </form>
                                )}
                                {(etat?.ressources ?? []).length === 0 && <p className="text-muted-foreground">{L('aucune_ressource', 'Rien de partagé pour le moment.')}</p>}
                                {(etat?.ressources ?? []).map((r) => (
                                    <a key={r.id} href={r.url} target="_blank" rel="noopener" className="block rounded-lg border border-border p-2 font-medium text-primary hover:bg-muted/50">
                                        {r.type === 'exercice' ? '✏️' : r.type === 'document' ? '📄' : '🔗'} {r.titre}
                                    </a>
                                ))}
                            </CardContent>
                        </Card>

                        {prof && !termine && (
                            <Card>
                                <CardHeader><CardTitle className="text-base">{L('lancer_sondage', 'Lancer un sondage ou un quiz éclair')}</CardTitle></CardHeader>
                                <CardContent>
                                    <form onSubmit={lancerSondage} className="space-y-2 text-sm">
                                        <input className={champ} placeholder={L('question', 'Question')} value={sq} onChange={(e) => setSq(e.target.value)} maxLength={300} required />
                                        {so.map((o, i) => (
                                            <div key={i} className="flex items-center gap-2">
                                                <input className={champ} placeholder={L('option', 'Réponse {{n}}', { n: i + 1 })} value={o} onChange={(e) => setSo(so.map((x, j) => (j === i ? e.target.value : x)))} maxLength={120} />
                                                <label className="flex shrink-0 items-center gap-1 text-xs" title={L('bonne_reponse', 'Bonne réponse (quiz)')}>
                                                    <input type="radio" name="correct" checked={correct === i} onChange={() => setCorrect(i)} /> ✓
                                                </label>
                                            </div>
                                        ))}
                                        <div className="flex flex-wrap gap-2">
                                            {so.length < 6 && <Button type="button" size="sm" variant="ghost" onClick={() => setSo([...so, ''])}>+ {L('ajouter_option', 'Réponse')}</Button>}
                                            {correct !== null && <Button type="button" size="sm" variant="ghost" onClick={() => setCorrect(null)}>{L('sans_bonne', 'Sondage sans bonne réponse')}</Button>}
                                            <Button type="submit" size="sm">{L('lancer', 'Lancer')}</Button>
                                        </div>
                                    </form>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    <div className="space-y-4">
                        {/* Main levee et reactions (eleve) */}
                        {!prof && enCours && (
                            <Card>
                                <CardContent className="space-y-3 py-4">
                                    <Button className="w-full" variant={etat?.maMain ? 'default' : 'outline'} onClick={() => envoyer(route('live.main', live.id))} aria-pressed={etat?.maMain}>
                                        ✋ {etat?.maMain ? L('baisser', 'Baisser la main') : L('lever', 'Lever la main')}
                                    </Button>
                                    <div className="grid grid-cols-3 gap-2">
                                        {REACTIONS.map(([k, e, fr]) => (
                                            <button key={k} type="button" onClick={() => envoyer(route('live.message', live.id), { kind: 'reaction', body: k })}
                                                className="rounded-lg border border-border p-2 text-center text-xs hover:bg-muted">
                                                <span className="block text-xl">{e}</span>{L(`r_${k}`, fr)}
                                            </button>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {prof && !termine && (
                            <Card>
                                <CardHeader><CardTitle className="text-base">✋ {L('mains', 'Mains levées')}</CardTitle></CardHeader>
                                <CardContent className="space-y-1 text-sm">
                                    {(etat?.mains ?? []).length === 0 && <p className="text-muted-foreground">{L('aucune_main', 'Personne pour le moment.')}</p>}
                                    {(etat?.mains ?? []).map((m) => (
                                        <div key={m.user_id} className="flex items-center justify-between">
                                            <span>{m.name}</span>
                                            <Button size="sm" variant="ghost" onClick={() => envoyer(route('live.main.baisser', [live.id, m.user_id]))}>{L('baisser', 'Baisser la main')}</Button>
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        )}

                        {/* Chat de la seance */}
                        <Card>
                            <CardHeader><CardTitle className="text-base">{L('chat', 'Chat de la séance')}</CardTitle></CardHeader>
                            <CardContent className="space-y-2">
                                <div className="max-h-72 space-y-1 overflow-y-auto text-sm" aria-live="polite">
                                    {chat.length === 0 && <p className="text-muted-foreground">{L('chat_vide', 'Aucun message.')}</p>}
                                    {chat.map((m) => (
                                        <p key={m.id} className={m.moi ? 'text-right' : ''}>
                                            <span className="text-xs font-semibold">{m.auteur}</span> <span className="break-words">{m.body}</span>
                                        </p>
                                    ))}
                                </div>
                                {!termine && (
                                    <form className="flex gap-2" onSubmit={(e) => { e.preventDefault(); if (message.trim()) { envoyer(route('live.message', live.id), { kind: 'chat', body: message }); setMessage(''); } }}>
                                        <input className={champ} value={message} onChange={(e) => setMessage(e.target.value)} maxLength={1000} placeholder={L('ecrire', 'Écrire un message')} />
                                        <Button type="submit" size="sm">{L('envoyer', 'Envoyer')}</Button>
                                    </form>
                                )}
                            </CardContent>
                        </Card>

                        {/* Notes personnelles */}
                        {!prof && (
                            <Card>
                                <CardHeader><CardTitle className="text-base">{L('notes', 'Mes notes')}</CardTitle></CardHeader>
                                <CardContent>
                                    <textarea rows={8} className={champ} value={notes} onChange={(e) => changerNotes(e.target.value)} placeholder={L('notes_aide', 'Tes notes restent privées et sont gardées après le live.')} />
                                    <p className="mt-1 text-xs text-muted-foreground">{notesEtat === 'saving' ? L('enregistrement', 'Enregistrement…') : notesEtat === 'saved' ? L('enregistre', 'Enregistré') : ''}</p>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
