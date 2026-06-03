<x-mail::message>
# Bienvenue sur PrePla, {{ $userName }} !

Votre compte est actif et votre **essai gratuit de {{ $trialDays }} jours** commence dès maintenant.

Pendant votre essai, vous avez accès à toutes les fonctionnalités Premium :

- Accès illimité à tous les examens (IELTS, DELF, Goethe...)
- Correction IA instantanée pour rédactions et oraux
- Expliqueur d'erreurs sur chaque exercice
- Statistiques de progression avancées

<x-mail::button :url="$dashboardUrl" color="primary">
Commencer ma préparation
</x-mail::button>

**Astuce :** Pratiquez au moins 15 minutes par jour pour progresser rapidement. PrePla vous enverra des rappels pour maintenir votre rythme.

Après vos {{ $trialDays }} jours d'essai, vous pourrez continuer avec **PrePla Plus** à 9.99€/mois ou 79.99€/an.

Bonne préparation,

L'équipe PrePla

---

<small>Si vous n'avez pas créé ce compte, ignorez cet email.</small>
</x-mail::message>
