import { useTranslation } from 'react-i18next';

interface Competence { seances: number; moyenne: number | null; exige: boolean }
interface Partie { seances: number; moyenne: number | null; minimum: number; seuil: number; ok: boolean }

export interface BilanNiveau {
    pret: boolean;
    /** examen : seul l'examen de niveau fait monter ; comprehension : cas A0. */
    regle: 'examen' | 'comprehension';
    examen_seuil: number;
    niveau: string;
    niveau_suivant: string | null;
    competences: Record<'lecture' | 'ecoute' | 'ecrit' | 'oral', Competence>;
    reception: Partie;
    ecrit: Partie;
    oral: Partie;
    manques: string[];
}

const ORDRE = ['lecture', 'ecoute', 'ecrit', 'oral'] as const;

/**
 * Ce que l'apprenant a montre a son niveau, competence par competence, et ce
 * qui lui manque pour passer au suivant.
 *
 * Hors A0, seul l'examen de niveau (au moins 70 %) fait monter : les
 * competences sont des reperes de preparation, et la carte le dit. Le A0, lui,
 * passe A1 des que sa comprehension (lecture et ecoute ensemble) tient.
 */
export function BilanNiveauCarte({ bilan }: { bilan: BilanNiveau }) {
    const { t } = useTranslation();
    if (!bilan.niveau_suivant) return null;
    const grandDebutant = bilan.regle === 'comprehension';

    const phrase = (cle: (typeof ORDRE)[number]): { texte: string; ok: boolean } => {
        const c = bilan.competences[cle];
        if (!c.exige) return { texte: t('bilan.pas_exige'), ok: true };
        const partie = cle === 'ecrit' ? bilan.ecrit : cle === 'oral' ? bilan.oral : bilan.reception;
        const moyenne = c.moyenne === null ? null : Math.round(c.moyenne);
        if (c.seances === 0) {
            return { texte: t('bilan.rien_encore'), ok: false };
        }
        if (partie.ok) {
            return { texte: t('bilan.acquis', { count: c.seances, moyenne }), ok: true };
        }
        if (partie.seances < partie.minimum) {
            const reste = partie.minimum - partie.seances;
            const cleReste = cle === 'lecture' || cle === 'ecoute' ? 'bilan.reste_comprehension' : 'bilan.reste';
            return { texte: t(cleReste, { count: reste, moyenne }), ok: false };
        }
        return { texte: t('bilan.sous_seuil', { moyenne, seuil: partie.seuil }), ok: false };
    };

    return (
        <section aria-label={t('bilan.titre', { niveau: bilan.niveau_suivant })} className="mx-auto mb-4 max-w-lg rounded-2xl border border-border/70 bg-card p-4 lg:max-w-5xl">
            <h2 className="text-base font-bold text-foreground">{t('bilan.titre', { niveau: bilan.niveau_suivant })}</h2>
            <p className="mt-1 text-sm text-muted-foreground">
                {grandDebutant
                    ? bilan.pret
                        ? t('bilan.pret_a0')
                        : t('bilan.intro_a0', { minimum: bilan.reception.minimum, seuil: bilan.reception.seuil })
                    : t(bilan.pret ? 'bilan.pret' : 'bilan.intro', { niveau: bilan.niveau, suivant: bilan.niveau_suivant, seuil: bilan.examen_seuil })}
            </p>
            <ul className="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                {ORDRE.map((cle) => {
                    const { texte, ok } = phrase(cle);
                    return (
                        <li key={cle} className="flex items-center gap-3 rounded-xl border border-border/60 p-2">
                            <img src={`/illustrations/competences/${cle}.webp`} alt="" width={44} height={44} className="h-11 w-11 shrink-0 rounded-lg object-cover" />
                            <div className="min-w-0">
                                <p className="text-sm font-semibold text-foreground">
                                    {t(`bilan.competence.${cle}`)}{' '}
                                    <span className={ok ? 'text-green-600' : 'text-amber-600'}>{ok ? t('bilan.ok') : t('bilan.a_faire')}</span>
                                </p>
                                <p className="text-xs text-muted-foreground">{texte}</p>
                            </div>
                        </li>
                    );
                })}
            </ul>
            {!grandDebutant && <p className="mt-2 text-xs text-muted-foreground">{t('bilan.repere')}</p>}
        </section>
    );
}
