import { AlertCircle, ArrowDown, ArrowUp, CheckCircle2, ChevronDown, Copy, Plus, Trash2, X } from 'lucide-react';
import { useId, useState } from 'react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import {
    accepteLesDeuxModes,
    choixVraiFaux,
    cocherOption,
    indexDeLaBonneReponse,
    lettre,
    modeDeReponse,
    retirerOption,
    texteDeLOption,
    type Probleme,
} from '@/lib/createur-exercices';
import type { Family } from '@/lib/exercise-schemas';
import { useT } from '@/lib/i18n/traduire';
import { cn } from '@/lib/utils';

export const champ = 'w-full rounded-lg border border-border bg-background px-3 py-2 text-base sm:text-sm placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary aria-[invalid=true]:border-rose-400';

const ECOUTES_AVEC_TEXTE = ['listen-repeat', 'listen-choose-response', 'dictation'];
const MAX_OPTIONS = 6;

// eslint-disable-next-line @typescript-eslint/no-explicit-any -- chaque format a sa forme de question
type Question = Record<string, any>;

function Message({ problemes, champ: nom }: { problemes: Probleme[]; champ: string }) {
    const { t } = useT();
    const p = problemes.filter((x) => x.champ === nom);
    if (p.length === 0) return null;
    return (
        <p className="mt-1 flex items-start gap-1 text-xs text-rose-600 dark:text-rose-400">
            <AlertCircle className="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
            {p.map((x) => t(x.message)).join(' ')}
        </p>
    );
}

