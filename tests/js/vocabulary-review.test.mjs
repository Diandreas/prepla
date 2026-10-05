import test from 'node:test';
import assert from 'node:assert/strict';
import { vocabularyOptions, vocabularyMode } from '../../resources/js/lib/vocabulary-review.js';

const word = { word: 'hello', definition: 'A greeting', translation: 'bonjour' };
test('sans distracteurs, jamais de QCM avec un seul choix', () => {
    const options = vocabularyOptions(word, [], 'def2word');
    assert.equal(vocabularyMode('def2word', options), 'recall');
});
test('les autres mots de la seance donnent des alternatives distinctes', () => {
    const pool = [{ word: 'name' }, { word: 'student' }, { word: 'HELLO' }, { word: 'name' }];
    const options = vocabularyOptions(word, pool, 'def2word');
    assert.deepEqual(options, ['hello', 'name', 'student']);
    assert.equal(vocabularyMode('def2word', options), 'def2word');
});
test('les traductions vides ou identiques ne creent pas de faux choix', () => {
    const options = vocabularyOptions(word, [{ translation: '' }, { translation: 'BONJOUR' }, { translation: 'salut' }], 'translation');
    assert.equal(options.length, 2);
    assert.equal(vocabularyMode('translation', options), 'recall');
});
