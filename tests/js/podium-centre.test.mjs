import assert from 'node:assert/strict';
import { test } from 'node:test';
import { distinctions, podium, rangs } from '../../resources/js/lib/podium-centre.js';

/**
 * Le classement de la page Eleves du centre : un podium par XP sur la periode
 * choisie, et quelques distinctions, sur la liste deja filtree.
 */
const eleve = (id, name, xp, champs = {}) => ({
    id,
    name,
    periodes: { semaine: { xp, exercices: 1, precision: 80 }, mois: { xp: xp * 2, exercices: 2, precision: 80 }, total: { xp: xp * 10, exercices: 5, precision: 80 } },
    serie: 0,
    jours_actifs: 0,
    progression: null,
    ...champs,
});

test('les rangs suivent les XP de la periode, egalite partagee, zero XP non classe', () => {
    const liste = [eleve(1, 'Awa', 50), eleve(2, 'Paul', 80), eleve(3, 'Zoé', 50), eleve(4, 'Léo', 0)];
    assert.deepEqual(rangs(liste, 'semaine'), { 2: 1, 1: 2, 3: 2 });
    assert.deepEqual(podium(liste).map((p) => [p.eleve.name, p.rang, p.xp]), [['Paul', 1, 80], ['Awa', 2, 50], ['Zoé', 2, 50]]);
    assert.equal(podium(liste, 'total')[0].xp, 800);
    assert.deepEqual(podium([eleve(4, 'Léo', 0)]), []);
});

test('les distinctions : en feu des 7 jours, un seul plus regulier et une seule meilleure progression', () => {
    const liste = [
        eleve(1, 'Awa', 10, { serie: 7, jours_actifs: 12, progression: 3 }),
        eleve(2, 'Paul', 10, { serie: 9, jours_actifs: 12, progression: 15 }),
        eleve(3, 'Zoé', 10, { serie: 6, jours_actifs: 2, progression: 20 }),
    ];
    assert.deepEqual(distinctions(liste), { 1: ['en_feu', 'regulier'], 2: ['en_feu'], 3: ['progression'] });
});

test('pas de distinction sous les seuils', () => {
    const liste = [eleve(1, 'Awa', 10, { jours_actifs: 2, progression: 4 })];
    assert.deepEqual(distinctions(liste), {});
});
