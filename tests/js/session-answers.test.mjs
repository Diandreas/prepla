import assert from 'node:assert/strict';
import { test } from 'node:test';
import { assessmentAnswers } from '../../resources/js/lib/session-answers.js';

test('guided retries never overwrite the independent answer or another exercise', () => {
    assert.deepEqual(assessmentAnswers({
        '0::q1': 'is', '1::q1': 'blue', 'review-10::q1': 'am',
        'review-11::q1': 'red', 'transcription': 'ignored', '99::q2': 'ignored',
    }, [{ id: 10 }, { id: 11 }]), { 10: { q1: 'is' }, 11: { q1: 'blue' } });
});
