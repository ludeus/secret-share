#!/bin/sh
#
# Installe le timer systemd (utilisateur courant) de la maintenance mensuelle
#
# Usage : maintenance/systemd/install.sh
#
set -eu

root="$(cd "$(dirname "$(readlink -f "$0")")/../.." && pwd)"
units="${XDG_CONFIG_HOME:-$HOME/.config}/systemd/user"
mkdir -p "$units"

sed "s#@ROOT@#${root}#" "$root/maintenance/systemd/secret-share-maintenance.service" \
    > "$units/secret-share-maintenance.service"
cp "$root/maintenance/systemd/secret-share-maintenance.timer" "$units/"

systemctl --user daemon-reload
systemctl --user enable --now secret-share-maintenance.timer
systemctl --user list-timers secret-share-maintenance.timer --no-pager

# sans session ouverte, les timers utilisateur ne tournent que si « linger » est actif
if [ "$(loginctl show-user "$(id -un)" -p Linger --value 2>/dev/null)" != "yes" ]; then
    echo "Attention : lancer « loginctl enable-linger $(id -un) » pour que le timer tourne sans session ouverte."
fi
