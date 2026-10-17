#!/usr/bin/env bash
#
# Secret Share - maintenance mensuelle automatisée
#
# Usage : maintenance/monthly.sh [--dry-run] [--no-deploy] [--force-release]
#
#   --dry-run        met à jour, teste et analyse sans rien commiter, publier ni déployer
#   --no-deploy      publie le patch mais ne met pas à jour le serveur
#   --force-release  publie un patch même si rien n'a changé
#
# Étapes :
#   1. mise à jour des dépendances (Composer, tests e2e) et reconstruction de l'image
#      avec les derniers correctifs de l'image de base et de Debian
#   2. tests : standard de codage, traductions, analyse statique, PHPUnit, tests e2e
#   3. analyse Trivy de l'image et des dépendances ; en cas de vulnérabilité, correction
#      par Claude Code (montée de version ou exception justifiée dans .trivyignore)
#   4. publication d'un patch (version, CHANGELOG, tag) si quelque chose a changé
#   5. attente de la CI, mise à jour du serveur, tests e2e en production, retour à la
#      version précédente en cas d'échec
#
# Configuration : maintenance/maintenance.env (voir maintenance.env.dist).
# Journal : maintenance/logs/<date>.log
#
set -Eeuo pipefail

ROOT="$(cd "$(dirname "$(readlink -f "$0")")/.." && pwd)"
cd "$ROOT"

dry_run=0
deploy=1
force_release=0
for arg in "$@"; do
    case "$arg" in
        --dry-run) dry_run=1; deploy=0 ;;
        --no-deploy) deploy=0 ;;
        --force-release) force_release=1 ;;
        -h|--help) sed -n '3,22p' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
        *) echo "Option inconnue : $arg" >&2; exit 1 ;;
    esac
done

if [ ! -f maintenance/maintenance.env ]; then
    echo "maintenance/maintenance.env introuvable : copier maintenance/maintenance.env.dist" >&2
    exit 1
fi
# shellcheck source=/dev/null
. maintenance/maintenance.env
: "${GITHUB_REPO:?}" "${GITHUB_TOKEN_FILE:?}" "${DOCKER_IMAGE:?}"
export PATH="${EXTRA_PATH:+$EXTRA_PATH:}$PATH"

mkdir -p maintenance/logs
LOG="maintenance/logs/$(date +%Y-%m-%d_%H%M%S).log"
exec > >(tee -a "$LOG") 2>&1

# une seule exécution à la fois
exec 9>maintenance/logs/.lock
if ! flock -n 9; then
    echo "Une maintenance est déjà en cours." >&2
    exit 1
fi

WORK_IMAGE="secret-share:maintenance"
TEST_IMAGE="secret-share:maintenance-test"
TRIVY_IMAGE="aquasec/trivy:${TRIVY_VERSION:-0.58.1}"
TRIVY_CACHE="${HOME}/.cache/trivy"
E2E_CONTAINER="secret-share-maintenance-e2e"
summary=()

step() {
    printf '\n==== %s : %s\n' "$(date '+%F %T')" "$*"
}

notify() {
    local status="$1"
    local message="Secret Share - maintenance ${status}"
    if [ ${#summary[@]} -gt 0 ]; then
        message+=$'\n'"$(printf -- '- %s\n' "${summary[@]}")"
    fi
    echo "$message"
    if [ -n "${NOTIFY_COMMAND:-}" ]; then
        printf '%s\n' "$message" | sh -c "$NOTIFY_COMMAND" || true
    fi
}

fail() {
    summary+=("ÉCHEC : $*")
    notify "en échec (journal : $LOG)"
    exit 1
}

cleanup() {
    docker rm -f "$E2E_CONTAINER" >/dev/null 2>&1 || true
    [ -z "${ASKPASS:-}" ] || rm -f "$ASKPASS"
}
trap cleanup EXIT
trap 'fail "erreur inattendue à la ligne $LINENO"' ERR

github_api() {
    curl -fsS -H "Authorization: Bearer $(tr -d '[:space:]' < "$GITHUB_TOKEN_FILE")" \
        -H "Accept: application/vnd.github+json" "https://api.github.com/repos/${GITHUB_REPO}/$1"
}

# git authentifié par le token, sans l'écrire dans la configuration ni sur la ligne de commande
git_remote() {
    if [ -z "${ASKPASS:-}" ]; then
        ASKPASS="$(mktemp)"
        cat > "$ASKPASS" <<EOF
#!/bin/sh
case "\$1" in
    Username*) echo x-access-token ;;
    *) tr -d '[:space:]' < "$GITHUB_TOKEN_FILE" ;;
esac
EOF
        chmod 700 "$ASKPASS"
    fi
    GIT_ASKPASS="$ASKPASS" GIT_TERMINAL_PROMPT=0 git -c credential.helper= "$@"
}

