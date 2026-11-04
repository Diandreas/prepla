import assert from 'node:assert/strict';
import { test } from 'node:test';
import {
    activiteCoherente,
    celluleCsv,
    elevesDistincts,
    entreeHistorique,
    joursCalendaires,
    comparerCles,
    compterStatuts,
    correspondRecherche,
    ecrireEtat,
    filtrer,
    lireEtat,
    optionsDe,
    pluriel,
    regrouper,
    SANS,
    versCsv,
} from '../../resources/js/lib/classement-centre.js';

/**
 * Le classement des classes et des eleves d'un centre, dans le navigateur :
 * filtres, regroupements, export. Le responsable de centre range ses groupes
 * par langue, niveau, salle — et un lien copie doit rouvrir la meme vue.
 */
const eleves = [
    { id: 1, name: 'Élodie Ngo', email: 'elodie@exemple.test', statut: 'actif', classes: [{ cle: 10, libelle: 'Groupe A' }, { cle: 11, libelle: 'Groupe B' }], niveaux: ['B1'] },
    { id: 2, name: 'Paul Mbia', email: 'paul@exemple.test', statut: 'a_relancer', classes: [{ cle: 10, libelle: 'Groupe A' }], niveaux: ['A2'] },
    { id: 3, name: 'Awa Diallo', email: 'awa@exemple.test', statut: 'jamais', classes: [], niveaux: [] },
];
const parClasse = (e) => e.classes;
const parNiveau = (e) => e.niveaux.map((n) => ({ cle: n, libelle: n }));

test('la recherche ignore accents et majuscules, mot par mot', () => {
    assert.equal(correspondRecherche('elodie', ['Élodie Ngo']), true);
    assert.equal(correspondRecherche('NGO elo', ['Élodie Ngo']), true);
    assert.equal(correspondRecherche('paul', ['Élodie Ngo', 'elodie@exemple.test']), false);
    assert.equal(correspondRecherche('   ', ['x']), true);
});

test('les niveaux se rangent A1..C2, les niveaux libres apres, « sans » a la fin', () => {
    const cles = ['C1', SANS, 'B1+', 'A2', 'A1', 'C2'].map((cle) => ({ cle, libelle: cle === SANS ? 'Sans niveau' : cle }));
    cles.sort((a, b) => comparerCles(a, b, 'niveau'));
    assert.deepEqual(cles.map((c) => c.cle), ['A1', 'A2', 'C1', 'C2', 'B1+', SANS]);
});

test('l ordre alphabetique est francais et « sans » reste a la fin', () => {
    const cles = [{ cle: SANS, libelle: 'Sans salle' }, { cle: 's10', libelle: 'Salle 10' }, { cle: 's2', libelle: 'Salle 2' }, { cle: 'a', libelle: 'Amphi' }];
    cles.sort((a, b) => comparerCles(a, b));
    assert.deepEqual(cles.map((c) => c.libelle), ['Amphi', 'Salle 2', 'Salle 10', 'Sans salle']);
});

test('un eleve dans deux classes apparait dans les deux groupes', () => {
    const groupes = regrouper(eleves, parClasse, { libelleSans: 'Sans classe' });
    assert.deepEqual(groupes.map((g) => [g.libelle, g.lignes.map((e) => e.id)]), [
        ['Groupe A', [1, 2]],
        ['Groupe B', [1]],
        ['Sans classe', [3]],
    ]);
});

test('les filtres se cumulent, et « sans » filtre les lignes sans valeur', () => {
    const criteres = { classe: { etiquettes: parClasse }, niveau: { etiquettes: parNiveau } };
    const champsRecherche = (e) => [e.name, e.email];
    assert.deepEqual(filtrer(eleves, { criteres, filtres: { classe: '10' } }).map((e) => e.id), [1, 2]);
    assert.deepEqual(filtrer(eleves, { criteres, filtres: { classe: '10', niveau: 'A2' } }).map((e) => e.id), [2]);
    assert.deepEqual(filtrer(eleves, { criteres, filtres: { classe: SANS } }).map((e) => e.id), [3]);
    assert.deepEqual(filtrer(eleves, { criteres, champsRecherche, recherche: 'exemple', filtres: { niveau: '' } }).map((e) => e.id), [1, 2, 3]);
    // Un filtre inconnu (adresse bricolee) n'efface pas la liste.
    assert.equal(filtrer(eleves, { criteres, filtres: { inconnu: 'x' } }).length, 3);
});

