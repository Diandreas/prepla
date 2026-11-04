import assert from 'node:assert/strict';
import { test } from 'node:test';
import {
    brouillonUtile,
    cocherOption,
    choixVraiFaux,
    competenceDe,
    deplacer,
    descriptionDuType,
    dupliquer,
    ecrireObjet,
    filtrerExercices,
    indexDeLaBonneReponse,
    libelleDuType,
    libelleExercice,
    lireObjet,
    modeDeReponse,
    niveauDeLaClasse,
    nombreDeQuestions,
    optionsDeFiltre,
    problemesDeLaQuestion,
    problemesDeLExercice,
    questionsACompleter,
    questionsAEnvoyer,
    regrouperParCompetence,
    resumeACompleter,
    retirer,
    retirerOption,
} from '../../resources/js/lib/createur-exercices.js';

/**
 * Le createur d'exercices d'un centre : un responsable lit des noms francais,
 * coche la bonne reponse au lieu d'en taper la lettre, et voit avant l'envoi
 * ce qui manque a chaque question.
 */

const QCM = { famille: 'exact-match', componentKey: 'mcq' };

test('les types portent un nom francais, plus precis que le format quand le slug le permet', () => {
    assert.equal(libelleDuType({ slug: 'mcq', component_key: 'mcq', name: 'Multiple Choice' }), 'Questions à choix multiples');
    assert.equal(libelleDuType({ slug: 'matching-headings', component_key: 'matching', name: 'Matching Headings' }), 'Associer des titres aux paragraphes');
    // Un slug inconnu retombe sur le format, jamais sur le nom anglais.
    assert.equal(libelleDuType({ slug: 'inconnu', component_key: 'true-false-ng', name: 'True/False/Not Given' }), 'Vrai / Faux / Non mentionné');
    // Rien de connu : le nom donne, a defaut « Exercice ».
    assert.equal(libelleDuType({ slug: 'x', component_key: 'y', name: 'Format maison' }), 'Format maison');
    assert.equal(libelleDuType(null), 'Exercice');
    assert.match(descriptionDuType({ slug: 'mcq', component_key: 'mcq' }), /choisit la bonne/);
});

test('les types se rangent par competence, parties officielles en tete', () => {
    const types = [
        { id: 1, slug: 'essay', component_key: 'essay-editor', skill_type: 'writing' },
        { id: 2, slug: 'mcq', component_key: 'mcq', skill_type: 'reading' },
        { id: 3, slug: 'gap-fill', component_key: 'gap-fill', skill_type: 'reading' },
        { id: 4, slug: 'grammar-mcq', component_key: 'mcq', skill_type: 'vocabulary' },
        { id: 5, slug: 'mcq', component_key: 'mcq', skill_type: 'listening' },
    ];
    const groupes = regrouperParCompetence(types, { 3: [{ name: 'Lesen, Teil 2' }] });
    assert.deepEqual(groupes.map((g) => g.competence.nom), [
        'Compréhension écrite', 'Compréhension orale', 'Expression écrite', 'Grammaire et vocabulaire',
    ]);
    // Le texte a trous est une partie officielle : il passe devant le QCM.
    assert.deepEqual(groupes[0].types.map((t) => t.id), [3, 2]);
    assert.equal(competenceDe('vocabulary').cle, 'grammar');
    assert.equal(competenceDe('inconnu').nom, 'Autres exercices');
});

test('la bonne reponse d un QCM se retrouve par sa lettre ou par le texte de l option', () => {
    assert.equal(indexDeLaBonneReponse({ options: ['a', 'b', 'c'], correct_answer: 'B' }), 1);
    assert.equal(indexDeLaBonneReponse({ options: ['Berlin', 'Paris'], correct_answer: 'paris' }), 1);
    assert.equal(indexDeLaBonneReponse({ options: ['a', 'b'], correct_answer: 'D' }), -1);
    assert.equal(indexDeLaBonneReponse({ options: ['a', 'b'], correct_answer: '' }), -1);
});

test('le mode de reponse suit le format, et le texte a trous accepte les deux', () => {
    assert.equal(modeDeReponse('mcq', {}), 'choix');
    assert.equal(modeDeReponse('true-false-ng', {}), 'vrai-faux');
    assert.equal(modeDeReponse('gap-fill', { options: ['a', 'b'] }), 'choix');
    assert.equal(modeDeReponse('gap-fill', {}), 'texte');
    assert.equal(modeDeReponse('short-answer', {}), 'texte');
});

