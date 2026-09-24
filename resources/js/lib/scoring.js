/**
 * Deterministic correction shared by the session player and the offline space.
 *
 * Mirrors ExerciseScoringService branch by branch (ordered sequences, multi-field
 * answers, exact match with the option-letter fallback) so the feedback a learner sees
 * during a session is the one the server records. Both sides run the same cases from
 * tests/fixtures/scoring-parity.json: change a rule here, change it there.
 *
 * Plain JavaScript on purpose: TypeScript imports it through scoring.d.ts and
 * `node --test` runs it without a build step.
 */

const PASS_THRESHOLD = 70;

// Same list as the server: these answers are judged by the AI, never matched locally.
const SERVER_EVALUATED_TYPES = [
    'essay',
    'essay-editor',
    'speaking',
    'writing',
    'short-writing',
    'graph-description',
    'academic-discussion',
    'speaking-recorder',
    'role-play',
    'synthesis',
    'integrated-task',
    'guided-rewrite',
    'text-continuation',
    'synthesis-essay',
];

/** PHP's (string) cast: null → '', true → '1', false → '', arrays → '' (malformed data). */
function phpString(value) {
    if (value === null || value === undefined) return '';
    if (typeof value === 'boolean') return value ? '1' : '';
    if (typeof value === 'string' || typeof value === 'number') return String(value);
    return '';
}

function isScalar(value) {
    return typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean';
}

function isCollection(value) {
    return value !== null && typeof value === 'object';
}

function entries(collection) {
    return Array.isArray(collection) ? collection.map((value, index) => [String(index), value]) : Object.entries(collection);
}

