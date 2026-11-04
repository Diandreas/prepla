<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Commissions de parrainage (lot D) : une ligne par facture Stripe payee d'un
 * compte parraine (type charge), une ligne negative par remboursement.
 *
 * stripe_invoice_id est UNIQUE : c'est la base, et non le cache, qui empeche
 * de compter deux fois un evenement rejoue par Stripe. Pour un remboursement,
 * la cle vaut « remb:{charge}:{cumul rembourse} » (voir EnregistrerCommission).
 * stripe_charge_id reste souvent vide sur les lignes charge : en API Stripe
 * basil, la facture ne donne plus son paiement.
 *
 * Montants dans la plus petite unite de la devise, comme Stripe (centimes).
 * Le rapprochement mensuel se fait dans le releve (center_statements, lot C).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('center_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('language_centers')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('stripe_invoice_id')->unique();
            $table->string('stripe_charge_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            $table->integer('amount_paid');
            $table->string('currency', 3);
            $table->unsignedTinyInteger('rate')->default(25);
            $table->integer('commission');
            $table->string('periode', 7);
            $table->string('type', 16)->default('charge');
            $table->timestamps();

            $table->index(['center_id', 'periode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_commissions');
    }
};
