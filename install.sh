#!/usr/bin/env bash
#
# Secret Share - installation
#
# Crée le fichier .env à partir de .env.dist en générant une clé APP_SECRET
# aléatoire, puis (optionnellement) démarre les conteneurs.
#
# Usage : ./install.sh [--url URL] [--port PORT] [--bind ADRESSE] [--start] [--force]
#
set -euo pipefail

cd "$(dirname "$0")"

url=""
port=""
bind=""
start=0
force=0

usage() {
    sed -n '3,9p' "$0" | sed 's/^# \{0,1\}//'
    exit "${1:-0}"
}

while [ $# -gt 0 ]; do
    case "$1" in
        --url) url="${2:?--url attend une valeur}"; shift 2 ;;
        --port) port="${2:?--port attend une valeur}"; shift 2 ;;
        --bind) bind="${2:?--bind attend une valeur}"; shift 2 ;;
        --start) start=1; shift ;;
        --force) force=1; shift ;;
        -h|--help) usage 0 ;;
        *) echo "Option inconnue : $1" >&2; usage 1 ;;
    esac
done

if [ -f .env ] && [ "$force" -eq 0 ]; then
    if grep -Eq '^APP_SECRET=.{32,}' .env; then
        echo ".env existe déjà avec une clé APP_SECRET : rien à faire (--force pour le régénérer)."
        echo "Attention : régénérer APP_SECRET rend illisibles tous les secrets existants."
        [ "$start" -eq 1 ] && exec docker compose up -d
        exit 0
    fi
fi

# 48 octets aléatoires encodés en base64url (64 caractères, sans caractères spéciaux pour sed)
generate_secret() {
    if command -v openssl >/dev/null 2>&1; then
        openssl rand -base64 48
    else
        head -c 48 /dev/urandom | base64
    fi | tr -d '\n=' | tr '+/' '-_'
}

# remplace la valeur d'une variable dans .env ; les valeurs sont validées ci-dessous
set_env() {
    local name="$1" value="$2"
    sed -i.bak "s|^${name}=.*|${name}=${value}|" .env && rm -f .env.bak
}

if [ -n "$port" ] && ! [[ "$port" =~ ^[0-9]{1,5}$ && "$port" -ge 1 && "$port" -le 65535 ]]; then
    echo "Port invalide : $port" >&2
    exit 1
fi
# caractères volontairement restreints : la valeur est ensuite insérée dans .env via sed
if [ -n "$url" ] && ! [[ "$url" =~ ^https?://[A-Za-z0-9.-]+(:[0-9]{1,5})?(/[A-Za-z0-9._~/-]*[A-Za-z0-9._~-])?$ ]]; then
    echo "URL invalide (http(s)://hote[:port][/chemin], sans slash final) : $url" >&2
    exit 1
fi
if [ -n "$bind" ] && ! [[ "$bind" =~ ^[0-9a-fA-F.:]+$ ]]; then
    echo "Adresse d'écoute invalide : $bind" >&2
    exit 1
fi

umask 077
cp .env.dist .env
set_env APP_SECRET "$(generate_secret)"
[ -n "$port" ] && set_env APP_PORT "$port"
[ -n "$bind" ] && set_env APP_BIND "$bind"
if [ -n "$url" ]; then
    set_env APP_URL "$url"
elif [ -n "$port" ]; then
    set_env APP_URL "http://localhost:${port}"
fi
chmod 600 .env

configured_port="$(sed -n 's/^APP_PORT=//p' .env)"
if command -v ss >/dev/null 2>&1 && ss -ltn | awk '{print $4}' | grep -Eq "[:.]${configured_port}\$"; then
    echo "Attention : le port ${configured_port} semble déjà utilisé (modifier APP_PORT dans .env)." >&2
fi

echo "Fichier .env créé (droits 600) avec une clé APP_SECRET aléatoire."
echo "  URL publique : $(sed -n 's/^APP_URL=//p' .env)"
echo "  Écoute       : $(sed -n 's/^APP_BIND=//p' .env):${configured_port}"
echo "Sauvegardez APP_SECRET : sans elle, les secrets en cours sont irrécupérables."

if [ "$start" -eq 1 ]; then
    exec docker compose up -d
fi
echo "Démarrage : docker compose up -d"
