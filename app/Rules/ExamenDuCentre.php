<?php

namespace App\Rules;

use App\Models\LanguageCenter;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * L'examen choisi fait partie de ceux que le centre propose (center_exam).
 *
 * Masquer un examen des listes ne suffit pas : un formulaire envoye a la main
 * le choisirait encore. Sans centre, ou sans selection, tout examen passe.
 * L'examen deja choisi reste accepte : un eleve qui modifie un autre reglage
 * ne doit pas etre bloque parce que son centre a retire son examen depuis.
 */
class ExamenDuCentre implements ValidationRule
{
    public function __construct(
        private readonly ?LanguageCenter $centre,
        private readonly int|string|null $dejaChoisi = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->dejaChoisi !== null && (string) $value === (string) $this->dejaChoisi) {
            return;
        }

        if ($this->centre !== null && ! $this->centre->proposeExamen(is_scalar($value) ? (string) $value : null)) {
            $fail("Cet examen n'est pas proposé par votre centre.");
        }
    }
}
