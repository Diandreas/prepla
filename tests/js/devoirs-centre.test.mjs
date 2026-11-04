import assert from 'node:assert/strict';
import { test } from 'node:test';
import { echeance, jourCourt, syntheseDevoirs, tauxFini, trierDevoirs } from '../../resources/js/lib/devoirs-centre.js';

/**
 * La liste des devoirs du centre : l'enseignant doit lire en un coup d'oeil
 * ce qui est a rendre bientot et ce qui est en retard.
 */
const devoir = (titre, champs = {}) => ({
    titre,
    statut: 'publie',
    due_date: null,
    days_left: null,
    overdue: false,
    en_retard: false,
    created_at: '2026-11-01T10:00:00+00:00',
    progress: { students: 10, finished: 0, started: 0 },
    ...champs,
});

test('le jour de remise se lit sans fuseau, l annee seulement si elle differe', () => {
    assert.equal(jourCourt('2026-11-07', 2026), '7 nov.');
    assert.equal(jourCourt('2027-01-03', 2026), '3 janv. 2027');
    assert.equal(jourCourt(null, 2026), '');
});

test('l echeance dit dans combien de jours, ou le retard', () => {
    assert.deepEqual(echeance(devoir('x')), { texte: 'Sans échéance', detail: '', ton: 'aucune' });
    assert.equal(echeance(devoir('x', { due_date: '2026-11-04', days_left: 0 }), 2026).texte, "Aujourd'hui");
    assert.equal(echeance(devoir('x', { due_date: '2026-11-05', days_left: 1 }), 2026).texte, 'Demain');
    assert.deepEqual(echeance(devoir('x', { due_date: '2026-11-07', days_left: 3 }), 2026), { texte: 'Dans 3 jours', detail: '7 nov.', ton: 'proche' });
    assert.equal(echeance(devoir('x', { due_date: '2026-11-20', days_left: 16 }), 2026).ton, 'loin');

    const retard = echeance(devoir('x', { due_date: '2026-11-01', days_left: -3, overdue: true, en_retard: true }), 2026);
    assert.deepEqual(retard, { texte: 'En retard', detail: 'échu il y a 3 jours', ton: 'retard' });
    const rendu = echeance(devoir('x', { due_date: '2026-11-01', days_left: -3, overdue: true }), 2026);
    assert.equal(rendu.texte, 'Échu le 1 nov.');
    assert.equal(rendu.ton, 'passee');
});

test('le tri par echeance met en tete ce qui est a rendre le plus tot', () => {
    const liste = [
        devoir('termine', { statut: 'termine', due_date: '2026-10-01', days_left: -34, overdue: true }),
        devoir('sans date'),
        devoir('loin', { due_date: '2026-11-20', days_left: 16 }),
        devoir('brouillon', { statut: 'brouillon' }),
        devoir('proche', { due_date: '2026-11-05', days_left: 1 }),
        devoir('termine recent', { statut: 'termine', due_date: '2026-11-02', days_left: -2, overdue: true }),
    ];
    assert.deepEqual(
        trierDevoirs(liste).map((d) => d.titre),
        ['proche', 'loin', 'sans date', 'brouillon', 'termine recent', 'termine'],
    );
    // Copie : la liste d'origine reste dans son ordre.
    assert.equal(liste[0].titre, 'termine');
});

test('le tri par progression montre d abord les classes en retrait', () => {
    const liste = [
        devoir('moitie', { progress: { students: 10, finished: 5, started: 0 } }),
        devoir('vide', { progress: { students: 0, finished: 0, started: 0 } }),
        devoir('rien', { progress: { students: 10, finished: 0, started: 2 } }),
    ];
    assert.deepEqual(trierDevoirs(liste, 'progression').map((d) => d.titre), ['rien', 'moitie', 'vide']);
});

test('la synthese compte les devoirs en cours, de la semaine, en retard et le taux de rendu', () => {
    assert.equal(tauxFini({ students: 3, finished: 2, started: 0 }), 67);
    assert.equal(tauxFini({ students: 0, finished: 0, started: 0 }), null);

    const s = syntheseDevoirs([
        devoir('a', { due_date: '2026-11-06', days_left: 2, progress: { students: 10, finished: 4, started: 0 } }),
        devoir('b', { due_date: '2026-11-30', days_left: 26, progress: { students: 10, finished: 6, started: 0 } }),
        devoir('c', { statut: 'termine', overdue: true, en_retard: true, due_date: '2026-11-01', days_left: -3, progress: { students: 5, finished: 0, started: 0 } }),
        devoir('d', { statut: 'brouillon', progress: { students: 20, finished: 0, started: 0 } }),
    ]);
    assert.deepEqual(s, { total: 4, enCours: 2, cetteSemaine: 1, enRetard: 1, tauxRendu: 40 });
});
