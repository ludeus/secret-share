# Secret Share

**Partagez mots de passe, clés d’API et informations sensibles par lien éphémère chiffré ou QR code.**

Secret Share est une application web auto-hébergée, livrée dans une image Docker avec PHP, Apache et SQLite. Aucun serveur de base de données supplémentaire n’est nécessaire.

- Chiffrement XChaCha20-Poly1305 et protection optionnelle par mot de passe avec Argon2id.
- Secrets à usage unique par défaut, expiration configurable et lien de destruction.
- Liens de partage avec QR code ; option URL courte pour les secrets volumineux.
- Interface adaptée aux mobiles, thèmes clair, sombre ou automatique.
- Neuf langues : français, anglais, allemand, espagnol, portugais, russe, chinois, hindi et arabe.
- Changement de langue sans rechargement, y compris sur les pages de résultat.
- Limitation de débit et quotas de stockage intégrés.
- Conteneur exécuté sans privilèges ; configuration compatible avec un système de fichiers en lecture seule.

**Architectures :** `linux/amd64` et `linux/arm64`.

[Code source et documentation](https://github.com/ludeus/secret-share) · [Historique des versions](https://github.com/ludeus/secret-share/blob/main/CHANGELOG.md)

![Création d’un secret](https://raw.githubusercontent.com/ludeus/secret-share/main/docs/screenshot.png)

![Lien de partage et QR code](https://raw.githubusercontent.com/ludeus/secret-share/main/docs/screenshot-created.png)

## Démarrage avec Docker Compose

Créez un répertoire de déploiement, puis générez votre configuration :

```bash
mkdir secret-share
cd secret-share

(
  umask 077
  printf 'APP_SECRET=%s\nAPP_URL=https://secret.example.org\n' \
    "$(openssl rand -base64 48)" > .env
)
```

Remplacez `https://secret.example.org` dans `.env` par votre URL publique, sans slash final.

Créez un fichier `compose.yaml` :

```yaml
services:
  app:
    image: ludeus/secret-share:1.5.0
    restart: unless-stopped
    environment:
      APP_SECRET: ${APP_SECRET:?Définir APP_SECRET dans .env}
      APP_URL: ${APP_URL:?Définir APP_URL dans .env}
      DEFAULT_LOCALE: fr
    ports:
      - "127.0.0.1:8765:8080"
    volumes:
      - data:/app/data
    read_only: true
    tmpfs:
      - /tmp:mode=1777,size=16m
      - /var/run/apache2:uid=33,gid=33,mode=0700,size=1m
      - /var/lock/apache2:uid=33,gid=33,mode=0700,size=1m
    cap_drop:
      - ALL
    security_opt:
      - no-new-privileges:true
    pids_limit: 200
    mem_limit: 768m

volumes:
  data:
```

Démarrez l’application :

```bash
docker compose up -d
```

Le service écoute sur `127.0.0.1:8765`. Placez-le derrière un reverse proxy **HTTPS** correspondant à `APP_URL`.

**Conservez votre fichier `.env` et la valeur d’`APP_SECRET`.** Perdre ou changer cette clé rend les secrets en cours illisibles.

## Configuration

La configuration utilise des variables d’environnement. Ajoutez les options souhaitées à la section `environment` du service Compose.

| Variable | Défaut | Rôle |
| --- | --- | --- |
| `APP_SECRET` | Obligatoire | Clé maître de 32 caractères minimum. |
| `APP_URL` | Obligatoire | URL publique utilisée pour construire les liens. |
| `DEFAULT_LOCALE` | `fr` | Langue de repli si aucune langue du navigateur n’est disponible. |
| `SECRET_MAX_LENGTH` | `10000` | Nombre maximal de caractères par secret. |
| `SHORT_CODE_LENGTH` | `10` | Longueur des codes des URL courtes, de 8 à 64 caractères. |
| `PASSWORD_MAX_ATTEMPTS` | `5` | Essais de mot de passe avant destruction du secret. |
| `MAX_STORED_SECRETS` | `100000` | Nombre maximal de secrets conservés. |
| `MAX_UNLIMITED_SECRETS` | `1000` | Nombre maximal de secrets sans expiration. |
| `ARGON2_CONCURRENCY` | `2` | Nombre maximal de calculs Argon2id simultanés. |
| `RATE_LIMIT_CREATE_PER_MINUTE` | `5` | Créations par visiteur et par minute. |
| `RATE_LIMIT_CREATE_PER_HOUR` | `30` | Créations par visiteur et par heure. |
| `RATE_LIMIT_READ_PER_MINUTE` | `30` | Ouvertures et destructions par visiteur et par minute. |
| `TRUSTED_PROXIES` | Plages privées et boucle locale | Proxys autorisés à transmettre l’adresse du visiteur via `X-Forwarded-For`. |
| `SOURCE_URL` | Dépôt GitHub du projet | Lien vers le code source affiché dans l’interface. |
| `DATABASE_PATH` | `/app/data/secret-share.sqlite` | Emplacement de la base SQLite. |

Les variables `RATE_LIMIT_*` acceptent `0` pour désactiver la limite concernée.

[Configuration complète et exemples de reverse proxy](https://github.com/ludeus/secret-share#configuration)

## Données persistantes et mises à jour

Le volume `/app/data` contient la base SQLite. Elle est initialisée au premier démarrage et son schéma est mis à jour automatiquement.

Pour changer de version, modifiez le tag de l’image dans `compose.yaml`, puis lancez :

```bash
docker compose pull
docker compose up -d
```

Conservez le même volume et la même clé `APP_SECRET` pour préserver les secrets en cours. Sauvegardez ces deux éléments ensemble et protégez leur accès.

Les tags de version permettent de fixer une publication, par exemple `1.5.0`. Le tag `edge` suit la branche `main`.

## Fonctionnement et sécurité

Le chiffrement est effectué **côté serveur** : le serveur voit temporairement le secret lors de sa création et de sa lecture. Utilisez une instance de confiance, accessible en HTTPS.

En mode standard, le lien transporte le secret chiffré dans un jeton signé ; la base conserve une clé nécessaire à sa lecture. En mode URL courte, le contenu chiffré est également stocké en base. La suppression de la clé rend le secret illisible.

Le jeton se trouve dans le fragment du lien (`#…`) : il est absent de la requête HTTP initiale et des journaux d’accès. Il est transmis en POST lorsque le visiteur demande explicitement l’ouverture du secret, ce qui évite sa consommation par les robots de prévisualisation.

Les pages utilisent une politique de contenu stricte, une protection CSRF et des en-têtes interdisant leur mise en cache. Les journaux d’accès du conteneur ne contiennent ni adresse IP, ni user-agent, ni referer. La limitation de débit conserve temporairement des empreintes HMAC des adresses.

Pour les longs secrets, choisissez l’option **URL courte** afin de conserver un lien compatible avec la capacité d’un QR code.

## Licence et assistance

Licence **AGPL-3.0-or-later**. Si vous proposez une version modifiée sur un réseau, rendez son code source accessible aux utilisateurs et adaptez `SOURCE_URL`.

- [Documentation](https://github.com/ludeus/secret-share#readme)
- [Signaler un problème](https://github.com/ludeus/secret-share/issues)
- [Signaler une vulnérabilité](https://github.com/ludeus/secret-share/blob/main/SECURITY.md)