test('une question sans enonce ou sans bonne reponse est signalee avant l envoi', () => {
    const champs = (q, ctx = QCM) => problemesDeLaQuestion(q, ctx).map((p) => p.champ);
    assert.deepEqual(champs({ text: '', options: ['a', 'b'], correct_answer: '' }), ['text', 'correct_answer']);
    assert.deepEqual(champs({ text: 'Wo?', options: ['a', 'b'], correct_answer: 'A' }), []);
    assert.deepEqual(champs({ text: 'Wo?', options: ['a', 'a'], correct_answer: 'A' }), ['options']);
    assert.deepEqual(champs({ text: 'Wo?', options: ['a', ''], correct_answer: 'A' }), ['options']);
    assert.deepEqual(champs({ text: 'Wo?', options: ['a'], correct_answer: 'A' }), ['options']);

    const vf = { famille: 'exact-match', componentKey: 'true-false-ng' };
    assert.deepEqual(champs({ text: 'Anna ist da.', correct_answer: '' }, vf), ['correct_answer']);
    assert.deepEqual(champs({ text: 'Anna ist da.', correct_answer: 'False' }, vf), []);
    // Deux choix seulement (Richtig / Falsch) : « Not Given » n'est pas une reponse.
    assert.deepEqual(champs({ text: 'x', answer_options: ['Richtig', 'Falsch'], correct_answer: 'Not Given' }, vf), ['correct_answer']);

    // Ecouter et repeter : la phrase entendue tient lieu d'enonce et de reponse.
    assert.deepEqual(champs({ text: '', audio_text: 'Guten Tag', correct_answer: '' }, { famille: 'exact-match', componentKey: 'listen-repeat' }), []);

    assert.deepEqual(champs({ text: 'x', correct_answers: { 1: '' } }, { famille: 'multi-field', componentKey: 'note-completion' }), ['correct_answers']);
    assert.deepEqual(champs({ text: 'x', items: ['un', ''] }, { famille: 'order', componentKey: 'ordering' }), ['items']);
    assert.deepEqual(champs({ text: 'Écris', min_words: 200, max_words: 100 }, { famille: 'ai-writing', componentKey: 'essay-editor' }), ['words']);
    assert.deepEqual(champs({ text: 'Parle', expected_points: [''] }, { famille: 'ai-speaking', componentKey: 'speaking-recorder' }), ['expected_points']);
});

test('le compteur dit combien de questions restent a completer', () => {
    const questions = [
        { text: 'ok', options: ['a', 'b'], correct_answer: 'A' },
        { text: '', options: ['a', 'b'], correct_answer: 'A' },
        { text: 'ok', options: ['a', 'b'], correct_answer: '' },
    ];
    assert.deepEqual(questionsACompleter(questions, QCM), [1, 2]);
    assert.equal(resumeACompleter(2), '2 questions à compléter');
    assert.equal(resumeACompleter(1), '1 question à compléter');
    assert.equal(resumeACompleter(0), '');
});

test('une ecoute sans enregistrement ni texte a lire est refusee', () => {
    const q = [{ text: 'Wo ist Anna?' }];
    assert.equal(problemesDeLExercice({ skillType: 'listening', content: {}, questions: q }).length, 1);
    assert.deepEqual(problemesDeLExercice({ skillType: 'listening', content: { audio_text: 'Anna ist im Park.' }, questions: q }), []);
    assert.deepEqual(problemesDeLExercice({ skillType: 'reading', content: {}, questions: q }), []);
    assert.deepEqual(problemesDeLExercice({ skillType: 'reading', content: {}, questions: [] }), ['Ajoutez au moins une question.']);
});

test('le son commun est recopie sur chaque question que le lecteur jouera', () => {
    const sortie = questionsAEnvoyer([{ text: 'a' }, { text: 'b', audio_url: '/propre.mp3' }], { audio_url: '/commun.mp3', audio_text: 'Texte' });
    assert.equal(sortie[0].audio_url, '/commun.mp3');
    assert.equal(sortie[0].audio_text, 'Texte');
    assert.equal(sortie[1].audio_url, '/propre.mp3');
});

test('monter, descendre, dupliquer et retirer une question', () => {
    const l = [{ id: 'q1', text: 'a' }, { id: 'q2', text: 'b' }];
    assert.deepEqual(deplacer(l, 1, -1).map((q) => q.text), ['b', 'a']);
    assert.equal(deplacer(l, 0, -1), l);
    const d = dupliquer(l, 0);
    assert.deepEqual(d.map((q) => q.text), ['a', 'a', 'b']);
    assert.equal(d[1].id, '');
    d[1].text = 'modifie';
    assert.equal(l[0].text, 'a'); // la copie ne touche pas l'original
    assert.deepEqual(retirer(l, 0).map((q) => q.id), ['q2']);
});

