import assert from 'node:assert/strict';
import { test } from 'node:test';
import { enUnites, formaterMontant } from '../../resources/js/lib/montant-stripe.js';

/**
 * Les commissions sont stockees comme Stripe les envoie : en centimes pour
 * l'euro, en francs pour le franc CFA, qui n'a pas de centimes.
 */
test("l'euro est en centimes", () => {
    assert.equal(enUnites(2500, 'eur'), 25);
    assert.equal(formaterMontant(2500, 'EUR'), '25,00\u00a0€');
});

test("le franc CFA n'a pas de centimes", () => {
    assert.equal(enUnites(5000, 'XAF'), 5000);
    assert.equal(formaterMontant(5000, 'XAF'), '5\u00a0000\u00a0FCFA');
});

test('une reprise apres remboursement reste negative', () => {
    assert.equal(formaterMontant(-625, 'EUR'), '-6,25\u00a0€');
});