current_version() {
    sed -n "s/.*public const string VERSION = '\([0-9.]*\)';.*/\1/p" src/App.php
}

# ---------------------------------------------------------------------------
step "Préparation"
[ "$(git rev-parse --abbrev-ref HEAD)" = "main" ] || fail "le dépôt doit être sur la branche main"
# les fichiers non suivis sont ignorés : seuls les fichiers versionnés sont commités
[ -z "$(git status --porcelain --untracked-files=no)" ] || fail "le dépôt contient des modifications non commitées"
git_remote pull -q --ff-only origin main
previous_version="$(current_version)"
echo "Version actuelle : $previous_version"

# ---------------------------------------------------------------------------
step "Mise à jour des dépendances"
composer_output="$(docker run --rm -v "$ROOT:/app" -w /app -u "$(id -u):$(id -g)" \
    -e COMPOSER_HOME=/tmp/composer composer:2 update --no-interaction --no-progress --no-scripts 2>&1)" \
    || { echo "$composer_output"; fail "composer update a échoué"; }
echo "$composer_output"
# « - Upgrading vendor/paquet (1.0.0 => 1.0.1) » : une ligne de résumé par paquet
while read -r line; do
    summary+=("$line")
done < <(echo "$composer_output" | sed -n 's/^ *- \(Upgrading\|Installing\|Removing\) \(.*\)$/composer : \1 \2/p')
npm update --prefix tests/e2e --no-audit --no-fund --silent || fail "npm update a échoué"
npm ci --prefix tests/e2e --no-audit --no-fund --silent || fail "npm ci a échoué"
if ! git diff --quiet -- tests/e2e/package-lock.json; then
    summary+=("dépendances des tests e2e mises à jour")
fi

# ---------------------------------------------------------------------------
build_and_test() {
    step "Construction des images"
    docker build --pull -q --target production -t "$WORK_IMAGE" . >/dev/null || return 1
    docker build --pull -q --target test -t "$TEST_IMAGE" . >/dev/null || return 1

    step "Tests : standard, traductions, analyse statique, PHPUnit"
    docker run --rm "$TEST_IMAGE" || return 1
    docker run --rm -v "$ROOT:/mnt" -w /mnt koalaman/shellcheck:v0.10.0 \
        install.sh unblock.sh docker/entrypoint.sh maintenance/monthly.sh || return 1

    step "Tests fonctionnels (navigateur)"
    local port
    port="$(python3 -c 'import socket; s=socket.socket(); s.bind(("127.0.0.1", 0)); print(s.getsockname()[1])')"
    docker rm -f "$E2E_CONTAINER" >/dev/null 2>&1 || true
    docker run -d --name "$E2E_CONTAINER" -p "127.0.0.1:${port}:8080" \
        -e APP_SECRET="$(openssl rand -base64 48)" -e APP_URL="http://127.0.0.1:${port}" \
        -e RATE_LIMIT_CREATE_PER_MINUTE=0 -e RATE_LIMIT_CREATE_PER_HOUR=0 -e RATE_LIMIT_READ_PER_MINUTE=0 \
        "$WORK_IMAGE" >/dev/null || return 1
    local _
    for _ in $(seq 1 30); do
        curl -fsS "http://127.0.0.1:${port}/health" >/dev/null 2>&1 && break
        sleep 1
    done
    E2E_BASE_URL="http://127.0.0.1:${port}" E2E_RATE_LIMIT_PAUSE=0 node tests/e2e/e2e.js || return 1
    docker rm -f "$E2E_CONTAINER" >/dev/null
}