/** Une question, repliable, avec ses actions et ses champs selon le format. */
export function EditeurQuestion({
    index, total, famille, componentKey, ecoute, question, problemes, afficherProblemes,
    ouverte, onToggle, onChange, onMonter, onDescendre, onDupliquer, onSupprimer,
}: {
    index: number;
    total: number;
    famille: Family;
    componentKey: string;
    ecoute: boolean;
    question: Question;
    problemes: Probleme[];
    afficherProblemes: boolean;
    ouverte: boolean;
    onToggle: () => void;
    onChange: (q: Question) => void;
    onMonter: () => void;
    onDescendre: () => void;
    onDupliquer: () => void;
    onSupprimer: () => void;
}) {
    const { t } = useT();
    const id = useId();
    const [confirmer, setConfirmer] = useState(false);
    const visibles = afficherProblemes ? problemes : [];
    const maj = (patch: Question) => onChange({ ...question, ...patch });
    const resume = String(question.text ?? question.audio_text ?? '').trim();
    const pret = problemes.length === 0;

    const libelleEnonce = famille === 'ai-writing' ? t('Consigne de rédaction')
        : famille === 'ai-speaking' ? t('Consigne orale')
            : famille === 'multi-field' ? t('Consigne (et texte avec ses trous)')
                : t('Énoncé de la question');

    return (
        <div className={cn('rounded-xl border bg-card', afficherProblemes && !pret ? 'border-rose-300 dark:border-rose-800' : 'border-border')}>
            <div className="flex items-center gap-1 p-2 pl-3">
                <button
                    type="button"
                    onClick={onToggle}
                    aria-expanded={ouverte}
                    aria-controls={`${id}-corps`}
                    className="flex min-h-11 min-w-0 flex-1 items-center gap-2 rounded-lg text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                >
                    <ChevronDown className={cn('h-4 w-4 shrink-0 transition-transform', !ouverte && '-rotate-90')} aria-hidden="true" />
                    <span className="shrink-0 text-sm font-bold">{t('Question {n}', { n: index + 1 })}</span>
                    <span className="min-w-0 truncate text-sm text-muted-foreground">{resume || t('Sans énoncé')}</span>
                    {pret
                        ? <CheckCircle2 className="ml-auto h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" aria-label={t('Complète')} />
                        : <span className="ml-auto shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-900 dark:bg-amber-900/40 dark:text-amber-100">{t('À compléter')}</span>}
                </button>
                <div className="flex shrink-0 items-center">
                    <Button type="button" variant="ghost" size="icon" className="h-9 w-9" disabled={index === 0} onClick={onMonter} aria-label={t('Monter la question {n}', { n: index + 1 })}><ArrowUp className="h-4 w-4" /></Button>
                    <Button type="button" variant="ghost" size="icon" className="h-9 w-9" disabled={index === total - 1} onClick={onDescendre} aria-label={t('Descendre la question {n}', { n: index + 1 })}><ArrowDown className="h-4 w-4" /></Button>
                    <Button type="button" variant="ghost" size="icon" className="hidden h-9 w-9 sm:inline-flex" onClick={onDupliquer} aria-label={t('Dupliquer la question {n}', { n: index + 1 })}><Copy className="h-4 w-4" /></Button>
                    <Button type="button" variant="ghost" size="icon" className="h-9 w-9 text-rose-600 hover:text-rose-700 dark:text-rose-400" onClick={() => setConfirmer(true)} aria-label={t('Supprimer la question {n}', { n: index + 1 })}><Trash2 className="h-4 w-4" /></Button>
                </div>
            </div>

            {ouverte && (
                <div id={`${id}-corps`} className="space-y-4 border-t border-border p-3 sm:p-4">
                    {ecoute && ECOUTES_AVEC_TEXTE.includes(componentKey) && (
                        <div>
                            <label htmlFor={`${id}-audio`} className="text-sm font-medium">{t('Texte audio')} <span className="font-normal text-muted-foreground">{t('(lu par la voix de synthèse)')}</span></label>
                            <textarea id={`${id}-audio`} className={`mt-1 min-h-[60px] ${champ}`} value={question.audio_text ?? ''} onChange={(e) => maj({ audio_text: e.target.value })} placeholder={t('La phrase que l’élève entendra…')} />
                        </div>
                    )}

                    <div>
                        <label htmlFor={`${id}-texte`} className="text-sm font-medium">{libelleEnonce}</label>
                        <textarea
                            id={`${id}-texte`}
                            className={`mt-1 min-h-[70px] ${champ}`}
                            value={question.text ?? ''}
                            aria-invalid={visibles.some((p) => p.champ === 'text')}
                            onChange={(e) => maj({ text: e.target.value })}
                            placeholder={famille === 'ai-writing' ? t('Ex. : Écrivez un e-mail à votre ami pour l’inviter à votre anniversaire (80 mots).') : t('Ex. : Où Anna travaille-t-elle ?')}
                        />
                        <Message problemes={visibles} champ="text" />
                    </div>

                    {famille === 'exact-match' && <ReponseExacte question={question} componentKey={componentKey} problemes={visibles} onChange={onChange} />}
                    {famille === 'multi-field' && <Grille question={question} problemes={visibles} onChange={onChange} />}
                    {famille === 'order' && <Ordre question={question} problemes={visibles} onChange={onChange} />}
                    {famille === 'ai-writing' && <Mots question={question} problemes={visibles} onChange={onChange} />}
                    {famille === 'ai-speaking' && <Points question={question} problemes={visibles} onChange={onChange} />}

                    {['exact-match', 'multi-field', 'order'].includes(famille) && (
                        <div>
                            <label htmlFor={`${id}-expl`} className="text-sm font-medium">{t('Explication')} <span className="font-normal text-muted-foreground">{t('(montrée à l’élève après sa réponse)')}</span></label>
                            <textarea id={`${id}-expl`} className={`mt-1 min-h-[50px] ${champ}`} value={question.explanation ?? ''} onChange={(e) => maj({ explanation: e.target.value })} placeholder={t('Pourquoi cette réponse est la bonne…')} />
                        </div>
                    )}

                    <Button type="button" variant="outline" size="sm" className="sm:hidden" onClick={onDupliquer}><Copy className="h-4 w-4" aria-hidden="true" /> {t('Dupliquer')}</Button>
                </div>
            )}

            <AlertDialog open={confirmer} onOpenChange={setConfirmer}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>{t('Supprimer la question {n} ?', { n: index + 1 })}</AlertDialogTitle>
                        <AlertDialogDescription>{resume ? `« ${resume.slice(0, 90)} »` : t('Cette question est vide.')} {t('Elle sera retirée de l’exercice.')}</AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>{t('Annuler')}</AlertDialogCancel>
                        <AlertDialogAction onClick={onSupprimer} className="bg-rose-600 text-white hover:bg-rose-700">{t('Supprimer')}</AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
}

function ReponseExacte({ question, componentKey, problemes, onChange }: { question: Question; componentKey: string; problemes: Probleme[]; onChange: (q: Question) => void }) {
    const { t } = useT();
    const nom = useId();
    const mode = modeDeReponse(componentKey, question);
    const deuxModes = accepteLesDeuxModes(componentKey);

    if (mode === 'vrai-faux') {
        const valeur = String(question.correct_answer ?? '').trim().toLowerCase();
        return (
            <fieldset>
                <legend className="text-sm font-medium">{t('Bonne réponse')}</legend>
                <div className="mt-1.5 flex flex-wrap gap-2" role="radiogroup">
                    {choixVraiFaux(question).map((c) => {
                        const choisi = valeur === c.value.toLowerCase();
                        return (
                            <button
                                key={c.value}
                                type="button"
                                role="radio"
                                aria-checked={choisi}
                                onClick={() => onChange({ ...question, correct_answer: c.value })}
                                className={cn(
                                    'min-h-11 rounded-xl border-[1.5px] px-4 text-sm font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                                    choisi ? 'border-emerald-500 bg-emerald-50 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100' : 'border-border hover:bg-muted',
                                )}
                            >
                                {choisi && <CheckCircle2 className="mr-1.5 inline h-4 w-4" aria-hidden="true" />}{t(c.label)}
                            </button>
                        );
                    })}
                </div>
                <Message problemes={problemes} champ="correct_answer" />
            </fieldset>
        );
    }

    if (mode === 'texte') {
        return (
            <div className="space-y-2">
                {deuxModes && (
                    <button type="button" className="text-xs font-semibold text-primary hover:underline" onClick={() => onChange({ ...question, options: ['', '', ''], correct_answer: '' })}>
                        {t('Proposer plutôt des choix à cocher')}
                    </button>
                )}
                <label className="block text-sm font-medium">
                    {t('Bonne réponse')}
                    <input
                        className={`mt-1 ${champ}`}
                        value={Array.isArray(question.correct_answer) ? question.correct_answer[0] ?? '' : question.correct_answer ?? ''}
                        aria-invalid={problemes.some((p) => p.champ === 'correct_answer')}
                        onChange={(e) => onChange({ ...question, correct_answer: e.target.value })}
                        placeholder={t('Le mot ou la phrase attendus')}
                    />
                </label>
                <Message problemes={problemes} champ="correct_answer" />
            </div>
        );
    }

    const options: unknown[] = Array.isArray(question.options) ? question.options : [];
    const bonne = indexDeLaBonneReponse(question);

    return (
        <fieldset className="space-y-2">
            <legend className="text-sm font-medium">{t('Propositions')} <span className="font-normal text-muted-foreground">{t('— cochez la bonne réponse')}</span></legend>
            {options.map((opt, i) => (
                <div key={i} className={cn('flex items-center gap-2 rounded-xl border-[1.5px] p-1.5 pl-2', bonne === i ? 'border-emerald-500 bg-emerald-50/60 dark:bg-emerald-950/30' : 'border-border')}>
                    <input
                        type="radio"
                        name={nom}
                        checked={bonne === i}
                        onChange={() => onChange(cocherOption(question, i))}
                        className="h-5 w-5 shrink-0 accent-emerald-600"
                        aria-label={t('Proposition {lettre} : bonne réponse', { lettre: lettre(i) })}
                    />
                    <span className="w-5 shrink-0 text-center text-xs font-bold text-muted-foreground" aria-hidden="true">{lettre(i)}</span>
                    <input
                        className={cn(champ, 'border-transparent bg-transparent focus:bg-background')}
                        placeholder={t('Proposition {lettre}', { lettre: lettre(i) })}
                        aria-label={t('Texte de la proposition {lettre}', { lettre: lettre(i) })}
                        value={texteDeLOption(opt)}
                        onChange={(e) => {
                            const next = [...options];
                            next[i] = e.target.value;
                            onChange({ ...question, options: next });
                        }}
                    />
                    <Button type="button" variant="ghost" size="icon" className="h-9 w-9 shrink-0" disabled={options.length <= 2} onClick={() => onChange(retirerOption(question, i))} aria-label={t('Retirer la proposition {lettre}', { lettre: lettre(i) })}>
                        <X className="h-4 w-4" />
                    </Button>
                </div>
            ))}
            <div className="flex flex-wrap items-center gap-3">
                {options.length < MAX_OPTIONS && (
                    <Button type="button" variant="outline" size="sm" onClick={() => onChange({ ...question, options: [...options, ''] })}>
                        <Plus className="h-4 w-4" aria-hidden="true" /> {t('Ajouter une proposition')}
                    </Button>
                )}
                {deuxModes && (
                    <button type="button" className="text-xs font-semibold text-primary hover:underline" onClick={() => onChange({ ...question, options: [], correct_answer: '' })}>
                        {t('Faire écrire la réponse à la place')}
                    </button>
                )}
            </div>
            <Message problemes={problemes} champ="options" />
            <Message problemes={problemes} champ="correct_answer" />
        </fieldset>
    );
}

function Grille({ question, problemes, onChange }: { question: Question; problemes: Probleme[]; onChange: (q: Question) => void }) {
    const { t } = useT();
    const map: Record<string, string> = question.correct_answers && typeof question.correct_answers === 'object' ? question.correct_answers : {};
    const entrees = Object.entries(map);
    const ecrire = (paires: [string, string][]) => onChange({ ...question, correct_answers: Object.fromEntries(paires) });
    return (
        <fieldset className="space-y-2">
            <legend className="text-sm font-medium">{t('Les trous et leurs réponses')}</legend>
            <p className="text-xs text-muted-foreground">{t('Le repère est le numéro ou le nom du trou dans le texte (ex. « 1 », « nom »).')}</p>
            {entrees.map(([k, v], i) => (
                <div key={i} className="flex items-center gap-2">
                    <input className={`${champ} w-24 shrink-0`} aria-label={t('Repère du trou {n}', { n: i + 1 })} placeholder={t('Repère')} value={k} onChange={(e) => ecrire(entrees.map((p, j) => (j === i ? [e.target.value, p[1]] : p)))} />
                    <input className={champ} aria-label={t('Réponse du trou {n}', { n: i + 1 })} placeholder={t('Réponse attendue')} value={v} onChange={(e) => ecrire(entrees.map((p, j) => (j === i ? [p[0], e.target.value] : p)))} />
                    <Button type="button" variant="ghost" size="icon" className="h-9 w-9 shrink-0" onClick={() => ecrire(entrees.filter((_, j) => j !== i))} aria-label={t('Retirer le trou {n}', { n: i + 1 })}><X className="h-4 w-4" /></Button>
                </div>
            ))}
            <Button type="button" variant="outline" size="sm" onClick={() => {
                let n = entrees.length + 1;
                while (map[String(n)] !== undefined) n++;
                ecrire([...entrees, [String(n), '']]);
            }}><Plus className="h-4 w-4" aria-hidden="true" /> {t('Ajouter un trou')}</Button>
            <Message problemes={problemes} champ="correct_answers" />
        </fieldset>
    );
}

function Ordre({ question, problemes, onChange }: { question: Question; problemes: Probleme[]; onChange: (q: Question) => void }) {
    const { t } = useT();
    const items: string[] = (Array.isArray(question.items) ? question.items : []).map(texteDeLOption);
    // L'ordre saisi est la solution : le lecteur melange les elements.
    const ecrire = (next: string[]) => onChange({ ...question, items: next, correct_order: next });
    return (
        <fieldset className="space-y-2">
            <legend className="text-sm font-medium">{t('Éléments dans le BON ordre')} <span className="font-normal text-muted-foreground">{t('— l’élève les recevra mélangés')}</span></legend>
            {items.map((it, i) => (
                <div key={i} className="flex items-center gap-2">
                    <span className="w-5 shrink-0 text-center text-xs font-bold text-muted-foreground" aria-hidden="true">{i + 1}</span>
                    <input className={champ} aria-label={t('Élément {n}', { n: i + 1 })} value={it} onChange={(e) => ecrire(items.map((x, j) => (j === i ? e.target.value : x)))} />
                    <Button type="button" variant="ghost" size="icon" className="h-9 w-9 shrink-0" disabled={i === 0} onClick={() => { const n = [...items]; [n[i - 1], n[i]] = [n[i], n[i - 1]]; ecrire(n); }} aria-label={t('Monter l’élément {n}', { n: i + 1 })}><ArrowUp className="h-4 w-4" /></Button>
                    <Button type="button" variant="ghost" size="icon" className="h-9 w-9 shrink-0" onClick={() => ecrire(items.filter((_, j) => j !== i))} aria-label={t('Retirer l’élément {n}', { n: i + 1 })}><X className="h-4 w-4" /></Button>
                </div>
            ))}
            <Button type="button" variant="outline" size="sm" onClick={() => ecrire([...items, ''])}><Plus className="h-4 w-4" aria-hidden="true" /> {t('Ajouter un élément')}</Button>
            <Message problemes={problemes} champ="items" />
        </fieldset>
    );
}

function Mots({ question, problemes, onChange }: { question: Question; problemes: Probleme[]; onChange: (q: Question) => void }) {
    const { t } = useT();
    return (
        <div>
            <div className="grid grid-cols-2 gap-3">
                <label className="text-sm font-medium">{t('Mots minimum')}
                    <input type="number" min={0} inputMode="numeric" className={`mt-1 ${champ}`} value={question.min_words ?? 50} onChange={(e) => onChange({ ...question, min_words: Number(e.target.value) })} />
                </label>
                <label className="text-sm font-medium">{t('Mots maximum')}
                    <input type="number" min={0} inputMode="numeric" className={`mt-1 ${champ}`} value={question.max_words ?? 150} onChange={(e) => onChange({ ...question, max_words: Number(e.target.value) })} />
                </label>
            </div>
            <p className="mt-1 text-xs text-muted-foreground">{t('La copie est corrigée par l’IA selon les critères de l’examen.')}</p>
            <Message problemes={problemes} champ="words" />
        </div>
    );
}

function Points({ question, problemes, onChange }: { question: Question; problemes: Probleme[]; onChange: (q: Question) => void }) {
    const { t } = useT();
    const points: string[] = Array.isArray(question.expected_points) ? question.expected_points : [''];
    const ecrire = (next: string[]) => onChange({ ...question, expected_points: next });
    return (
        <fieldset className="space-y-2">
            <legend className="text-sm font-medium">{t('Points que l’élève doit aborder')} <span className="font-normal text-muted-foreground">{t('— l’IA vérifie qu’ils y sont')}</span></legend>
            {points.map((p, i) => (
                <div key={i} className="flex items-center gap-2">
                    <span className="text-muted-foreground" aria-hidden="true">•</span>
                    <input className={champ} aria-label={t('Point attendu {n}', { n: i + 1 })} value={p} placeholder={t('Ex. : se présenter')} onChange={(e) => ecrire(points.map((x, j) => (j === i ? e.target.value : x)))} />
                    <Button type="button" variant="ghost" size="icon" className="h-9 w-9 shrink-0" disabled={points.length <= 1} onClick={() => ecrire(points.filter((_, j) => j !== i))} aria-label={t('Retirer le point {n}', { n: i + 1 })}><X className="h-4 w-4" /></Button>
                </div>
            ))}
            <Button type="button" variant="outline" size="sm" onClick={() => ecrire([...points, ''])}><Plus className="h-4 w-4" aria-hidden="true" /> {t('Ajouter un point')}</Button>
            <Message problemes={problemes} champ="expected_points" />
        </fieldset>
    );
}
