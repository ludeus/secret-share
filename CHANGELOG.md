# Changelog

Toutes les évolutions notables sont consignées ici.
Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/) et le projet
respecte le [versionnage sémantique](https://semver.org/lang/fr/).

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