# retourne 1 si des vulnérabilités corrigeables subsistent ; le rapport est dans $1
trivy_scan() {
    local report="$1" status=0
    step "Analyse Trivy"
    : > "$report"
    docker run --rm -v /var/run/docker.sock:/var/run/docker.sock -v "$TRIVY_CACHE:/root/.cache/trivy" \
        -v "$ROOT:/src:ro" "$TRIVY_IMAGE" image --quiet --severity HIGH,CRITICAL --ignore-unfixed \
        --ignorefile /src/.trivyignore --exit-code 1 "$WORK_IMAGE" >> "$report" 2>&1 || status=1
    docker run --rm -v "$TRIVY_CACHE:/root/.cache/trivy" -v "$ROOT:/src:ro" "$TRIVY_IMAGE" \
        fs --quiet --severity HIGH,CRITICAL --ignore-unfixed --ignorefile /src/.trivyignore \
        --exit-code 1 /src >> "$report" 2>&1 || status=1
    cat "$report"
    return $status
}

ai_fix() {
    local report="$1"
    command -v claude >/dev/null 2>&1 || return 1
    [ "${AI_FIX:-1}" = "1" ] || return 1
    step "Correction des vulnérabilités par Claude Code"
    claude -p --permission-mode acceptEdits \
        --allowedTools "Read,Edit,Write,Glob,Grep,Bash(docker run --rm:*),Bash(git diff:*),Bash(git status:*)" \
        "Tu interviens sur le projet Secret Share (${ROOT}) lors de sa maintenance mensuelle automatisée.
Trivy signale les vulnérabilités HIGH/CRITICAL corrigeables suivantes dans l'image Docker ou les dépendances :

$(cat "$report")

Corrige-les avec le changement le plus petit possible, par ordre de préférence :
1. montée de version de la dépendance concernée (composer.json/composer.lock via
   « docker run --rm -v ${ROOT}:/app -w /app -u $(id -u):$(id -g) composer:2 … », ou Dockerfile :
   image de base, paquets) ;
2. uniquement si aucune correction n'est possible ou si la vulnérabilité ne concerne pas
   l'application (composant non utilisé, chemin de code non atteignable), ajoute une
   exception dans .trivyignore en respectant le format décrit en tête du fichier : date du
   jour, paquet, raison précise, date de réexamen à 3 mois.
Ne modifie aucun autre fichier, ne commite rien. Termine par un résumé d'une ligne par CVE
(corrigée ou ignorée, et pourquoi)." || return 1
}

# ---------------------------------------------------------------------------
build_and_test || fail "les tests ont échoué après la mise à jour des dépendances"

report="$(mktemp)"
if ! trivy_scan "$report"; then
    ai_fix "$report" || fail "vulnérabilités détectées et aucune correction automatique possible"
    summary+=("vulnérabilités traitées par Claude Code (voir le journal)")
    build_and_test || fail "les tests ont échoué après la correction des vulnérabilités"
    trivy_scan "$report" || fail "des vulnérabilités subsistent après correction"
fi
rm -f "$report"

# ---------------------------------------------------------------------------
step "Recherche de changements à publier"
# paquets système de l'image reconstruite comparés à ceux de la dernière version publiée
packages() {
    docker run --rm --entrypoint dpkg-query "$1" -W -f '${Package} ${Version}\n' 2>/dev/null | sort
}
system_changes="$(diff <(packages "${DOCKER_IMAGE}:${previous_version}") <(packages "$WORK_IMAGE") \
    | sed -n 's/^> //p' || true)"
if [ -n "$system_changes" ]; then
    summary+=("paquets système mis à jour : $(echo "$system_changes" | awk '{print $1}' | sort -u | paste -sd ' ')")
fi
git_changes="$(git status --porcelain --untracked-files=no)"

if [ -z "$git_changes" ] && [ -z "$system_changes" ] && [ "$force_release" -eq 0 ]; then
    summary+=("aucun changement : rien à publier")
    notify "terminée"
    exit 0
fi

if [ "$dry_run" -eq 1 ]; then
    git status --short --untracked-files=no
    summary+=("simulation : rien n'a été commité ni publié, modifications laissées dans le dépôt pour relecture")
    notify "terminée (simulation)"
    exit 0
fi

# ---------------------------------------------------------------------------
IFS=. read -r major minor patch <<< "$previous_version"
version="${major}.${minor}.$((patch + 1))"
step "Publication de la version $version"
sed -i "s/public const string VERSION = '${previous_version}';/public const string VERSION = '${version}';/" src/App.php

