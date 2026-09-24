import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { evaluateAnswer, expectedAnswerText, needsServerEvaluation, normalizeAnswer, scoreSession } from '../../resources/js/lib/scoring.js';

const fixture = JSON.parse(readFileSync(new URL('../fixtures/scoring-parity.json', import.meta.url), 'utf8'));

for (const testCase of fixture.cases) {
    test(`correction : ${testCase.name}`, () => {
        const result = evaluateAnswer(testCase.question, testCase.answer);

        assert.equal(result.correct, testCase.correct);
        if (testCase.accuracy !== undefined) {
            assert.ok(Math.abs(result.accuracy - testCase.accuracy) < 0.01, `precision ${result.accuracy} au lieu de ${testCase.accuracy}`);
        }
    });
}

test("le score d'une seance agrege les cas partages", () => {
    const questions = fixture.cases.map((testCase, index) => ({ ...testCase.question, id: `case-${index}` }));
    const answers = Object.fromEntries(fixture.cases.map((testCase, index) => [`case-${index}`, testCase.answer]));
    const expected = fixture.cases.filter((testCase) => testCase.correct).length;

    const result = scoreSession(questions, answers);

    assert.equal(result.total, fixture.cases.length);
    assert.equal(result.score, expected);
    assert.deepEqual(
        result.details.map((detail) => detail.correct),
        fixture.cases.map((testCase) => testCase.correct),
    );
});

test('une question sans reponse enregistree reste fausse', () => {
    const result = scoreSession([{ id: 'q1', correct_answer: 'bin' }], {});

    assert.equal(result.score, 0);
    assert.equal(normalizeAnswer(undefined), '');
});

test('la reponse attendue est ecrite en clair', () => {
    assert.equal(expectedAnswerText({ correct_answer: 'C', options: ['Am Freitag', 'Am Samstag', 'Am Sonntag', 'Am Montag'] }), 'C) Am Sonntag');
    assert.equal(
        expectedAnswerText({
            notes: [
                { label: 'Name', value: '' },
                { label: 'Stadt', value: 'Berlin' },
                { label: 'Beruf', value: '' },
            ],
            correct_answers: { 0: 'Anna', 1: 'Berlin', 2: 'Lehrerin' },
        }),
        'Anna, Lehrerin',
    );
    assert.equal(expectedAnswerText({ items: ['Erstens', 'Dann', 'Zum Schluss'], correct_order: ['Erstens', 'Dann', 'Zum Schluss'] }), 'Erstens → Dann → Zum Schluss');
    assert.equal(expectedAnswerText({ correct_answer: 'bin' }), 'bin');
    assert.equal(expectedAnswerText({}), '');
});

test('les reponses ouvertes restent evaluees par le serveur', () => {
    assert.equal(needsServerEvaluation({ type: 'essay-editor' }), true);
    assert.equal(needsServerEvaluation({ type: 'short-answer', correct_answer: 'Er hat das Buch gestern gelesen' }), true);
    assert.equal(needsServerEvaluation({ type: 'short-answer', correct_answer: 'Berlin' }), false);
    assert.equal(needsServerEvaluation({}, 'mcq'), false);
    assert.equal(needsServerEvaluation({}, 'speaking-recorder'), true);
});
