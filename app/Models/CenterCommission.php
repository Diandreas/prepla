<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une commission de parrainage : 25 % d'une facture Stripe payee par un compte
 * qu'un centre a amene (type charge), ou sa reprise apres un remboursement
 * (type remboursement, montants negatifs).
 *
 * Ecrite uniquement par App\Listeners\EnregistrerCommission. Montants dans la
 * plus petite unite de la devise (centimes), comme Stripe.
 */
class CenterCommission extends Model
{
    public const TAUX = 25;

    public const CHARGE = 'charge';

    public const REMBOURSEMENT = 'remboursement';

    protected $fillable = [
        'center_id',
        'user_id',
        'stripe_invoice_id',
        'stripe_charge_id',
        'stripe_subscription_id',
        'amount_paid',
        'currency',
        'rate',
        'commission',
        'periode',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'amount_paid' => 'integer',
            'commission' => 'integer',
            'rate' => 'integer',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(LanguageCenter::class, 'center_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
