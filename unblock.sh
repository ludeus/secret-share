#!/usr/bin/env bash
#
# Secret Share - déblocage des adresses IP limitées
#
# Usage : ./unblock.sh <adresse IP>              débloque une adresse
#         ./unblock.sh --all                     débloque toutes les adresses
#         ./unblock.sh --status [<adresse IP>]   état d'une adresse, ou résumé global
#
# Agit sur les compteurs de l'application (dans le conteneur) et, si QOS_CONSOLE_URL est
# défini dans .env, sur ceux de mod_qos du serveur web frontal.
# Réservé à root et aux membres du groupe docker.
#
set -euo pipefail

cd "$(dirname "$(readlink -f "$0")")"

usage() {
    sed -n '5,7p' "$0" | sed 's/^# \{0,1\}//'
    exit 1
}

if [ "$(id -u)" -ne 0 ] && ! id -nG | tr ' ' '\n' | grep -qx docker; then
    echo "Réservé à root et aux membres du groupe docker." >&2
    exit 1
fi
if [ ! -f .env ]; then
    echo "Fichier .env introuvable : lancer d'abord ./install.sh." >&2
    exit 1
fi

# lit une variable de .env sans exécuter le fichier
env_value() {
    sed -n "s/^$1=//p" .env | tail -n 1
}

# validation stricte : l'adresse est transmise au conteneur et dans une URL
check_ip() {
    if ! [[ "$1" =~ ^[0-9a-fA-F.:]+$ ]]; then
        echo "Adresse IP invalide : $1" >&2
        exit 1
    fi
}

action="${1:-}"
ip=""
case "$action" in
    --all) [ $# -eq 1 ] || usage ;;
    --status) [ $# -le 2 ] || usage; ip="${2:-}" ;;
    ""|-*) usage ;;
    *) [ $# -eq 1 ] || usage; ip="$1" ;;
esac
[ -z "$ip" ] || check_ip "$ip"

echo "== Application"
docker compose exec -T app php bin/unblock "$@"

console="$(env_value QOS_CONSOLE_URL)"
if [ -z "$console" ]; then
    exit 0
fi
event="$(env_value QOS_EVENT)"
event="${event:-SECRET_SHARE}"

qos() {
    curl -fsS "${console}?action=$1&address=$2&event=${event}"
}

echo "== mod_qos"
case "$action" in
    --all)
        count=0
        # 2e colonne de la liste des adresses suivies par mod_qos
        for tracked in $(qos search '*' | awk '$3 ~ /^vip=/ { print $2 }'); do
            qos unlimit "$tracked" >/dev/null
            count=$((count + 1))
        done
        echo "$count adresse(s) débloquée(s)"
        ;;
    --status)
        if [ -n "$ip" ]; then
            qos search "$ip"
        else
            echo "$(qos search '*' | awk '$3 ~ /^vip=/' | grep -cv 'limit=0/') adresse(s) avec un compteur non nul"
        fi
        ;;
    *)
        qos unlimit "$ip"
        ;;
esac
