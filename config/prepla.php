<?php

/*
 * Reglages propres a PrePla, hors fournisseurs (voir services.php).
 */

return [

    'generation' => [

        /*
         * La verification « a l'aveugle » d'un exercice genere : un second
         * appel a l'IA resout l'exercice SANS la cle, et l'exercice est refuse
         * si sa reponse differe de la reponse marquee ou s'il trouve deux
         * bonnes options. Releve en production : des reponses marquees justes
         * qui etaient fausses (#464, #2287), des associations a deux bonnes
         * reponses (#581). Cela double le nombre d'appels par exercice ecrit.
         *
         * Activee par defaut. Les tests la coupent (phpunit.xml) et la
         * rallument explicitement la ou ils la verifient.
         */
        'verification_aveugle' => (bool) env('PREPLA_VERIFICATION_AVEUGLE', true),

    ],

    /*
     * Paiement par Mobile Money, active a la main : l'apprenant paie, envoie la
     * capture sur WhatsApp, et l'equipe ouvre son acces depuis /admin/activations.
     * Les prix se changent dans le .env, sans redeployer.
     */
    'mobile_money' => [
        'orange' => env('MOBILE_MONEY_ORANGE', '693427913'),
        'mtn' => env('MOBILE_MONEY_MTN', '683735889'),
        // Numero WhatsApp au format international, sans « + » (lien wa.me).
        'whatsapp' => env('MOBILE_MONEY_WHATSAPP', '237693427913'),
        'prix' => [
            'mensuel' => (int) env('MOBILE_MONEY_PRIX_MENSUEL', 6500),
            'annuel' => (int) env('MOBILE_MONEY_PRIX_ANNUEL', 45000),
        ],
    ],

];
