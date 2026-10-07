/** Only independent answers are graded; guided review has its own namespace. */
export function assessmentAnswers(answers, exercises) {
    const grouped = {};
    for (const [key, value] of Object.entries(answers)) {
        const match = /^(\d+)::(.+)$/.exec(key);
        if (!match) continue;
        const exercise = exercises[Number(match[1])];
        if (!exercise) continue;
        (grouped[exercise.id] ??= {})[match[2]] = value;
    }
    return grouped;
}
