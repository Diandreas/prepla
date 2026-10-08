import assert from 'node:assert/strict';
import { test } from 'node:test';
import { isBlankAnswer } from '../../resources/js/lib/scoring.js';

/**
 * « Verifier » restait allume sur une reponse vide : juste apres « Refaire
 * l'enregistrement » (l'enregistreur transmet '' pour effacer l'audio) ou apres
 * avoir efface un mot au clavier. Un appui envoyait le vide a la correction et la
 * question etait perdue.
 */
test('une reponse vide ne compte pas comme une reponse', () => {
    for (const vide of [undefined, null, '', '   ', {}, [], { '0': '', '1': '  ' }, ['', '']]) {
        assert.equal(isBlankAnswer(vide), true, `${JSON.stringify(vide)} devrait etre vide`);
    }
});

test('une vraie reponse reste une reponse', () => {
    for (const plein of ['a', ' mot ', 0, 1, false, true, { '0': '', '2': 'mot' }, ['', 'p3']]) {
        assert.equal(isBlankAnswer(plein), false, `${JSON.stringify(plein)} devrait compter`);
    }
});

test('un enregistrement vide ne compte pas, un enregistrement rempli compte', () => {
    assert.equal(isBlankAnswer(new Blob([])), true);
    assert.equal(isBlankAnswer(new Blob(['son'])), false);
});
