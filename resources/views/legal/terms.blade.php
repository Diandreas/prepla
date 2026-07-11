@extends('legal.layout')

@section('title', "Conditions générales d'utilisation")
@section('updated', '11 juillet 2026')

@section('content')

    <p>Les présentes conditions régissent l'utilisation de PrePla, plateforme de
    préparation aux examens de langue (anglais, français, allemand) avec exercices
    générés par IA. En créant un compte, vous acceptez ces conditions.</p>

    <h2>1. Le service</h2>
    <p>PrePla propose un test de placement, un parcours d'exercices personnalisé et des
    outils de pratique (grammaire, compréhension, expression écrite et orale) pour vous
    préparer à un examen de langue (IELTS, TOEFL, DELF, Goethe...). Les exercices sont
    en grande partie générés automatiquement par intelligence artificielle.</p>

    <h2>2. Votre compte</h2>
    <ul>
        <li>Vous devez fournir des informations exactes lors de l'inscription.</li>
        <li>Vous êtes responsable de la confidentialité de votre mot de passe et de
        toute activité effectuée depuis votre compte.</li>
        <li>Un compte est personnel ; sauf accord spécifique (offre établissement/
        centre de formation), il ne doit pas être partagé entre plusieurs personnes.</li>
    </ul>

    <h2>3. Abonnement et paiement</h2>
    <ul>
        <li>PrePla propose un essai gratuit puis des formules d'abonnement payantes
        (mensuelle ou annuelle). La durée de l'essai et les tarifs en vigueur sont
        affichés sur la page « Abonnement » de l'application.</li>
        <li>Le paiement est traité par Stripe. En vous abonnant, vous autorisez le
        renouvellement automatique de votre abonnement au tarif en vigueur, jusqu'à
        résiliation de votre part.</li>
        <li>Vous pouvez annuler à tout moment depuis la page « Abonnement » ; l'accès
        premium reste actif jusqu'à la fin de la période déjà payée, sans reconduction
        ultérieure.</li>
        <li>Sauf disposition légale contraire applicable à votre pays de résidence,
        les sommes déjà prélevées pour une période en cours ne sont pas remboursées au
        prorata en cas d'annulation en cours de période.</li>
    </ul>

    <h2>4. Contenu généré par IA</h2>
    <p>Les exercices, corrections et évaluations de niveau sont en grande partie
    produits automatiquement par des modèles d'IA. Malgré nos vérifications, ce contenu
    peut occasionnellement contenir des erreurs ou imprécisions. PrePla est un outil de
    préparation et d'entraînement : il ne garantit pas un résultat particulier à
    l'examen officiel et ne remplace pas l'avis d'un enseignant certifié.</p>

    <h2>5. Usage autorisé</h2>
    <p>Vous vous engagez à ne pas :</p>
    <ul>
        <li>Revendre, redistribuer ou exploiter commercialement le contenu de PrePla
        sans autorisation ;</li>
        <li>Tenter d'extraire massivement le contenu du service (scraping automatisé) ;</li>
        <li>Utiliser le service à des fins illégales ou pour nuire à d'autres
        utilisateurs.</li>
    </ul>

    <h2>6. Propriété intellectuelle</h2>
    <p>La plateforme PrePla (marque, interface, code, méthode pédagogique) reste la
    propriété de Mirlab. Vos propres réponses et enregistrements vous appartiennent ;
    vous nous accordez uniquement le droit de les traiter pour vous fournir le
    service (correction, suivi de progression).</p>

    <h2>7. Résiliation</h2>
    <p>Vous pouvez supprimer votre compte à tout moment. Nous pouvons suspendre ou
    résilier un compte en cas de violation manifeste de ces conditions (fraude, abus,
    partage non autorisé de compte).</p>

    <h2>8. Limitation de responsabilité</h2>
    <p>PrePla est fourni « en l'état ». Dans la limite permise par la loi, nous ne
    pourrons être tenus responsables des dommages indirects liés à l'utilisation du
    service, ni d'un résultat insuffisant à un examen officiel.</p>

    <h2>9. Modifications</h2>
    <p>Ces conditions peuvent évoluer ; la date de mise à jour en haut de page reflète
    la version en vigueur. Une utilisation continue du service après modification vaut
    acceptation des nouvelles conditions.</p>

    <h2>10. Contact et support</h2>
    <ul>
        <li>Email : <a href="mailto:prepla.mirlab@gmail.com">prepla.mirlab@gmail.com</a></li>
        <li>WhatsApp support : <a href="https://wa.me/237693427913">+237 693 42 79 13</a></li>
    </ul>

    <p>Voir aussi notre <a href="{{ route('privacy') }}">politique de confidentialité</a>.</p>

@endsection
