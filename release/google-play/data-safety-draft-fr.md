# Sécurité des données — brouillon à confirmer

Ce document traduit le comportement observé dans le code et la politique de
confidentialité en réponses probables pour le formulaire Play Console. Il ne remplace pas
une validation juridique ni la vérification des configurations réelles des prestataires.

## Déclarations générales probables

- Des données sont collectées et certaines sont transmises à des prestataires nécessaires
  au service.
- Les données sont chiffrées en transit via HTTPS.
- L'utilisateur peut demander la suppression de ses données dans l'application et depuis
  `https://prepla.mirlab.cloud/account-deletion`.
- Les données ne sont pas vendues et ne servent pas à de la publicité, d'après la politique
  actuelle.

## Catégories à déclarer

| Catégorie Play | Données PrePla | Requise ? | Usage principal | Partage / traitement externe |
|---|---|---:|---|---|
| Informations personnelles | nom, email | oui | création et gestion du compte | hébergeur et messagerie, selon configuration |
| Identifiants utilisateur | identifiant interne, identifiant Google si choisi | oui / facultatif | connexion et sécurité | Google uniquement pour la connexion Google |
| Informations financières | état d'abonnement, historique nécessaire à la facturation | facultatif | achat et gestion de PrePla Plus | Stripe sur la version web actuelle |
| Photos | photo d'une copie écrite importée pour OCR | facultatif | extraction et correction du texte | Mistral AI ; confirmer la conservation éphémère |
| Audio | enregistrement d'une réponse orale | facultatif | transcription et évaluation | Deepgram ; déclaré comme non conservé après traitement |
| Activité dans l'application | réponses, scores, progression, séries, erreurs, vocabulaire | oui | fonctions de l'app, personnalisation, analyse de progression | Mistral AI pour certaines générations/corrections |
| Informations et performances de l'application | journaux techniques, adresse IP | oui | sécurité, diagnostic et prévention des abus | hébergeur ; vérifier les journaux et leur rétention |
| Appareil ou autres identifiants | abonnement de notification push | facultatif | rappels demandés par l'utilisateur | service Web Push du navigateur/appareil |

## Questions à confirmer avant validation

1. Où sont hébergées la base de données, les sauvegardes et les journaux, et pendant
   combien de temps chaque copie est-elle conservée ?
2. Mistral AI et Deepgram utilisent-ils les requêtes pour entraîner leurs modèles dans les
   offres/API réellement souscrites ?
3. Les images OCR sont-elles supprimées juste après extraction ou conservées dans les
   journaux / sauvegardes ?
4. Quel prestataire SMTP traite les adresses email et les messages transactionnels ?
5. La version Android utilisera-t-elle Stripe, Google Play Billing ou aucun achat intégré ?
6. Les comptes Google sont-ils activés dans la version soumise ?

## Cohérence documentaire à maintenir

Toute réponse Play Console doit rester cohérente avec
`https://prepla.mirlab.cloud/privacy`. Si le traitement des photos OCR, un prestataire
d'hébergement ou une durée de conservation est confirmé, mettre aussi la politique de
confidentialité à jour avant la soumission.
