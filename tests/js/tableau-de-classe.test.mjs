import assert from 'node:assert/strict';
import { test } from 'node:test';
import { cheminCourbe, ecartPoints, initiales, pointsCourbe, prenom, variation } from '../../resources/js/lib/tableau-de-classe.js';

test('initiales et prenom', () => {
    assert.equal(initiales('Awa Diallo'), 'AD');
    assert.equal(initiales('  zoé  '), 'Z');
    assert.equal(initiales('Jean Paul Sartre'), 'JS');
    assert.equal(initiales(''), '?');
    assert.equal(prenom('Awa Diallo'), 'Awa');
    assert.equal(prenom(null), '—');
});

test('variation d un volume entre deux periodes', () => {
    assert.deepEqual(variation(15, 12), { texte: '+25 %', sens: 'hausse' });
    assert.deepEqual(variation(6, 12), { texte: '−50 %', sens: 'baisse' });
    assert.deepEqual(variation(5, 5), { texte: '=', sens: 'stable' });
    assert.deepEqual(variation(3, 0), { texte: 'nouveau', sens: 'nouveau' });
    assert.equal(variation(0, 0), null);
});

test('ecart de precision en points', () => {
    assert.deepEqual(ecartPoints(12.4), { texte: '+12 pts', sens: 'hausse' });
    assert.deepEqual(ecartPoints(-1), { texte: '−1 pt', sens: 'baisse' });
    assert.deepEqual(ecartPoints(0.3), { texte: '=', sens: 'stable' });
    assert.equal(ecartPoints(null), null);
});

test('courbe : points et trous', () => {
    const pts = pointsCourbe([0, 50, null, 100], 300, 100, 100);
    assert.deepEqual(pts.map((p) => p.x), [0, 100, 200, 300]);
    assert.deepEqual(pts.map((p) => p.y), [100, 50, null, 0]);
    assert.equal(cheminCourbe(pts), 'M0.0 100.0 L100.0 50.0 M300.0 0.0');
    assert.equal(pointsCourbe([7], 300, 100)[0].x, 150);
});
