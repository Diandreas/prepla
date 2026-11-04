import assert from 'node:assert/strict';
import { test } from 'node:test';
import { contraste, couleurLisible, cssDeLaMarque, estHexa, FOND_SOMBRE, nomDuSite, pourFondSombre, texteSur } from '../../resources/js/lib/marque-centre.js';

/**
 * La marque d'un centre cote client : l'apercu annonce le contraste avant
 * d'enregistrer, et les couleurs posees doivent garder un texte lisible. Les
 * valeurs attendues sont celles du calcul serveur (app/Services/Center/Couleurs.php).
 */
test('le contraste suit la formule WCAG', () => {
    assert.equal(contraste('#000000', '#FFFFFF').toFixed(1), '21.0');
    assert.equal(contraste('#FFFFFF', '#FFFFFF'), 1);
    // Le bleu de PrePla passe lui-meme le minimum de 3:1.
    assert.equal(contraste('#4A90E2', '#FFFFFF').toFixed(2), '3.29');
});

test('une couleur trop claire est refusee, une couleur foncee acceptee', () => {
    assert.equal(couleurLisible('#FFEB3B'), false); // jaune vif
    assert.equal(couleurLisible('#0F766E'), true);
    assert.equal(couleurLisible('rouge'), false);
    assert.equal(estHexa('#12abEF'), true);
    assert.equal(estHexa('#12ab'), false);
    assert.equal(estHexa('<script>'), false);
});

test('le texte pose sur la couleur est le plus lisible des deux', () => {
    assert.equal(texteSur('#0B2D63'), '#FFFFFF');
    assert.equal(texteSur('#FFD54F'), '#0B1322');
});

test('une couleur foncee est eclaircie pour le theme sombre', () => {
    const sombre = pourFondSombre('#0B2D63');
    assert.ok(contraste(sombre, FOND_SOMBRE) >= 3, `${sombre} reste trop sombre`);
    // Une couleur deja claire ne bouge pas.
    assert.equal(pourFondSombre('#FFFFFF'), '#FFFFFF');
});

test('la marque PrePla ne pose aucune variable, celle d un centre les pose', () => {
    assert.equal(cssDeLaMarque(null), '');
    assert.equal(cssDeLaMarque({ theme: null }), '');

    const css = cssDeLaMarque({
        theme: {
            primaire: '#0F766E', texte_sur_primaire: '#FFFFFF',
            primaire_sombre: '#2DD4BF', texte_sur_primaire_sombre: '#0B1322',
            accent: '#9A3412', accent_sombre: '#FB923C',
        },
    });
    assert.match(css, /html:root\{--primary:#0F766E;--primary-foreground:#FFFFFF;/);
    assert.match(css, /html\.dark\{--primary:#2DD4BF;--primary-foreground:#0B1322;/);
});

test('une valeur qui n est pas une couleur n est jamais injectee', () => {
    const css = cssDeLaMarque({
        theme: {
            primaire: 'red}body{display:none', texte_sur_primaire: '#FFFFFF',
            primaire_sombre: '#2DD4BF', texte_sur_primaire_sombre: '#0B1322',
            accent: '#9A3412', accent_sombre: '#FB923C',
        },
    });
    assert.equal(css, '');
});

test('le nom du site ne change qu en marque blanche', () => {
    assert.equal(nomDuSite({ nom: 'Institut Linguae', masquer_prepla: false }, 'PrePla'), 'PrePla');
    assert.equal(nomDuSite({ nom: 'Institut Linguae', masquer_prepla: true }, 'PrePla'), 'Institut Linguae');
    assert.equal(nomDuSite(undefined, 'PrePla'), 'PrePla');
});
