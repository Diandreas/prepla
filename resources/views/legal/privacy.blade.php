@extends('legal.layout')

@section('title', 'Politique de confidentialité')
@section('updated', '11 juillet 2026')

@section('content')

    <p>PrePla (édité par Mirlab, ci-après « nous ») propose une plateforme de préparation
    aux examens de langue (anglais, français, allemand) avec exercices générés par IA.
    Cette page explique quelles données nous collectons, pourquoi, et comment vous pouvez
    les contrôler.</p>

    <h2>1. Données que nous collectons</h2>
    <ul>
        <li><strong>Compte :</strong> nom, adresse email, mot de passe (stocké de façon
        chiffrée, jamais en clair), ou identifiant Google si vous vous connectez via
        Google.</li>
        <li><strong>Profil d'apprentissage :</strong> langue et examen visés, niveau
        estimé, réponses aux exercices, scores, séries de pratique (« streak »),
        progression dans le parcours.</li>
        <li><strong>Audio :</strong> si vous utilisez les exercices d'expression orale,
        votre enregistrement vocal est envoyé à notre prestataire de transcription pour
        être converti en texte et évalué, puis n'est pas conservé au-delà de ce
        traitement.</li>
        <li><strong>Paiement :</strong> géré entièrement par Stripe. Nous ne recevons ni
        ne stockons jamais votre numéro de carte bancaire.</li>
        <li><strong>Notifications :</strong> si vous activez les rappels, un identifiant
        d'abonnement push (fourni par votre navigateur) est enregistré pour vous envoyer
        des notifications de rappel de pratique.</li>
        <li><strong>Technique :</strong> adresse IP, journaux de connexion, préférence
        d'affichage clair/sombre (stockée localement dans votre navigateur).</li>
    </ul>

    <h2>2. Pourquoi nous utilisons ces données</h2>
    <ul>
        <li>Fournir le service : générer vos exercices, suivre votre progression,
        adapter la difficulté à votre niveau.</li>
        <li>Gérer votre abonnement et la facturation.</li>
        <li>Vous envoyer des rappels de pratique (email ou notification push), si vous
        les avez activés.</li>
        <li>Assurer la sécurité du compte et prévenir les abus.</li>
        <li>Améliorer la qualité des exercices et du parcours pédagogique.</li>
    </ul>

    <h2>3. Partage avec des prestataires tiers</h2>
    <p>Nous ne vendons jamais vos données. Certains traitements sont sous-traités à des
    prestataires spécialisés, uniquement pour les besoins du service :</p>
    <ul>
        <li><strong>Stripe</strong> — paiement et facturation des abonnements.</li>
        <li><strong>Mistral AI</strong> — génération des exercices et du test de
        placement.</li>
        <li><strong>Deepgram</strong> — synthèse et reconnaissance vocale (lecture audio
        des exercices, transcription des réponses orales).</li>
        <li><strong>Forvo</strong> — prononciations audio de référence.</li>
        <li><strong>Google</strong> — connexion via votre compte Google, si vous
        choisissez cette option.</li>
    </ul>

    <h2>4. Conservation des données</h2>
    <p>Vos données sont conservées tant que votre compte est actif. Si vous supprimez
    votre compte ou nous en faites la demande, nous supprimons vos données personnelles
    dans un délai raisonnable, sauf obligation légale de conservation (ex : factures).</p>

    <h2>5. Vos droits</h2>
    <p>Conformément au RGPD, vous pouvez à tout moment demander l'accès, la
    rectification, l'effacement ou l'export de vos données, ou vous opposer à un
    traitement. Il vous suffit d'écrire à
    <a href="mailto:prepla.mirlab@gmail.com">prepla.mirlab@gmail.com</a>.</p>

    <h2>6. Cookies et stockage local</h2>
    <p>Nous utilisons uniquement des cookies techniques nécessaires au fonctionnement du
    service (session, protection CSRF) et le stockage local de votre navigateur pour
    mémoriser votre préférence d'affichage clair/sombre. Aucun cookie publicitaire ou de
    tracking tiers n'est utilisé.</p>

    <h2>7. Sécurité</h2>
    <p>Les mots de passe sont chiffrés, les échanges passent par HTTPS, et l'accès aux
    données est restreint au strict nécessaire.</p>

    <h2>8. Utilisateurs mineurs</h2>
    <p>PrePla peut être utilisé par des lycéens ou étudiants préparant un examen. Si vous
    avez moins de 15 ans, l'inscription doit se faire avec l'accord d'un parent ou
    tuteur légal.</p>

    <h2>9. Modifications</h2>
    <p>Cette politique peut évoluer ; la date de dernière mise à jour en haut de page
    reflète la version en vigueur.</p>

    <h2>10. Contact</h2>
    <p>Pour toute question sur vos données ou cette politique :</p>
    <ul>
        <li>Email : <a href="mailto:prepla.mirlab@gmail.com">prepla.mirlab@gmail.com</a></li>
        <li>WhatsApp support : <a href="https://wa.me/237693427913">+237 693 42 79 13</a></li>
    </ul>

@endsection