export function normalizeAnswer(value) {
    return phpString(value).trim().toLowerCase().replace(/[’`]/g, "'");
}

/** PHP str_word_count in the default locale: ASCII letters, may contain ' and - but not start with them. */
function wordCount(text) {
    return (phpString(text).match(/[A-Za-z][A-Za-z'-]*/g) ?? []).length;
}

/** Whether the server hands this answer to the AI instead of matching it. */
export function needsServerEvaluation(question, fallbackType) {
    const type = question?.type ?? fallbackType ?? '';
    if (SERVER_EVALUATED_TYPES.includes(type)) return true;

    // A long expected short answer is an open response, not a word to match.
    return type === 'short-answer' && typeof question?.correct_answer === 'string' && wordCount(question.correct_answer.trim()) > 4;
}

// Indices of the genuinely blank note/form items, or null when there is no such structure.
function blankFieldIndices(question) {
    const items = question.notes ?? question.fields ?? null;
    if (!isCollection(items)) return null;

    return entries(items)
        .filter(([, item]) => isCollection(item) && item.value === '')
        .map(([key]) => key);
}

// Expected answers restricted to the blanks the learner could actually fill.
function expectedFields(question) {
    let expected = Object.fromEntries(entries(question.correct_answers));
    const blanks = blankFieldIndices(question);

    if (blanks !== null) {
        const restricted = Object.fromEntries(Object.entries(expected).filter(([key]) => blanks.includes(key)));
        if (Object.keys(restricted).length > 0) expected = restricted;
    }

    return expected;
}

function hasOrder(question) {
    return Array.isArray(question?.correct_order) && question.correct_order.length > 0;
}

function hasFields(question) {
    return isCollection(question?.correct_answers) && entries(question.correct_answers).length > 0;
}

function scoreOrder(question, answer) {
    let given = [];
    if (Array.isArray(answer)) {
        given = [...answer];
    } else if (isCollection(answer)) {
        given = Object.keys(answer)
            .sort((a, b) => a.localeCompare(b, undefined, { numeric: true }))
            .map((key) => answer[key]);
    }

    const expected = Array.isArray(question.items) && question.items.length === given.length ? question.items : question.correct_order;
    const compared = Math.min(expected.length, given.length);
    let hits = 0;
    for (let index = 0; index < compared; index++) {
        if (normalizeAnswer(given[index] ?? '') === normalizeAnswer(expected[index] ?? '')) hits++;
    }

    const accuracy = expected.length > 0 ? (hits / expected.length) * 100 : 0;
    return { correct: accuracy >= PASS_THRESHOLD, accuracy };
}

function scoreFields(question, answer) {
    const expected = Object.entries(expectedFields(question));

    // AI-written keys are unpredictable: a value typed in another field still counts,
    // but each typed value can only be credited once.
    const pool = new Map();
    for (const [, value] of entries(answer)) {
        if (!isScalar(value)) continue;
        const normalized = normalizeAnswer(value);
        if (normalized !== '') pool.set(normalized, (pool.get(normalized) ?? 0) + 1);
    }
    const consume = (key) => {
        const left = (pool.get(key) ?? 0) - 1;
        if (left <= 0) pool.delete(key);
        else pool.set(key, left);
    };

    let correctFields = 0;
    for (const [key, value] of expected) {
        const expectedValue = normalizeAnswer(isScalar(value) ? value : '');
        const given = answer[key] ?? '';
        const givenValue = isScalar(given) ? normalizeAnswer(given) : '';

        if (givenValue !== '' && givenValue === expectedValue) {
            correctFields++;
            if (pool.has(givenValue)) consume(givenValue);
            continue;
        }
        if (expectedValue !== '' && (pool.get(expectedValue) ?? 0) > 0) {
            consume(expectedValue);
            correctFields++;
        }
    }

    const accuracy = expected.length > 0 ? (correctFields / expected.length) * 100 : 0;
    return { correct: accuracy >= PASS_THRESHOLD, accuracy };
}

function scoreExact(question, answer) {
    const expected = question.correct_answer ?? null;

    if (isCollection(expected)) {
        if (!isCollection(answer)) return { correct: false, accuracy: 0 };
        const wanted = new Set(entries(expected).map(([, value]) => normalizeAnswer(value)));
        const given = new Set(entries(answer).map(([, value]) => normalizeAnswer(value)));
        const correct = [...wanted].every((value) => given.has(value)) && [...given].every((value) => wanted.has(value));
        return { correct, accuracy: correct ? 100 : 0 };
    }

    let given = answer;
    if (isCollection(given)) {
        given = entries(given)
            .map(([, value]) => value)
            .filter((value) => value !== null && value !== undefined && value !== '')
            .map(phpString)
            .join(' ');
    }

    const givenValue = normalizeAnswer(given);
    const expectedValue = normalizeAnswer(expected);
    // An empty answer is never correct, even when the exercise expects nothing.
    let correct = givenValue !== '' && givenValue === expectedValue;

    // A letter also matches when the expected answer is written out as the option text.
    if (!correct && /^[a-d]$/.test(givenValue) && isCollection(question.options)) {
        const option = question.options[givenValue.charCodeAt(0) - 97];
        if (option !== undefined && option !== null) correct = normalizeAnswer(option) === expectedValue;
    }

    return { correct, accuracy: correct ? 100 : 0 };
}

export function evaluateAnswer(question, answer) {
    if (hasOrder(question)) return scoreOrder(question, answer);
    if (hasFields(question) && isCollection(answer)) return scoreFields(question, answer);
    return scoreExact(question ?? {}, answer);
}

export function isAnswerCorrect(question, answer) {
    return evaluateAnswer(question, answer).correct;
}

/** The expected answer in words, so a learner never sees a bare letter or nothing at all. */
export function expectedAnswerText(question) {
    if (hasOrder(question)) {
        const sequence = Array.isArray(question.items) && question.items.length === question.correct_order.length ? question.items : question.correct_order;
        return sequence.map(phpString).filter(Boolean).join(' → ');
    }
    if (hasFields(question)) {
        return Object.values(expectedFields(question)).map(phpString).filter(Boolean).join(', ');
    }

    const expected = question?.correct_answer;
    if (isCollection(expected)) {
        return entries(expected)
            .map(([, value]) => phpString(value))
            .filter(Boolean)
            .join(', ');
    }

    const text = phpString(expected).trim();
    if (/^[A-Za-z]$/.test(text) && isCollection(question?.options)) {
        const letter = text.toUpperCase();
        const option = question.options[letter.charCodeAt(0) - 65];
        if (option !== undefined && option !== null && phpString(option) !== '') return `${letter}) ${phpString(option)}`;
    }

    return text;
}

export function scoreSession(questions, answers) {
    const details = (questions ?? []).map((question) => {
        const given = answers ? answers[question.id] : undefined;

        return {
            question_id: question.id,
            correct: isAnswerCorrect(question, given),
            given_answer: given ?? null,
            correct_answer: question.correct_answer ?? null,
            explanation: question.explanation ?? '',
        };
    });

    const total = details.length;
    const score = details.filter((detail) => detail.correct).length;

    return {
        score,
        total,
        accuracy: total > 0 ? Math.round((score / total) * 100) : 0,
        details,
    };
}
