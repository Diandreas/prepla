<?php

use App\Models\Exercise;

/**
 * Trois familles d'exercices etaient jugees « impossibles a repondre » a 100 % :
 * leurs champs ne s'appellent ni « notes » ni « fields ». Les quatre chemins qui
 * filtrent jetaient donc l'exercice entier, en silence.
 */
test('associations, etiquetage et organigramme sont jouables', function () {
    expect(Exercise::questionIsAnswerable([
        'id' => 'q1', 'type' => 'multiple-matching',
        'texts' => [['id' => 't1', 'title' => 'A', 'content' => '…']],
        'statements' => [['id' => 's1', 'text' => 'Phrase', 'correct_text_id' => 't1']],
    ]))->toBeTrue();

    expect(Exercise::questionIsAnswerable([
        'id' => 'q2', 'type' => 'diagram-labeling',
        'labels' => [['id' => 'l1', 'text' => 'Etiquette']],
    ]))->toBeTrue();

    expect(Exercise::questionIsAnswerable([
        'id' => 'q3', 'type' => 'flow-chart-completion',
        'steps' => ['Premiere etape', ''],
    ]))->toBeTrue();

    // Et le garde-fou tient toujours : rien a remplir reste injouable.
    expect(Exercise::questionIsAnswerable([
        'id' => 'q4', 'type' => 'note-completion',
        'notes' => [['label' => 'Nom', 'value' => 'Deja rempli']],
    ]))->toBeFalse();
});
