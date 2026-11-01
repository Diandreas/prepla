import { Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import type { EssaiLangue, ExerciceDEssai, Language } from '@/data/languages';

/**
 * L'essai sans compte, dans la page : on choisit son examen et ses vrais
 * exercices d'essai s'affichent aussitot, sans chargement. Un clic, et l'on est
 * dans le vrai lecteur, avec la vraie correction.
 *
 * L'etiquette dit la verite : « Format officiel » seulement la ou un exercice
 * suit le format de l'epreuve, « Entrainement » ailleurs. Promettre l'officiel
 * partout, ce serait mentir sur ce que le visiteur va voir.
 */
const CLE_CHOIX = 'prepla-accueil-examen';

function examenRetenu(): string | null {
    try {
        return localStorage.getItem(CLE_CHOIX);
    } catch {
        return null;
    }
}

function retenir(slug: string) {
    try {
        localStorage.setItem(CLE_CHOIX, slug);
    } catch {
        // Le choix ne sera simplement pas retenu.
    }
}

function CarteExercice({ exercice }: { exercice: ExerciceDEssai }) {
    const { t } = useTranslation();

    return (
        <li>
            <Link
                href={route('essai.show', exercice.id)}
                prefetch="hover"
                className="accueil-carte group flex h-full flex-col p-4 transition hover:-translate-y-0.5"
            >
                <span className="flex flex-wrap items-center gap-2 text-xs font-semibold">
                    <span className="rounded-full px-2.5 py-1" style={exercice.officiel
                        ? { background: 'rgba(34,197,94,0.14)', color: 'var(--officiel)' }
                        : { background: 'var(--carte-forte)', color: 'var(--texte-moyen)' }}>
                        {exercice.officiel ? t('landing.essai_officiel') : t('landing.essai_entrainement')}
                    </span>
                    {exercice.niveau && <span style={{ color: 'var(--texte-moyen)' }}>{exercice.niveau}</span>}
                    {exercice.competence && (
                        <span style={{ color: 'var(--texte-moyen)' }}>· {t(`landing.competence_${exercice.competence}`, { defaultValue: exercice.competence })}</span>
                    )}
                </span>
                <span className="mt-2 font-semibold leading-snug" lang="fr" style={{ color: 'var(--texte)' }}>{exercice.titre}</span>
                {exercice.partie && <span className="mt-0.5 text-xs" lang="fr" style={{ color: 'var(--texte-doux)' }}>{exercice.partie}</span>}
                <span className="mt-auto pt-3 text-sm font-bold" style={{ color: 'var(--accent)' }}>
                    {t('landing.essai_questions', { count: exercice.questions })} · {t('landing.essai_commencer')} →
                </span>
            </Link>
        </li>
    );
}

export function EssaiSection({ langues, essai }: { langues: Language[]; essai: EssaiLangue[] }) {
    const { t, i18n } = useTranslation();

    // Les exercices ouverts, par examen.
    const parExamen = useMemo(() => {
        const carte = new Map<string, ExerciceDEssai[]>();
        for (const langue of essai) {
            for (const examen of langue.examens) carte.set(examen.slug, examen.exercices);
        }
        return carte;
    }, [essai]);

    const premierOuvert = langues.flatMap((l) => l.exams).find((e) => (parExamen.get(e.slug)?.length ?? 0) > 0)?.slug ?? langues[0]?.exams[0]?.slug ?? '';
    const [choisi, setChoisi] = useState<string>(() => {
        const retenu = examenRetenu();
        return retenu && langues.some((l) => l.exams.some((e) => e.slug === retenu)) ? retenu : premierOuvert;
    });

    const langueChoisie = langues.find((l) => l.exams.some((e) => e.slug === choisi));
    const examenChoisi = langueChoisie?.exams.find((e) => e.slug === choisi);
    const exercices = parExamen.get(choisi) ?? [];
    const pasEnFrancais = !(i18n.resolvedLanguage ?? i18n.language ?? 'fr').startsWith('fr');

    const choisir = (slug: string) => {
        setChoisi(slug);
        retenir(slug);
    };

    return (
        <section id="essayer" className="accueil-section" aria-labelledby="essayer-titre">
            <div className="accueil-section-interieur">
                <h2 id="essayer-titre" tabIndex={-1}>
                    {t('landing.essai_titre')} <em>{t('landing.essai_titre_accent')}</em>
                </h2>
                <p className="accueil-section-chapo">{t('landing.essai_chapo')}</p>

                <div className="mt-10 grid gap-5 md:grid-cols-3">
                    {langues.map((langue) => (
                        <fieldset key={langue.slug} className="min-w-0">
                            <legend className="mb-2 text-xs font-bold uppercase tracking-widest" style={{ color: 'var(--texte-doux)' }}>
                                {t(`landing.langue_${langue.slug}`, { defaultValue: langue.name })}
                            </legend>
                            <div className="accueil-puces">
                                {langue.exams.map((examen) => (
                                    <button
                                        key={examen.slug}
                                        type="button"
                                        className="accueil-puce"
                                        aria-pressed={examen.slug === choisi}
                                        aria-controls="essai-exercices"
                                        onClick={() => choisir(examen.slug)}
                                    >
                                        {examen.name}
                                    </button>
                                ))}
                            </div>
                        </fieldset>
                    ))}
                </div>

                <div id="essai-exercices" className="mt-8" aria-live="polite">
                    {exercices.length > 0 ? (
                        <ul className="accueil-grille deux mt-0" style={{ marginTop: 0 }}>
                            {exercices.map((exercice) => (
                                <CarteExercice key={exercice.id} exercice={exercice} />
                            ))}
                        </ul>
                    ) : (
                        <p className="accueil-carte p-5 text-sm" style={{ color: 'var(--texte-moyen)' }}>
                            {t('landing.essai_aucun', { examen: examenChoisi?.name ?? '' })}
                        </p>
                    )}

                    {examenChoisi && (
                        <p className="mt-5 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                            {exercices.length > 0 && (
                                <Link href={`/essayer?examen=${examenChoisi.slug}`} className="accueil-lien font-semibold">
                                    {t('landing.essai_tous', { examen: examenChoisi.name })} →
                                </Link>
                            )}
                            <a href={`/structures?langue=${langueChoisie?.slug}#${examenChoisi.slug}`} className="accueil-lien">
                                {t('landing.essai_structure', { examen: examenChoisi.name })} →
                            </a>
                        </p>
                    )}

                    <p className="mt-4 text-xs leading-relaxed" style={{ color: 'var(--texte-doux)' }}>
                        {t('landing.essai_note')}
                        {pasEnFrancais && <> {t('landing.essai_note_francais')}</>}
                    </p>
                </div>
            </div>
        </section>
    );
}
