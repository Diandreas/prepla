import ExerciseLayout from '@/layouts/exercise-layout';
import { ExercisePlayer } from '@/components/exercises/exercise-player';
import type { ExerciseRecord } from '@/types';

interface Props {
    exercise: ExerciseRecord;
}

/**
 * Le titre de la mise en page devient le h1 : celui de l'exercice quand il en a
 * un (bibliotheque PrepLa), sinon le nom du type — comme pour l'essai.
 */
export default function ExerciseShow({ exercise }: Props) {
    return (
        <ExerciseLayout
            title={typeof exercise.content?.title === 'string' ? exercise.content.title : (exercise.exercise_type?.name ?? 'Exercice')}
            examName={exercise.exam?.name}
            backUrl={`/practice/${exercise.exam_id}`}
        >
            <ExercisePlayer key={exercise.id} exercise={exercise} />
        </ExerciseLayout>
    );
}
