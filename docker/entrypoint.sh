#!/bin/sh
# Secret Share - point d'entrée du conteneur
# Vérifie la configuration et initialise la base avant de démarrer Apache.
set -eu

php /app/bin/install

exec "$@"
