import { Link } from '@inertiajs/react';
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
 */
export default function EssaiShow({ exercise, submitUrl }: Props) {
    return (
        <ExerciseLayout
            title={typeof exercise.content?.title === 'string' ? exercise.content.title : (exercise.exercise_type?.name ?? 'Exercice')}
            examName={exercise.exam?.name}
            backUrl={route('essai.index')}
        >
            <p className="mb-6 rounded-lg border border-dashed px-4 py-2.5 text-center text-sm text-muted-foreground">
                Mode essai : tes réponses sont corrigées mais pas enregistrées.{' '}
                <Link href={route('register')} className="font-medium text-primary underline-offset-4 hover:underline">
                    Crée un compte
                </Link>{' '}
                pour suivre ta progression.
            </p>
            <ExercisePlayer key={exercise.id} exercise={exercise} submitUrl={submitUrl} />
        </ExerciseLayout>
    );
}
