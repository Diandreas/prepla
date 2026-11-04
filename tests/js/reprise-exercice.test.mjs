import assert from 'node:assert/strict';
import { test } from 'node:test';
import {
    cleDeReprise, DUREE_DE_VIE_MS, ecrireReprise, effacerReprise, lireReprise, reponsesLegeres,
} from '../../resources/js/lib/reprise-exercice.js';

function stockage() {
    const m = new Map();
    return {
        m,
        getItem: (k) => (m.has(k) ? m.get(k) : null),
        setItem: (k, v) => { m.set(k, String(v)); },
        removeItem: (k) => { m.delete(k); },
    };
}

const IDS = ['q1', 'q2', 'q3'];
const T0 = 1_800_000_000_000;

test('une reprise relit l index et les reponses donnees', () => {
    const s = stockage();
    const cle = cleDeReprise(12, 7);
    assert.equal(cle, 'prepla-reprise-7-12');
    assert.ok(ecrireReprise(s, cle, { index: 2, answers: { q1: 'A', q2: { 0: 'is' } }, questionIds: IDS, tempsPasse: 42 }, T0));
    assert.deepEqual(lireReprise(s, cle, IDS, T0 + 1000), { index: 2, answers: { q1: 'A', q2: { 0: 'is' } }, tempsPasse: 42 });
});

test('le visiteur sans compte et chaque eleve ont leur propre cle', () => {
    assert.equal(cleDeReprise(12, null), 'prepla-reprise-essai-12');
    assert.notEqual(cleDeReprise(12, 7), cleDeReprise(12, 8));
});

test('les enregistrements audio ne sont jamais ranges', () => {
    const fichier = new Blob(['son']);
    assert.deepEqual(reponsesLegeres({ q1: 'A', q2: fichier, q3: ['b', 'a'] }), { q1: 'A', q3: ['b', 'a'] });

    // La question dont l'enregistrement est perdu est reposee, pas sautee.
    const s = stockage();
    ecrireReprise(s, 'k', { index: 2, answers: { q1: 'A', q2: fichier }, questionIds: IDS, tempsPasse: 0 }, T0);
    assert.deepEqual(lireReprise(s, 'k', IDS, T0), { index: 1, answers: { q1: 'A' }, tempsPasse: 0 });
});

test('rien n est ecrit tant qu aucune reponse n est donnee', () => {
    const s = stockage();
    ecrireReprise(s, 'k', { index: 1, answers: { q1: 'A' }, questionIds: IDS, tempsPasse: 0 }, T0);
    assert.equal(ecrireReprise(s, 'k', { index: 0, answers: {}, questionIds: IDS, tempsPasse: 0 }, T0), false);
    assert.ok(lireReprise(s, 'k', IDS, T0), 'la sauvegarde existante est gardee');
});

test('une sauvegarde de plus de sept jours est effacee', () => {
    const s = stockage();
    ecrireReprise(s, 'k', { index: 1, answers: { q1: 'A' }, questionIds: IDS, tempsPasse: 0 }, T0);
    assert.equal(lireReprise(s, 'k', IDS, T0 + DUREE_DE_VIE_MS), null);
    assert.equal(s.m.size, 0);
});

test('une sauvegarde faite pour d autres questions est effacee', () => {
    const s = stockage();
    ecrireReprise(s, 'k', { index: 1, answers: { q1: 'A' }, questionIds: IDS, tempsPasse: 0 }, T0);
    assert.equal(lireReprise(s, 'k', ['q1', 'q2'], T0), null);
    assert.equal(s.m.size, 0);

    ecrireReprise(s, 'k', { index: 1, answers: { q1: 'A' }, questionIds: IDS, tempsPasse: 0 }, T0);
    assert.equal(lireReprise(s, 'k', ['q1', 'q2', 'q9'], T0), null);
    assert.equal(s.m.size, 0);
});

test('une sauvegarde illisible est effacee', () => {
    const s = stockage();
    s.setItem('k', '{pas du json');
    assert.equal(lireReprise(s, 'k', IDS, T0), null);
    assert.equal(s.m.size, 0);
});

test('seules les reponses de l eleve sont rangees, jamais l enonce ni la correction', () => {
    const s = stockage();
    ecrireReprise(s, 'k', { index: 0, answers: { q1: 'A' }, questionIds: IDS, tempsPasse: 0 }, T0);
    assert.deepEqual(Object.keys(JSON.parse(s.m.get('k'))).sort(), ['enregistre_le', 'index', 'questions', 'reponses', 'temps', 'v']);
});

test('sans stockage, ou quand il refuse, rien ne casse', () => {
    assert.equal(lireReprise(null, 'k', IDS), null);
    assert.equal(ecrireReprise(null, 'k', { index: 0, answers: { q1: 'A' }, questionIds: IDS, tempsPasse: 0 }), false);
    effacerReprise(null, 'k');

    const hostile = {
        getItem() { throw new Error('SecurityError'); },
        setItem() { throw new Error('QuotaExceededError'); },
        removeItem() { throw new Error('SecurityError'); },
    };
    assert.equal(lireReprise(hostile, 'k', IDS), null);
    assert.equal(ecrireReprise(hostile, 'k', { index: 0, answers: { q1: 'A' }, questionIds: IDS, tempsPasse: 0 }), false);
    effacerReprise(hostile, 'k');
});
