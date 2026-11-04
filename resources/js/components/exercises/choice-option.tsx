import { Check, Lock, X } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * L'etat d'un choix une fois la correction revelee. `null` tant qu'on ne sait
 * rien : pendant l'envoi, notamment, aucune couleur juste/faux ne doit
 * apparaitre — le visiteur de l'essai n'a meme pas la bonne reponse.
 */
export type VerdictChoix = 'juste' | 'faux' | null;

/**
 * Le verdict d'un choix, a partir de la seule correction connue.
 *
 * `revealed` dit si la correction est montree ; `disabled` seul (verrou pendant
 * l'envoi) ne colore jamais rien. Sans bonne reponse connue, rien n'est colore
 * non plus : un choix ne passe pas au rouge faute d'information.
 */
export function verdictDuChoix(revealed: boolean, attendu: string, valeur: string, choisi: boolean): VerdictChoix {
    if (!revealed || attendu === '') return null;
    if (attendu === valeur) return 'juste';
    return choisi ? 'faux' : null;
}

/** La lettre d'un choix : A, B, C… */
export const lettreDuChoix = (index: number) => String.fromCharCode(65 + index);

/**
 * Un seul style de champ de saisie pour tous les formats : au moins 44 px de
 * haut, 16 px de texte (en dessous, le telephone zoome sur la page), bordure
 * aux couleurs du theme et anneau de focus visible.
 */
export const CHAMP_SAISIE =
    'h-11 w-full min-w-0 rounded-xl border-[1.5px] border-border bg-background px-4 text-base text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-60';

/** Le meme champ, pose dans la phrase a la place du blanc. */
export const CHAMP_DANS_LA_PHRASE =
    'mx-1 inline-block h-11 max-w-full rounded-xl border-[1.5px] border-primary/60 bg-primary/5 px-3 text-center align-middle text-base font-semibold text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-60';

interface ChoiceOptionProps {
    /** La pastille : « A », « B »… Absente pour Vrai/Faux, ou le libelle suffit. */
    letter?: string;
    children: ReactNode;
    selected: boolean;
    verdict?: VerdictChoix;
    /** Verrouille (envoi en cours, correction affichee) : plus de clic ni d'effet. */
    disabled?: boolean;
    onSelect: () => void;
    /** Rang du choix, lu par les raccourcis clavier du lecteur (1-4, A-D). */
    index?: number;
    /** `centre` pour les choix courts poses cote a cote (Vrai / Faux). */
    align?: 'gauche' | 'centre';
    className?: string;
}

/**
 * Le choix de reponse commun a tous les formats fermes (QCM, vrai/faux,
 * appariement, texte a trous en mode QCM, QCM a images sans images…).
 *
 * Avant, chaque format avait le sien : bordure de 1 ou 2 px, texte de 14 ou
 * 16 px, une ombre gris clair ecrite en dur qui tracait un trait sous chaque
 * choix en theme sombre, pas toujours d'etat annonce ni d'anneau de focus.
 * Juste et faux portent TOUJOURS une icone et un texte pour les lecteurs
 * d'ecran : la couleur seule ne dit rien a qui ne la voit pas.
 */
export function ChoiceOption({ letter, children, selected, verdict = null, disabled = false, onSelect, index, align = 'gauche', className }: ChoiceOptionProps) {
    const juste = verdict === 'juste';
    const faux = verdict === 'faux';

    return (
        <button
            type="button"
            onClick={() => !disabled && onSelect()}
            disabled={disabled}
            aria-pressed={selected}
            data-choix={index}
            className={cn(
                'duo-press group flex min-h-14 w-full items-center gap-3 rounded-2xl border-[1.5px] px-4 py-3 text-base font-medium leading-snug',
                'shadow-[0_3px_0_0_var(--ombre-choix,var(--duo-shadow))] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background',
                align === 'centre' ? 'justify-center text-center' : 'text-left',
                juste
                    ? 'border-emerald-600 bg-emerald-50 text-emerald-950 [--ombre-choix:var(--color-emerald-600)] dark:border-emerald-400 dark:bg-emerald-950/40 dark:text-emerald-50 dark:[--ombre-choix:var(--color-emerald-700)]'
                    : faux
                      ? 'border-red-600 bg-red-50 text-red-950 [--ombre-choix:var(--color-red-600)] dark:border-red-400 dark:bg-red-950/40 dark:text-red-50 dark:[--ombre-choix:var(--color-red-800)]'
                      : selected
                        ? 'border-primary bg-primary/15 text-foreground [--ombre-choix:var(--primary)]'
                        : 'border-border bg-card text-foreground enabled:hover:border-primary/60',
                disabled && !verdict && !selected && 'opacity-60',
                className,
            )}
        >
            {letter && (
                <span
                    aria-hidden="true"
                    className={cn(
                        'inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-sm font-bold',
                        juste
                            ? 'bg-emerald-700 text-white'
                            : faux
                              ? 'bg-red-700 text-white'
                              : selected
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted text-foreground',
                    )}
                >
                    {letter}
                </span>
            )}
            <span className={cn('min-w-0', align === 'centre' ? '' : 'flex-1')}>
                {letter && <span className="sr-only">{letter}. </span>}
                {children}
            </span>
            {juste && (
                <>
                    <Check aria-hidden="true" className="h-5 w-5 shrink-0 text-emerald-700 dark:text-emerald-300" />
                    <span className="sr-only"> — Bonne réponse</span>
                </>
            )}
            {faux && (
                <>
                    <X aria-hidden="true" className="h-5 w-5 shrink-0 text-red-700 dark:text-red-300" />
                    <span className="sr-only"> — Réponse incorrecte</span>
                </>
            )}
            {disabled && !verdict && selected && <Lock aria-hidden="true" className="h-4 w-4 shrink-0 text-muted-foreground" />}
        </button>
    );
}
