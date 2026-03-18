import ExerciseLayout from '@/layouts/exercise-layout';
import { ExercisePlayer } from '@/components/exercises/exercise-player';
import type { ExerciseRecord } from '@/types';

interface Props {
    exercise: ExerciseRecord;
}

export default function ExerciseShow({ exercise }: Props) {
    return (
        <ExerciseLayout
            title={exercise.exercise_type?.name ?? 'Exercise'}
            examName={exercise.exam?.name}
            backUrl={`/practice/${exercise.exam_id}`}
        >
            <ExercisePlayer exercise={exercise} />
        </ExerciseLayout>
    );
}