maintenance_notes="### Maintenance"$'\n\n'
for line in "${summary[@]}"; do
    maintenance_notes+="- ${line}"$'\n'
done
# une section « [Non publié] » (changements commités à la main) devient celle de la version
python3 - "${version}" "$(date +%F)" "$maintenance_notes" <<'PY'
import sys
version, day, notes = sys.argv[1:]
path = 'CHANGELOG.md'
text = open(path, encoding='utf-8').read()
heading = f'## [{version}] - {day}'
if '\n## [Non publié]' in text:
    start = text.index('\n## [Non publié]') + 1
    end = text.find('\n## [', start + 1)
    end = len(text) if end == -1 else end + 1
    section = text[start:end].replace('## [Non publié]', heading, 1).rstrip('\n')
    text = text[:start] + section + '\n\n' + notes + '\n' + text[end:]
else:
    position = text.index('\n## [') + 1
    text = text[:position] + heading + '\n\n' + notes + '\n' + text[position:]
open(path, 'w', encoding='utf-8').write(text)
PY

git add -u
git commit -q -m "Maintenance mensuelle : version ${version}" -m "$(printf -- '- %s\n' "${summary[@]}")"
git tag -a "v${version}" -m "Secret Share ${version}"
git_remote push -q origin main "v${version}"

step "Attente de la CI du tag v${version}"
conclusion=""
for _ in $(seq 1 90); do
    run="$(github_api "actions/runs?per_page=10" | python3 -c "
import json, sys
runs = [r for r in json.load(sys.stdin)['workflow_runs'] if r['head_branch'] == 'v${version}']
print(f\"{runs[0]['status']} {runs[0]['conclusion']}\" if runs else 'absent')")"
    case "$run" in
        "completed success") conclusion=success; break ;;
        completed*) conclusion="${run#completed }"; break ;;
    esac
    sleep 30
done
[ "$conclusion" = "success" ] || fail "la CI du tag v${version} n'a pas réussi (${conclusion:-délai dépassé})"
summary+=("version ${version} publiée sur Docker Hub")

# ---------------------------------------------------------------------------
if [ "$deploy" -eq 0 ] || [ -z "${DEPLOY_HOST:-}" ]; then
    notify "terminée sans déploiement"
    exit 0
fi

step "Mise à jour du serveur ${DEPLOY_HOST}"
remote() {
    ssh -o BatchMode=yes "$DEPLOY_HOST" "cd '${DEPLOY_PATH}' && $1"
}
previous_image="$(remote "sed -n 's/^IMAGE=//p' .env")"
deploy_image() {
    remote "git pull -q --ff-only && sed -i 's#^IMAGE=.*#IMAGE=$1#' .env && docker compose pull -q \
        && docker compose up -d --no-build && for i in \$(seq 1 30); do \
        curl -fsS http://127.0.0.1:\$(sed -n 's/^APP_PORT=//p' .env)/health >/dev/null 2>&1 && exit 0; sleep 2; done; exit 1"
}
rollback() {
    step "Retour à ${previous_image}"
    deploy_image "$previous_image" || true
    fail "$1 : serveur revenu à ${previous_image}"
}

deploy_image "${DOCKER_IMAGE}:${version}" || rollback "le conteneur ne démarre pas"
hub_digest="$(curl -fsS "https://hub.docker.com/v2/repositories/${DOCKER_IMAGE}/tags/${version}" \
    | python3 -c 'import json, sys; print(json.load(sys.stdin)["digest"])')"
server_digest="$(remote "docker image inspect ${DOCKER_IMAGE}:${version} --format '{{index .RepoDigests 0}}'")"
[ "${server_digest#*@}" = "$hub_digest" ] || rollback "l'image du serveur ne correspond pas à celle publiée"

if [ -n "${PUBLIC_URL:-}" ]; then
    step "Tests fonctionnels en production"
    E2E_BASE_URL="$PUBLIC_URL" node tests/e2e/e2e.js || rollback "les tests fonctionnels en production ont échoué"
fi
remote "docker image prune -f >/dev/null"
summary+=("serveur ${DEPLOY_HOST} mis à jour en ${version}")
notify "terminée"