test('les listes de filtre viennent des donnees, avec leur effectif', () => {
    assert.deepEqual(optionsDe(eleves, parNiveau, { mode: 'niveau', libelleSans: 'Sans niveau' }), [
        { cle: 'A2', libelle: 'A2', nombre: 1 },
        { cle: 'B1', libelle: 'B1', nombre: 1 },
        { cle: SANS, libelle: 'Sans niveau', nombre: 1 },
    ]);
});

test('les compteurs par statut', () => {
    assert.deepEqual(compterStatuts(eleves), { total: 3, actif: 1, a_relancer: 1, jamais: 1 });
});

test('pluriel', () => {
    assert.equal(pluriel(0, 'élève'), '0 élève');
    assert.equal(pluriel(1, 'classe'), '1 classe');
    assert.equal(pluriel(41, 'élève'), '41 élèves');
});

test('le CSV s ouvre dans Excel : BOM, point-virgule, guillemets, pas de formule', () => {
    const csv = versCsv(['Élève', 'Classe(s)'], [['Ngo; Élodie', 'Groupe "A"'], ['=HYPERLINK("x")', null]]);
    const BOM = String.fromCharCode(0xfeff);
    assert.ok(csv.startsWith(BOM));
    assert.equal(csv, BOM + 'Élève;Classe(s)\r\n"Ngo; Élodie";"Groupe ""A"""\r\n"\'=HYPERLINK(""x"")";\r\n');
    assert.equal(celluleCsv(42), '42');
    assert.equal(celluleCsv('-3'), "'-3");
});

test('l adresse garde la vue et ses autres parametres', () => {
    assert.deepEqual(lireEtat('?groupe=niveau&langue=german&x=1', ['groupe', 'langue', 'salle']), { groupe: 'niveau', langue: 'german', salle: '' });
    assert.equal(ecrireEtat('?x=1&salle=2', { groupe: 'salle', salle: '', q: 'awa' }), '?x=1&groupe=salle&q=awa');
    assert.equal(ecrireEtat('', { groupe: '' }), '');
    // Ce qu'on ecrit se relit a l'identique.
    const adresse = ecrireEtat('', { salle: 'Salle 2 & 3', niveau: SANS });
    assert.deepEqual(lireEtat(adresse, ['salle', 'niveau']), { salle: 'Salle 2 & 3', niveau: SANS });
});

test('un groupe de classes compte des personnes, pas des inscriptions', () => {
    // Awa (1) suit les deux classes d'allemand : 3 personnes, pas 4.
    assert.equal(elevesDistincts([{ student_ids: [1, 2] }, { student_ids: [1, 3] }]), 3);
    assert.equal(elevesDistincts([{ student_ids: [] }, {}]), 0);
});

test('changer de vue garde l entree d historique d Inertia, avec la nouvelle adresse', () => {
    const avant = { page: { component: 'center/students/index', url: '/center/students', props: { a: 1 } }, scrollRegions: [] };
    const apres = entreeHistorique(avant, '/center/students?groupe=salle');
    assert.equal(apres.page.url, '/center/students?groupe=salle');
    assert.equal(apres.page.component, 'center/students/index');
    assert.deepEqual(apres.scrollRegions, []);
    // L'entree d'origine n'est pas modifiee en place.
    assert.equal(avant.page.url, '/center/students');
    // Page chiffree (illisible) ou pas d'etat : rendue telle quelle.
    const chiffree = { page: new ArrayBuffer(8) };
    assert.equal(entreeHistorique(chiffree, '/x'), chiffree);
    assert.equal(entreeHistorique(null, '/x'), null);
});

test('la derniere activite ne contredit pas le statut autour des 7 jours', () => {
    const maintenant = new Date(2026, 10, 12, 10, 0, 0);
    const formater = (iso) => `ilYA(${joursCalendaires(iso, maintenant)})`;
    const il_y_a = (jours, heures = 0) => new Date(maintenant.getTime() - (jours * 24 + heures) * 3_600_000).toISOString();

    assert.equal(joursCalendaires(il_y_a(0), maintenant), 0);
    assert.equal(joursCalendaires(il_y_a(7, 3), maintenant), 7);
    // 7 jours et 3 heures : a relancer, mais « il y a 7 jours » au calendrier.
    assert.equal(activiteCoherente(il_y_a(7, 3), 'a_relancer', 7, formater, maintenant), 'il y a plus de 7 jours');
    // Exactement 7 jours : encore actif, la date ordinaire.
    assert.equal(activiteCoherente(il_y_a(7), 'actif', 7, formater, maintenant), 'ilYA(7)');
    assert.equal(activiteCoherente(il_y_a(9), 'a_relancer', 7, formater, maintenant), 'ilYA(9)');
    assert.equal(activiteCoherente(null, 'jamais', 7, () => 'Jamais', maintenant), 'Jamais');
});
