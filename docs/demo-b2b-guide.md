# Guide de démo B2B — PrePla pour centres de langue

Ce guide accompagne le commercial qui fait visiter l'espace "Centre" de PrePla à un
prospect (école ou centre de langue). Les données sont générées par un seeder et
peuvent être réinitialisées à tout moment.

## 1. Connexion

- **URL** : `https://prepla.mirlab.cloud/login` (adapter si environnement de staging)
- **Compte center_admin** : `demo-admin@demo.prepla.local` / `Demo-Prepla-2026`
- Des comptes prof et élève existent aussi avec le même mot de passe si besoin de
  montrer le point de vue enseignant ou élève (voir le récapitulatif affiché après
  `php artisan demo:reset` pour les emails exacts).
- Ne jamais donner l'accès super_admin (plateforme) à un prospect.

## 2. Réinitialiser la démo avant un rendez-vous

```bash
php artisan demo:reset
```

Recrée le centre "Institut Linguae" avec des données propres et affiche les
identifiants à l'écran. **À relancer avant chaque rendez-vous** si un prospect
précédent a modifié la démo (créé/supprimé une classe, un devoir, etc.).

## 3. Tour guidé (ordre recommandé)

1. **Dashboard centre** (`/center`) — vue d'ensemble : nombre de classes, sièges
   utilisés / limite disponible.
2. **Classes** (`/center/classes`) — 3 classes de niveaux différents (A1, B1, B2),
   chacune avec son code d'invitation.
   → *Montrer* : créer une nouvelle classe en direct (bouton "Créer une classe").
3. **Détail d'une classe** (cliquer sur une classe) — statistiques agrégées :
   moyenne de la classe, élèves décrocheurs (pas d'activité depuis 7+ jours),
   faiblesses communes identifiées automatiquement (grammaire, vocabulaire...).
4. **Élèves** (`/center/students`) puis fiche d'un élève — progression
   individuelle, historique de tentatives, points faibles détectés.
5. **Exercices** (`/center/exercises`) — bibliothèque de contenu créé par les
   profs du centre.
   → *Montrer* : créer un exercice (manuel ou génération assistée par IA).
6. **Devoirs** (`/center/assignments`) — devoirs publiés avec échéances passées
   et futures ; ouvrir un devoir pour montrer l'avancement par élève (fait / en
   cours / en retard / pas commencé).
   → *Montrer* : créer un nouveau devoir et l'assigner à une classe.
7. **Médiathèque** (`/center/media`) — upload d'images/audio réutilisables dans
   les exercices.

## 4. Ce que le prospect peut tester librement

- Créer une classe, régénérer un code d'invitation.
- Créer un devoir et l'assigner à une classe.
- Créer un exercice (manuel ou génération IA).
- Consulter la fiche de progression d'un élève démo.

## 5. Limites de l'environnement de démo

- C'est un bac à sable partagé entre prospects : ne jamais y saisir de données
  confidentielles réelles.
- Les modifications faites par un prospect restent visibles jusqu'à la prochaine
  réinitialisation (`php artisan demo:reset`).
- La démo ne reflète pas les vrais quotas/tarifs — c'est un environnement de test.

## 6. FAQ commercial rapide

- **"Un élève peut-il être dans 2 centres ?"** → Non, contrainte volontaire de la
  plateforme : un compte appartient à un seul centre.
- **"Les données sont-elles isolées entre centres ?"** → Oui, chaque centre ne
  voit que ses propres classes, élèves, exercices et devoirs.
- **"Combien d'élèves un centre peut-il avoir ?"** → Dépend du plan souscrit
  (`seats_limit` configurable par centre).
