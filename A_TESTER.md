# À TESTER — checklist de feedback

> ⚠️ **Avant de tester** : recharge la page **2 fois** (ou ferme/rouvre la PWA) pour activer le nouveau service worker. Sinon tu vois l'ancienne version.
> Teste sur **davidnjandje@gmail.com**, idéalement sur mobile (sans VPN).
> Pour chaque point : ✅ marche / ❌ cassé + ce que tu as vu.

Dernier déploiement : commit `aef2396`, SW `v10`.

---

## LOT A — Oral interactif (NOUVEAU, priorité)
Aller dans **Practice → DELF/DALF ou Goethe → compétence orale (Production orale / Sprechen)**.

- [ ] **Débat** (`oral-debate`) — DELF B2, DALF C1/C2
  - L'examinateur lance une position (audio TTS)
  - Tu réponds au micro → « Valider ma réponse »
  - **Mini-loader « Analyse… »** puis correction de CE tour (transcription + ✓ bien dit / + à améliorer + %)
  - Bouton « Continuer » → tour suivant ; à la fin = récap score moyen
  - ❓ Vérifie : ça avance bien, pas de « n'a pas répondu » quand tu as parlé

- [ ] **Négociation** (`negotiation`) — Goethe A2/B1
  - Même mécanique, scénario « se mettre d'accord / planifier »

- [ ] **Poser des questions** (`speaking-elicitation`) — DELF A1
  - On te demande de **formuler une question** à l'oral ; l'IA vérifie si elle est pertinente

- [ ] **Role-play classique** (ex. exercice 366 si encore là)
  - Le bouton micro apparaît bien, tu peux parler, correction par tour

---

## LOT B — Speaking simple (corrigé récemment)
- [ ] Un exercice **speaking-recorder** (monologue)
  - Tu enregistres → tu vois **« Ce que l'IA a entendu »** (la transcription)
  - La note n'est **PAS** binaire 0%/100% — elle est nuancée
  - Si tu ne dis rien : message clair « on n'a pas réussi à t'entendre » (pas 0% muet)

---

## LOT C — Remplissage écrit DANS le texte
- [ ] **gap-fill** : tu écris **dans le trou, à l'intérieur de la phrase** (pas d'input séparé en bas)
- [ ] **open-cloze** : un **texte long avec 8-10 trous** inline, tu écris dans chaque trou
- [ ] **gap-fill grammaire** : verbes à conjuguer `(gehen)`, terminaisons allemandes `(groß)→großen`

---

## LOT D — Exercices réparés (contrats de données)
Vérifie que chacun **s'affiche et se note** correctement (une bonne réponse ≠ 0%) :
- [ ] **note-completion** (notes à compléter — y compris en Hören)
- [ ] **form-completion** (formulaire — plus d'input date forcé)
- [ ] **table-completion** (tableau à trous)
- [ ] **summary-completion** (résumé + banque de mots)
- [ ] **flow-chart-completion** (étapes à compléter)
- [ ] **gapped-text** (replacer des paragraphes)
- [ ] **sentence-completion** (QCM aligné)
- [ ] **true-false-ng** (Vrai/Faux/Non mentionné)

---

## LOT E — Listening (écoute)
- [ ] Un exercice **Hören/Listening** : le **transcript n'est PAS affiché**, seulement un bouton « Écouter » + la question/options
- [ ] L'audio est dans la **bonne langue** (allemand lu avec voix allemande, pas anglaise)
- [ ] L'audio se lance **vite** (pré-généré), pas de longue attente au clic
- [ ] Plus de **matching en écoute** bidon (terme dit + réécrit, réponse toujours A)

---

## LOT F — QCM / réponses
- [ ] La **bonne réponse n'est PAS toujours en position A** (avant : on cliquait toujours A)
- [ ] Sur un QCM : une **consigne d'action** apparaît au-dessus (« Choisis la bonne réponse »)
- [ ] À chaque bonne réponse : **son + petite animation** (étoile) ; mauvaise = son + shake

---

## LOT G — Practice : navigation par compétence
- [ ] `/practice` → clic sur une **compétence** → **galerie des TYPES** de cette compétence
- [ ] Clic sur un **type** → un exo **à mon niveau** se lance
- [ ] Sur l'écran de résultat : bouton **« Autre exercice »** → un autre exo du même type

---

## LOT H — Fin de leçon / parcours
- [ ] Fin de leçon (quiz réussi) → bouton **« Revenir au parcours »** (plus de « Pratiquer ce concept » bizarre)
- [ ] Quand je finis la 1re leçon, le **practice suivant n'est plus bloqué**, le parcours avance
- [ ] Seuil de validation = **60%** (plus 80%) — moins punitif
- [ ] Fin de session : GIF (trophée/winner) + son + confetti

---

## LOT I — Onboarding
- [ ] Test de placement : design **Duolingo**, **pas besoin de scroller** en mobile
- [ ] Page résultat : **loader** pendant la génération IA (pas de page figée)
- [ ] « Voir mon parcours » → renvoie bien au **dashboard**
- [ ] Chargement onboarding rapide (pas de gros temps mort)

---

## LOT J — Divers / PWA / UI
- [ ] **Installer l'app** sur le bureau / écran d'accueil : l'invite apparaît (HTTPS, après un peu d'usage)
- [ ] **Icônes** en couleurs (header / tab bar) — plus d'inversion bizarre
- [ ] **Plus d'emojis** dans l'interface
- [ ] **Loader sablier** pendant les changements de page (pas 2 loaders superposés)
- [ ] **Sons** ElevenLabs s'entendent (après 1re interaction) — toggle dans Paramètres › Apparence
- [ ] **Dictionnaire** compact en mobile
- [ ] **Explicateur IA** (`/ai-tools/explainer`) : scrollable + conversation conservée + markdown (tableaux/listes)
- [ ] **Profil** : le niveau **n'est plus modifiable** (lecture seule + lien « Repasser le test »)

---

## LOT K — Exercices à NE PLUS voir (nettoyés)
- [ ] Plus d'exercice **« What is this exercise about? »** (placeholder bidon)
- [ ] Plus de **diagram-labeling** (diagramme vide sans image)

---

## ❗ Bugs connus / pas encore faits (ne pas tester)
- TOEFL est encore à l'**ancien format** (refonte 2026 = vague 3, à venir)
- **MCQ avec images** (Goethe A1/A2, TEF audio→image) = vague 2, pas encore fait
- **Genres d'écrit avancés** (Cambridge C2, Goethe C2 réécriture) = vague 4, pas encore fait

---

## Mon feedback (à remplir)
Écris ici (ou dis-moi) : numéro du lot + ✅/❌ + ce que tu as vu.

Exemple :
- LOT A débat : ❌ le micro n'apparaît pas sur DALF C1
- LOT E : ✅ transcript caché, mais l'audio met 5s à se lancer
