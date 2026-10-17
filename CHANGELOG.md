# Changelog

Toutes les évolutions notables sont consignées ici.
Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/) et le projet
respecte le [versionnage sémantique](https://semver.org/lang/fr/).

## [1.3.0] - 2026-10-17

### Maintenance automatisée

- Montées de version : PHP (dernier cycle stable, code modernisé par Rector), dépendances
  Composer et npm y compris majeures, Node.js et actions de la CI, images d'outils.
- Images de base du `Dockerfile` figées par empreinte (`@sha256`), mises à jour chaque mois.
- Adaptation du code par Claude Code si une montée de version fait échouer les tests,
  abandon des montées de version à défaut.
- Mise à jour du serveur aussi lorsqu'il n'est pas sur la dernière version publiée, second
  essai des tests en production avant tout retour en arrière.

### Maintenance

- PHP 8.3 => 8.5
- Node.js de la CI : 22 => 24
- action GitHub actions/checkout : v4 => v7
- action GitHub actions/setup-node : v4 => v7
- action GitHub docker/build-push-action : v6 => v7
- action GitHub docker/login-action : v3 => v4
- action GitHub docker/metadata-action : v5 => v6
- action GitHub docker/setup-buildx-action : v3 => v4
- image ShellCheck : v0.10.0 => v0.11.0
- images de base figées sur leur dernière version : php:8.5-apache@sha256:974e3a920309…
- code modernisé par Rector pour la nouvelle version de PHP
- composer : Removing sebastian/git-state (1.0.0)
- composer : Removing sebastian/file-filter (1.0.0)
- code adapté aux montées de version par Claude Code (voir le journal)

## [1.2.3] - 2026-10-17

### Ajouts

- Maintenance mensuelle automatisée (`maintenance/monthly.sh` et son timer systemd) :
  mises à jour, tests, analyse Trivy avec correction par Claude Code, publication d'un
  correctif et mise à jour du serveur avec retour arrière en cas d'échec.
- Tests fonctionnels dans un navigateur versionnés (`tests/e2e/`) et exécutés en CI.
- Analyse Trivy de l'image et des dépendances en CI ; exceptions documentées dans
  `.trivyignore`.

### Sécurité

- L'image applique les correctifs de sécurité Debian publiés depuis l'image de base
  (curl : CVE-2026-8286, CVE-2026-8927, CVE-2026-92392).
- `puppeteer-core` 25 pour les tests e2e : retire `basic-ftp` vulnérable (CVE-2026-102990).

### Maintenance

- paquets système mis à jour : apache2 apache2-bin apache2-data apache2-utils curl libcurl4t64 libexpat1 liblzma5 linux-libc-dev xz-utils

## [1.2.2] - 2026-10-17

### Modifications

- Corrections signalées par l'analyse de PhpStorm : imports explicites, annotations `@throws`
  et types des gabarits.

### Documentation

- README à jour : protection contre les abus, mise à jour d'une installation, langues
  disponibles, variables `QOS_*`, scripts de `bin/`, limites connues, capture d'écran.

## [1.2.1] - 2026-10-17

### Modifications

- Les fichiers statiques sont versionnés par leur date de modification : le cache du navigateur
  est renouvelé dès qu'un fichier change.
- Drapeau devant chaque langue du sélecteur (clé `lang.flag` des fichiers de langue).

## [1.2.0] - 2026-10-17

### Ajouts

- Interface disponible en 9 langues : anglais, chinois (simplifié), hindi, espagnol, français,
  arabe, portugais, russe et allemand, avec prise en charge de l'écriture de droite à gauche.
- Sélecteur de langue, choix mémorisé dans un cookie ; à défaut, langue du navigateur.
- Ajout d'une langue par simple dépôt d'un fichier `lang/<code>.php`, textes manquants affichés
  en anglais, vérification par `composer translations` (incluse dans `composer check`).

## [1.1.0] - 2026-10-17

### Ajouts

- Script `./unblock.sh` (root ou groupe `docker`) pour débloquer une adresse ou toutes les
  adresses limitées, dans l'application et, si `QOS_CONSOLE_URL` est défini, dans `mod_qos`.
- Commande `bin/unblock` dans l'image pour gérer les compteurs de l'application.

## [1.0.1] - 2026-10-17

### Sécurité

- Limitation de débit par visiteur des créations (par minute et par heure) et des ouvertures,
  sans stocker d'adresse IP (empreinte HMAC à clé quotidienne, conservée le temps de la fenêtre).
- Prise en compte de `X-Forwarded-For` derrière un proxy de confiance (`TRUSTED_PROXIES`).
- Quota dédié aux secrets sans limite de durée (`MAX_UNLIMITED_SECRETS`), qui ne peuvent plus
  saturer durablement le quota global.

### Modifications

- Le nettoyage des données expirées s'exécute au plus une fois par minute au lieu de chaque requête.
- Les connexions internes d'Apache n'apparaissent plus dans le journal d'accès.

## [1.0.0] - 2026-10-17

### Ajouts

- Partage d'un secret par lien et QR code, chiffré par XChaCha20-Poly1305.
- Clé de chiffrement répartie entre le lien et le serveur, destruction par suppression de la clé.
- Durées de validité de 10 minutes à 1 an, ou sans limite.
- Usage unique (par défaut), mot de passe optionnel (Argon2id) avec limite d'essais.
- URL courte optionnelle, de longueur configurable.
- Lien de destruction remis au créateur.
- Interface responsive, accessible, thème clair/sombre automatique, en français et en anglais.
- Image Docker durcie, installation par `install.sh`, base SQLite créée automatiquement.
