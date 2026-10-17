#!/usr/bin/env bash
#
# Secret Share - diagnostic d'un lien de partage
#
# Usage : ./check-token.sh <jeton ou lien complet>
#
# Indique si le jeton est authentique (signature JWT), s'il est expiré et si le
# secret est encore consommable. Le secret n'est jamais déchiffré ni consommé.
# Code de retour : 0 si le secret est consommable, 1 sinon, 2 en cas d'erreur.
# Réservé à root et aux membres du groupe docker.
#
set -euo pipefail

cd "$(dirname "$(readlink -f "$0")")"

usage() {
    sed -n '5p' "$0" | sed 's/^# \{0,1\}//'
    exit 2
}

[ $# -eq 1 ] || usage

if [ "$(id -u)" -ne 0 ] && ! id -nG | tr ' ' '\n' | grep -qx docker; then
    echo "Réservé à root et aux membres du groupe docker." >&2
    exit 2
fi

# accepte le lien complet : seul ce qui suit « # » est le jeton
token="${1##*#}"
# validation stricte : le jeton est inséré dans le script PHP exécuté dans le conteneur
if ! [[ "$token" =~ ^[A-Za-z0-9_.-]{1,20000}$ ]]; then
    echo "Jeton invalide : caractères inattendus (copie incomplète ou altérée ?)" >&2
    exit 2
fi

docker compose exec -T app php <<PHP
<?php

declare(strict_types=1);

use SecretShare\Clock;
use SecretShare\Config;
use SecretShare\Crypto\DecryptionException;
use SecretShare\Crypto\JwtCodec;
use SecretShare\Crypto\KeyRing;
use SecretShare\Crypto\Random;
use SecretShare\Storage\Database;
use SecretShare\Storage\SecretRepository;

require '/app/vendor/autoload.php';

\$token = '$token';
\$config = Config::fromEnvironment(getenv());
\$keyRing = new KeyRing(\$config->appSecret);
\$now = time();
\$date = static fn (int \$timestamp): string => gmdate('Y-m-d H:i:s', \$timestamp) . ' UTC';

// espaces de noms des empreintes de recherche (voir SecretService)
if (Random::isShortCode(\$token, Config::MIN_SHORT_CODE_LENGTH, Config::MAX_SHORT_CODE_LENGTH)) {
    echo "Format       : code court\n";
    \$lookupHash = \$keyRing->lookupHash('c', \$token);
} else {
    echo "Format       : JWT\n";
    // horloge à 0 : la signature est vérifiée, l'expiration est contrôlée séparément ci-dessous
    \$epoch = new class implements Clock {
        public function now(): int
        {
            return 0;
        }
    };
    try {
        \$claims = (new JwtCodec(\$keyRing, \$epoch))->decode(\$token);
    } catch (DecryptionException \$e) {
        echo "Signature    : INVALIDE (jeton altéré, tronqué ou émis par une autre instance)\n";
        echo "Consommable  : non\n";
        exit(1);
    }
    echo "Signature    : valide\n";
    \$exp = \$claims['exp'] ?? null;
    if (is_int(\$exp)) {
        printf("Expiration   : %s%s\n", \$date(\$exp), \$exp <= \$now ? ' (EXPIRÉ)' : '');
    } else {
        echo "Expiration   : aucune\n";
    }
    \$jti = \$claims['jti'] ?? null;
    if ((\$claims['v'] ?? null) !== 1 || !is_string(\$jti) || !is_string(\$claims['d'] ?? null)) {
        echo "Contenu      : format inconnu\n";
        echo "Consommable  : non\n";
        exit(1);
    }
    \$lookupHash = \$keyRing->lookupHash('t', \$jti);
}

\$record = (new SecretRepository(Database::connect(\$config->databasePath)))->find(\$lookupHash);
if (\$record === null) {
    echo "En base      : absent (déjà consulté, détruit, expiré et purgé, ou inconnu)\n";
    echo "Consommable  : non\n";
    exit(1);
}
echo "En base      : présent\n";
printf("Usage unique : %s\n", \$record->singleUse ? 'oui' : 'non');
printf(
    "Essais mdp   : %d / %d consommé(s)\n",
    \$record->failedAttempts,
    \$config->maxPasswordAttempts
);
if (\$record->expiresAt !== null) {
    printf("Expire (base): %s%s\n", \$date(\$record->expiresAt), \$record->isExpired(\$now) ? ' (EXPIRÉ)' : '');
}
if (\$record->isExpired(\$now)) {
    echo "Consommable  : non (expiré, sera purgé au prochain nettoyage)\n";
    exit(1);
}
if (\$record->failedAttempts >= \$config->maxPasswordAttempts) {
    echo "Consommable  : non (trop d'essais de mot de passe)\n";
    exit(1);
}
echo "Consommable  : oui\n";
exit(0);
PHP
