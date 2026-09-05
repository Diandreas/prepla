@extends('legal.layout')

@section('title', 'Suppression du compte et des données')
@section('updated', '5 septembre 2026')

@section('content')

    <p>Cette page explique comment supprimer définitivement votre compte PrePla et les
    données qui lui sont associées. Vous pouvez effectuer l'opération vous-même depuis
    le site, sans réinstaller l'application.</p>

    <h2>Supprimer votre compte</h2>
    <ol>
        <li><a href="{{ route('login') }}">Connectez-vous à votre compte PrePla</a>.</li>
        <li>Ouvrez <strong>Profil</strong>, puis faites défiler jusqu'à la
        <strong>Zone de danger</strong>.</li>
        <li>Sélectionnez <strong>Supprimer mon compte</strong>, confirmez votre mot de
        passe, puis validez la suppression définitive.</li>
    </ol>

    <p>Si vous ne pouvez plus accéder à votre compte, envoyez une demande depuis
    l'adresse email associée au compte à
    <a href="mailto:prepla.mirlab@gmail.com?subject=Suppression%20de%20mon%20compte%20PrePla">prepla.mirlab@gmail.com</a>.
    Nous pourrons vous demander de vérifier que le compte vous appartient avant de
    traiter la demande.</p>

    <h2>Données supprimées</h2>
    <p>La suppression du compte efface les informations de profil et d'apprentissage
    associées : progression, réponses et résultats d'exercices, vocabulaire, séries,
    accomplissements, préférences et abonnements aux notifications.</p>

    <h2>Données pouvant être conservées</h2>
    <p>Les justificatifs de paiement ou éléments strictement nécessaires au respect de
    nos obligations légales peuvent être conservés pendant la durée imposée par la loi.
    Une empreinte cryptographique irréversible de l'adresse email peut également être
    conservée pour empêcher l'utilisation répétée abusive d'un essai gratuit. Elle ne
    permet pas de récupérer l'adresse d'origine. Ces éléments ne sont pas utilisés à des
    fins publicitaires.</p>

    <p>Consultez également notre
    <a href="{{ route('privacy') }}">politique de confidentialité</a>.</p>

@endsection
