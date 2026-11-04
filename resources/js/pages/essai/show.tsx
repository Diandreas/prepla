import ExerciseLayout from '@/layouts/exercise-layout';
import { ExercisePlayer } from '@/components/exercises/exercise-player';
import type { ExerciseRecord } from '@/types';

interface Props {
    exercise: ExerciseRecord;
    submitUrl: string;
}

/**
 * Un exercice joue sans compte, dans le VRAI lecteur.
 *
 * Seule la destination des reponses change : elles partent vers une correction
 * qui n'enregistre rien. Le visiteur voit donc exactement ce que verra un
 * apprenant — c'est tout l'interet de l'essai.
 *
 * La croix ramene a la liste des essais DANS la langue de l'exercice : sans
 * `?langue=`, le visiteur retombait sur la premiere langue de la liste.
 */
export default function EssaiShow({ exercise, submitUrl }: Props) {
    const langue = exercise.exam?.language?.slug;

    return (
        <ExerciseLayout
            title={typeof exercise.content?.title === 'string' ? exercise.content.title : (exercise.exercise_type?.name ?? 'Exercice')}
            examName={exercise.exam?.name}
            backUrl={langue ? route('essai.index', { langue }) : route('essai.index')}
            essai
        >
            <ExercisePlayer key={exercise.id} exercise={exercise} submitUrl={submitUrl} essai />
        </ExerciseLayout>
    );
}
