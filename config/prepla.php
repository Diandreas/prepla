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

];
