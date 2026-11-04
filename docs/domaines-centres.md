# Sous-domaines et domaines propres des centres

Ce document décrit les étapes à faire **sur le VPS** pour que les adresses des centres
(lot B) fonctionnent en production. Le code est prêt ; **rien de ce qui suit n'a été exécuté**.

Tant que ces étapes ne sont pas faites, rien ne change : `prepla.mirlab.cloud` répond comme
avant, et une adresse ajoutée dans l'administration ne mène nulle part, car le DNS et nginx ne
la connaissent pas.

## Ce que fait l'application

- **Hôte principal** (`prepla.mirlab.cloud`, et l'hôte de `APP_URL`) : comportement inchangé.
- **Sous-domaine d'un centre** (`ecole.prepla.mirlab.cloud`) : le super administrateur l'ajoute
  dans *Centres → un centre → Adresses du centre*. Il est actif dès sa création, puisque la zone
  DNS est la nôtre.
- **Domaine propre** (`prepa.ecole.cm`) : ajouté de la même façon, mais actif seulement après
  vérification DNS. Le centre crée chez son hébergeur DNS **l'un** des deux enregistrements
  suivants :
  - `TXT _prepla.prepa.ecole.cm` = `prepla-verification=<jeton>` (le jeton est affiché sur la page) ;
  - ou `CNAME prepa.ecole.cm` → `prepla.mirlab.cloud`.
- **Sous-domaines réservés** (`www`, `api`, `admin`, `mail`, `staging`…, liste dans
  `config/domaines.php`) : 404, et impossibles à attribuer.
- **Hôte inconnu**, ou domaine non vérifié : refusé (400 « Untrusted Host » par
  `FaireConfianceAuxHotes`, ou 404 pour un sous-domaine inconnu).
- **Centre désactivé** : page « établissement indisponible » (503).
- Les hôtes des centres ne sont jamais indexés (`X-Robots-Tag: noindex, nofollow` et balise
  `robots`).

Réglages facultatifs dans `.env` (aucun n'est nécessaire en production, tout se déduit de `APP_URL`) :

| Clé | Rôle | Défaut |
|---|---|---|
| `CENTRES_DOMAINE_RACINE` | racine des sous-domaines | hôte de `APP_URL` |
| `CENTRES_CIBLE_CNAME` | cible attendue d'un CNAME | `prepla.mirlab.cloud` |

Après un déploiement qui touche `config/domaines.php`, relancer `php artisan config:cache` si la
configuration est mise en cache.

## 1. DNS joker

Chez l'hébergeur DNS de `mirlab.cloud`, ajouter :

```
*.prepla.mirlab.cloud.   A   147.93.87.67
```

Vérifier depuis une autre machine :

```
dig +short test123.prepla.mirlab.cloud
```

Aujourd'hui, ce nom ne répond pas : il n'y a pas de DNS joker.

## 2. Certificat joker (défi DNS-01)

Un certificat pour `*.prepla.mirlab.cloud` exige un défi **DNS-01**. Le Let's Encrypt intégré
à CloudPanel utilise un défi HTTP-01, qui ne délivre pas de certificat joker. Il faut donc
utiliser certbot ou acme.sh avec l'API DNS de l'hébergeur de `mirlab.cloud`.

Exemple avec acme.sh (à adapter au module DNS de l'hébergeur, voir la documentation
`dnsapi` d'acme.sh) :

```
# Clé d'API de l'hébergeur DNS : dans l'environnement du shell, jamais dans le dépôt.
export <VARIABLES_DE_L_API_DNS>=...
acme.sh --issue --dns <module_dns> -d prepla.mirlab.cloud -d '*.prepla.mirlab.cloud'
acme.sh --install-cert -d prepla.mirlab.cloud \
  --key-file       /etc/nginx/ssl-certificates/prepla-joker.key \
  --fullchain-file /etc/nginx/ssl-certificates/prepla-joker.crt \
  --reloadcmd      "systemctl reload nginx"
```

acme.sh installe lui-même sa tâche cron de renouvellement ; vérifier avec `crontab -l` qu'elle
existe, et qu'elle recharge bien nginx.

Le certificat actuel de `prepla.mirlab.cloud` expire le 29/12/2026 : ne pas le remplacer avant
d'avoir vérifié que le nouveau fonctionne.

## 3. nginx (hors CloudPanel)

**Une modification faite à la main dans le vhost géré par CloudPanel peut être écrasée** par
CloudPanel. Créer plutôt un fichier séparé, par exemple
`/etc/nginx/sites-enabled/prepla-centres.conf`, qui reprend la transmission du site existant :

```
server {
    listen 443 ssl;
    listen [::]:443 ssl;
    http2 on;
    server_name *.prepla.mirlab.cloud;

    ssl_certificate     /etc/nginx/ssl-certificates/prepla-joker.crt;
    ssl_certificate_key /etc/nginx/ssl-certificates/prepla-joker.key;

    # Reprendre ici la même configuration que le vhost de prepla.mirlab.cloud
    # (root, index, blocs location, transmission à 127.0.0.1:8080).
    # L'hôte DOIT être transmis tel quel : l'application en déduit le centre.
    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }
}

server {
    listen 80;
    listen [::]:80;
    server_name *.prepla.mirlab.cloud;
    return 301 https://$host$request_uri;
}
```

Puis :

```
nginx -t && systemctl reload nginx
```

Vérifier que le vhost interne (port 8080) accepte n'importe quel `Host` (ou les ajouter à son
`server_name`), sinon il répondra par son serveur par défaut.

Contrôle :

```
curl -sI https://inconnu.prepla.mirlab.cloud/login   # attendu : 404
curl -sI https://www.prepla.mirlab.cloud/login       # attendu : 404 (réservé)
curl -sI https://prepla.mirlab.cloud/                # attendu : 200, comme avant
```

## 4. Domaines propres (HTTP-01, un par client)

Pour chaque domaine propre :

1. Le centre crée un `CNAME` (ou un `A` vers `147.93.87.67`) pour son domaine, ainsi que
   l'enregistrement de vérification (TXT ou CNAME, voir plus haut).
2. Le super administrateur clique sur **Vérifier** dans la page du centre.
3. Sur le VPS, émettre un certificat HTTP-01 pour ce domaine, au choix :
   - `clpctl lets-encrypt:install:certificate` sur un site CloudPanel dédié, avec le domaine en SAN ;
   - ou certbot en mode webroot.
4. Ajouter le domaine au `server_name` d'un bloc nginx comme ci-dessus (avec son propre
   certificat), puis `nginx -t && systemctl reload nginx`.

C'est manuel pour chaque client ; un script le ferait si les clients deviennent nombreux.

Tant que le domaine n'est pas vérifié dans l'application, il est refusé (400) même si nginx le
sert : l'ordre des étapes 2 et 3 n'a donc pas d'importance pour la sécurité.

## 5. HSTS `includeSubDomains`

`AddSecurityHeaders` envoie `Strict-Transport-Security: max-age=31536000; includeSubDomains`
sur `prepla.mirlab.cloud`. Les navigateurs qui l'ont reçu refusent désormais **tout
sous-domaine en HTTP**. Avant d'ouvrir les sous-domaines :

- vérifier que le certificat joker est installé (étape 2) avant de communiquer une adresse ;
- vérifier qu'aucun autre sous-domaine de `prepla.mirlab.cloud` n'est encore servi en HTTP seul :

```
curl -sI http://ecole.prepla.mirlab.cloud/login   # attendu : 301 vers https
curl -sI https://ecole.prepla.mirlab.cloud/login  # attendu : 200 (ou 404 si non attribué)
```

## Points à connaître

- **Sessions** : `SESSION_DOMAIN` est vide, donc chaque adresse a sa propre connexion. Un élève
  se connecte sur l'adresse de son centre ; c'est voulu (isolation).
- **Connexion Google** : l'adresse de retour déclarée chez Google est celle de
  `prepla.mirlab.cloud`. Sur l'adresse d'un centre, « Continuer avec Google » ramène donc
  l'élève sur l'adresse principale. Pour l'éviter, il faudrait déclarer chaque adresse de retour
  dans la console Google, ou un retour par jeton signé (non fait).
- **Liens des courriels** : les notifications des devoirs et les liens d'invitation d'un
  enseignant partent sur le domaine principal du centre s'il en a un (`App\Support\UrlDuCentre`).
- **Stripe** : `success_url` est construit à partir de l'hôte de la requête ; rien à changer.