test('le vrai/faux affiche les choix que la partie declare', () => {
    assert.deepEqual(choixVraiFaux({}).map((c) => c.label), ['Vrai', 'Faux', 'Non mentionné']);
    assert.deepEqual(choixVraiFaux({ answer_options: ['Richtig', 'Falsch'] }).map((c) => c.value), ['Richtig', 'Falsch']);
});

test('le brouillon se lit et s ecrit sans jamais faire planter la page', () => {
    const memoire = new Map();
    const stockage = { getItem: (k) => memoire.get(k) ?? null, setItem: (k, v) => memoire.set(k, v), removeItem: (k) => memoire.delete(k) };
    assert.equal(ecrireObjet(stockage, 'cle', { typeId: 3, questions: [{ text: 'Hallo' }] }), true);
    assert.equal(lireObjet(stockage, 'cle').typeId, 3);
    assert.equal(brouillonUtile(lireObjet(stockage, 'cle')), true);
    assert.equal(brouillonUtile({ typeId: 3, questions: [{ text: '' }] }), false);

    const casse = { getItem: () => { throw new Error('refuse'); }, setItem: () => { throw new Error('plein'); } };
    assert.equal(lireObjet(casse, 'cle'), null);
    assert.equal(ecrireObjet(casse, 'cle', {}), false);
    assert.equal(lireObjet(null, 'cle'), null);
    memoire.set('illisible', '{pas du json');
    assert.equal(lireObjet(stockage, 'illisible'), null);
});

test('la liste se filtre par examen, niveau, competence, origine et recherche sans accents', () => {
    const lignes = [
        { id: 1, titre: 'Au marché', type_slug: 'mcq', component_key: 'mcq', skill_type: 'reading', difficulty: 'A2', exam_id: 1, exam: 'Goethe', source: 'centre' },
        { id: 2, titre: '', type_slug: 'true-false', component_key: 'true-false-ng', skill_type: 'listening', difficulty: 'B1', exam_id: 1, exam: 'Goethe', source: 'prepla' },
        { id: 3, titre: 'La santé', type_slug: 'essay', component_key: 'essay-editor', skill_type: 'writing', difficulty: 'B1', exam_id: 2, exam: 'TCF', source: 'centre' },
    ];
    const ids = (f) => filtrerExercices(lignes, f).map((l) => l.id);
    assert.deepEqual(ids({}), [1, 2, 3]);
    assert.deepEqual(ids({ examen: '1' }), [1, 2]);
    assert.deepEqual(ids({ niveau: 'B1' }), [2, 3]);
    assert.deepEqual(ids({ competence: 'listening' }), [2]);
    assert.deepEqual(ids({ source: 'prepla' }), [2]);
    assert.deepEqual(ids({ recherche: 'marche' }), [1]);
    assert.deepEqual(ids({ recherche: 'sante tcf' }), [3]);
    assert.deepEqual(ids({ recherche: 'vrai' }), [2]);
    // Sans titre, l'exercice prend le nom francais de son type.
    assert.equal(libelleExercice(lignes[1]), 'Vrai ou faux');
    assert.deepEqual(optionsDeFiltre(lignes).niveaux, ['A2', 'B1']);
});

test('retirer une proposition garde la bonne reponse sur la meme proposition', () => {
    const q = { text: 'Q', options: ['a', 'b', 'c', 'd'], correct_answer: 'C' };
    // On retire B : l'ancienne C devient B, et reste cochee.
    assert.deepEqual(retirerOption(q, 1), { text: 'Q', options: ['a', 'c', 'd'], correct_answer: 'B' });
    // On retire une proposition apres la bonne : la lettre ne bouge pas.
    assert.equal(retirerOption(q, 3).correct_answer, 'C');
    // On retire la bonne reponse elle-meme : plus rien n'est coche.
    assert.equal(retirerOption(q, 2).correct_answer, '');
    // Un ancien exercice qui portait le texte de l'option : retrouve, puis converti en lettre.
    assert.equal(retirerOption({ options: ['x', 'y', 'z'], correct_answer: 'z' }, 0).correct_answer, 'B');
    assert.equal(cocherOption(q, 0).correct_answer, 'A');
    assert.equal(retirerOption(q, 9), q);
});

test('le nombre de questions et le niveau de la classe se lisent sans surprise', () => {
    assert.equal(nombreDeQuestions(''), null);
    assert.equal(nombreDeQuestions('0'), null);
    assert.equal(nombreDeQuestions('6'), 6);
    assert.equal(nombreDeQuestions(80), 20);
    assert.equal(nombreDeQuestions('abc'), null);
    assert.equal(niveauDeLaClasse('B1 soir'), 'B1');
    assert.equal(niveauDeLaClasse('a2'), 'A2');
    assert.equal(niveauDeLaClasse('Débutants'), null);
    assert.equal(niveauDeLaClasse(null), null);
});
