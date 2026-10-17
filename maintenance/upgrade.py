#!/usr/bin/env python3
"""
Secret Share - montées de version des composants du projet

Usage : maintenance/upgrade.py [--dry-run] [--pin-only]

Passe en revue chaque composant versionné et le met à jour :
  - PHP (image Docker, composer.json, CI, documentation) : dernier cycle stable, sorti depuis
    au moins PHP_MIN_AGE_DAYS jours et disposant d'une image officielle ; le code est ensuite
    modernisé par Rector ;
  - dépendances Composer, montées majeures comprises (les contraintes de composer.json sont
    élargies) ; un paquet qui empêche la résolution est laissé de côté ;
  - dépendances npm des tests fonctionnels, montées majeures comprises ;
  - Node.js de la CI : dernière version LTS active ;
  - actions GitHub de la CI : dernière version publiée ;
  - images d'outils : version majeure de Composer, ShellCheck ;
  - images de base du Dockerfile figées par empreinte (@sha256) : chaque version publiée
    correspond à une image de base précise, mise à jour à chaque exécution.

Chaque changement est écrit sur la sortie standard sous la forme « UPGRADE: <description> »,
les avertissements sous la forme « WARNING: <description> ». Le fichier indiqué par la
variable UPGRADE_STATE reçoit « minor » si PHP ou une dépendance majeure a changé.

Variables d'environnement : GITHUB_TOKEN_FILE (limite de requêtes de l'API GitHub),
PHP_MIN_AGE_DAYS (90 par défaut), PHP_EOL_WARNING_DAYS (180 par défaut).
"""

import argparse
import datetime
import json
import os
import re
import subprocess
import sys
import urllib.error
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
TODAY = datetime.date.today()
PHP_MIN_AGE_DAYS = int(os.environ.get('PHP_MIN_AGE_DAYS', '90'))
PHP_EOL_WARNING_DAYS = int(os.environ.get('PHP_EOL_WARNING_DAYS', '180'))

dry_run = False
minor_release = False
php_upgraded = False


def report(kind, message):
    print(f'{kind}: {message}', flush=True)


def http_json(url):
    headers = {'User-Agent': 'secret-share-maintenance', 'Accept': 'application/json'}
    token_file = os.environ.get('GITHUB_TOKEN_FILE')
    if url.startswith('https://api.github.com/') and token_file and Path(token_file).is_file():
        headers['Authorization'] = 'Bearer ' + Path(token_file).read_text().strip()
    request = urllib.request.Request(url, headers=headers)
    with urllib.request.urlopen(request, timeout=30) as response:
        return json.load(response)


def http_ok(url):
    try:
        http_json(url)
        return True
    except urllib.error.HTTPError:
        return False


def version_key(version):
    return tuple(int(part) for part in re.findall(r'\d+', version))


def edit(path, pattern, replacement, count=0):
    """Remplace un motif dans un fichier ; retourne le nombre de remplacements"""
    file = ROOT / path
    text = file.read_text(encoding='utf-8')
    new_text, replaced = re.subn(pattern, replacement, text, count=count, flags=re.M)
    if replaced and new_text != text and not dry_run:
        file.write_text(new_text, encoding='utf-8')
    return replaced if new_text != text else 0


def run(command, **kwargs):
    return subprocess.run(command, cwd=ROOT, text=True, capture_output=True, **kwargs)


def composer(*args):
    """Composer dans un conteneur, avec les droits de l'utilisateur courant"""
    return run([
        'docker', 'run', '--rm', '-v', f'{ROOT}:/app', '-w', '/app',
        '-u', f'{os.getuid()}:{os.getgid()}', '-e', 'COMPOSER_HOME=/tmp/composer',
        composer_image(), *args, '--no-interaction',
    ])


def composer_image():
    match = re.search(r'FROM composer:(\d+)', (ROOT / 'Dockerfile').read_text(encoding='utf-8'))
    return f'composer:{match.group(1) if match else "2"}'


# ---------------------------------------------------------------------------
# PHP
# ---------------------------------------------------------------------------
def upgrade_php():
    global minor_release, php_upgraded
    dockerfile = (ROOT / 'Dockerfile').read_text(encoding='utf-8')
    current = re.search(r'^ARG PHP_VERSION=(\d+\.\d+)$', dockerfile, re.M).group(1)
    cycles = {c['cycle']: c for c in http_json('https://endoflife.date/api/php.json')}

    eol = cycles.get(current, {}).get('eol')
    if isinstance(eol, str):
        remaining = (datetime.date.fromisoformat(eol) - TODAY).days
        if remaining < PHP_EOL_WARNING_DAYS:
            report('WARNING', f'PHP {current} : fin du support de sécurité le {eol} ({remaining} jours)')

    def eligible(cycle):
        released = datetime.date.fromisoformat(cycle['releaseDate'])
        cycle_eol = cycle['eol']
        supported = cycle_eol is False or datetime.date.fromisoformat(cycle_eol) > TODAY
        return (supported and (TODAY - released).days >= PHP_MIN_AGE_DAYS
                and version_key(cycle['cycle']) > version_key(current))

    candidates = sorted((c['cycle'] for c in cycles.values() if eligible(c)), key=version_key, reverse=True)
    target = next(
        (v for v in candidates
         if http_ok(f'https://hub.docker.com/v2/repositories/library/php/tags/{v}-apache')),
        None,
    )
    if target is None:
        return

    edit('Dockerfile', rf'^ARG PHP_VERSION={re.escape(current)}$', f'ARG PHP_VERSION={target}')
    edit('composer.json', r'("php": ">=)\d+\.\d+(")', rf'\g<1>{target}\g<2>', count=1)
    edit('composer.json', r'("platform": \{\s*"php": ")[\d.]+(")', rf'\g<1>{target}.0\g<2>')
    # CI : version de référence et version la plus récente
    newest = max((c for c in cycles if eligible(cycles[c]) or c == target), key=version_key)
    matrix = f"['{target}', '{newest}']" if newest != target else f"['{target}']"
    edit('.github/workflows/ci.yml', r"^(\s*php: )\[[^\]]*\]$", rf'\g<1>{matrix}')
    edit('.github/workflows/ci.yml', rf"(php-version: ')({re.escape(current)})(')", rf'\g<1>{target}\g<3>')
    for doc in ('README.md', 'CONTRIBUTING.md'):
        edit(doc, rf'PHP {re.escape(current)}\+', f'PHP {target}+')
        edit(doc, r'sur PHP [\d.]+ et [\d.]+', f'sur PHP {target}' + (f' et {newest}' if newest != target else ''))
    report('UPGRADE', f'PHP {current} => {target}')
    minor_release = True
    php_upgraded = True


# ---------------------------------------------------------------------------
# Composer
# ---------------------------------------------------------------------------
def upgrade_composer_packages():
    global minor_release
    result = composer('outdated', '--direct', '--major-only', '--format=json')
    if result.returncode != 0:
        report('WARNING', 'composer outdated a échoué : ' + result.stderr.strip()[-300:])
        return
    outdated = json.loads(result.stdout or '{}').get('installed', [])
    manifest = json.loads((ROOT / 'composer.json').read_text(encoding='utf-8'))
    for package in outdated:
        name, latest = package['name'], package['latest']
        if not re.match(r'^v?\d', latest):
            continue
        major = version_key(latest)[0]
        constraint = f'^{major}.0'
        dev = name in manifest.get('require-dev', {})
        if dry_run:
            report('UPGRADE', f'composer : {name} {package["version"]} => {latest}')
            continue
        backup = {f: (ROOT / f).read_text(encoding='utf-8') for f in ('composer.json', 'composer.lock')}
        args = ['require', f'{name}:{constraint}', '--update-with-all-dependencies', '--no-progress']
        if dev:
            args.append('--dev')
        if composer(*args).returncode == 0:
            report('UPGRADE', f'composer : {name} {package["version"]} => {latest} (contrainte {constraint})')
            minor_release = True
        else:
            for file, content in backup.items():
                (ROOT / file).write_text(content, encoding='utf-8')
            report('WARNING', f'composer : {name} {latest} non installable (conflit de dépendances), laissé en place')


def rector():
    """Modernise le code pour la version de PHP de composer.json, puis rétablit le standard de codage"""
    if dry_run or not (ROOT / 'vendor/bin/rector').exists():
        return
    php_version = re.search(r'ARG PHP_VERSION=([\d.]+)', (ROOT / 'Dockerfile').read_text(encoding='utf-8')).group(1)
    image = f'php:{php_version}-cli'
    base = ['docker', 'run', '--rm', '-v', f'{ROOT}:/app', '-w', '/app', '-u', f'{os.getuid()}:{os.getgid()}', image]
    before = run(['git', 'diff', '--stat', '--', 'src', 'tests', 'bin']).stdout
    run(base + ['php', '-d', 'memory_limit=1G', 'vendor/bin/rector', 'process', '--no-progress-bar'])
    run(base + ['php', 'vendor/bin/phpcbf', '-q'])
    if run(['git', 'diff', '--stat', '--', 'src', 'tests', 'bin']).stdout != before:
        report('UPGRADE', 'code modernisé par Rector pour la nouvelle version de PHP')


# ---------------------------------------------------------------------------
# npm (tests fonctionnels)
# ---------------------------------------------------------------------------
def upgrade_npm_packages():
    package_file = ROOT / 'tests/e2e/package.json'
    before = json.loads(package_file.read_text(encoding='utf-8')).get('dependencies', {})
    args = ['npx', '-y', 'npm-check-updates@latest', '--packageFile', str(package_file), '--jsonUpgraded']
    if not dry_run:
        args.append('-u')
    result = run(args)
    if result.returncode != 0:
        report('WARNING', 'npm-check-updates a échoué : ' + result.stderr.strip()[-300:])
        return
    upgraded = json.loads(result.stdout or '{}')
    for name, version in upgraded.items():
        report('UPGRADE', f'npm : {name} {before.get(name, "?")} => {version}')
    if upgraded and not dry_run:
        run(['npm', 'install', '--prefix', 'tests/e2e', '--no-audit', '--no-fund', '--silent'])


# ---------------------------------------------------------------------------
# Node.js de la CI, actions GitHub, images d'outils
# ---------------------------------------------------------------------------
def upgrade_ci_node():
    workflow = (ROOT / '.github/workflows/ci.yml').read_text(encoding='utf-8')
    match = re.search(r"node-version: '(\d+)'", workflow)
    if not match:
        return
    current = match.group(1)
    lts = [
        c['cycle'] for c in http_json('https://endoflife.date/api/nodejs.json')
        if isinstance(c.get('lts'), str) and datetime.date.fromisoformat(c['lts']) <= TODAY
        and (c['eol'] is False or datetime.date.fromisoformat(c['eol']) > TODAY)
    ]
    target = max(lts, key=version_key) if lts else current
    if version_key(target) > version_key(current):
        edit('.github/workflows/ci.yml', r"node-version: '\d+'", f"node-version: '{target}'")
        report('UPGRADE', f'Node.js de la CI : {current} => {target}')


def latest_release(repository):
    try:
        return http_json(f'https://api.github.com/repos/{repository}/releases/latest')['tag_name']
    except urllib.error.HTTPError:
        tags = http_json(f'https://api.github.com/repos/{repository}/tags?per_page=100')
        stable = [t['name'] for t in tags if re.fullmatch(r'v?\d+(\.\d+)*', t['name'])]
        return max(stable, key=version_key) if stable else None


def upgrade_github_actions():
    workflow = (ROOT / '.github/workflows/ci.yml').read_text(encoding='utf-8')
    for repository, current in sorted(set(re.findall(r'uses: ([\w.-]+/[\w.-]+)@(v[\d.]+)', workflow))):
        latest = latest_release(repository)
        if latest is None or version_key(latest) <= version_key(current):
            continue
        # une référence à la seule version majeure (v4) reste une référence majeure (v5)
        target = f'v{version_key(latest)[0]}' if re.fullmatch(r'v\d+', current) else latest
        if target != current:
            edit('.github/workflows/ci.yml', rf'(uses: {re.escape(repository)}@){re.escape(current)}\b', rf'\g<1>{target}')
            report('UPGRADE', f'action GitHub {repository} : {current} => {target}')


def upgrade_tool_images():
    files = ['Dockerfile', 'maintenance/monthly.sh']
    latest_composer = latest_release('composer/composer')
    current_composer = composer_image().split(':')[1]
    if latest_composer and version_key(latest_composer)[0] > int(current_composer):
        major = version_key(latest_composer)[0]
        for file in files:
            edit(file, rf'\bcomposer:{current_composer}\b', f'composer:{major}')
        report('UPGRADE', f'image Composer : {current_composer} => {major}')

    script = (ROOT / 'maintenance/monthly.sh').read_text(encoding='utf-8')
    match = re.search(r'koalaman/shellcheck:(v[\d.]+)', script)
    latest = latest_release('koalaman/shellcheck')
    if match and latest and version_key(latest) > version_key(match.group(1)) \
            and http_ok(f'https://hub.docker.com/v2/repositories/koalaman/shellcheck/tags/{latest}'):
        edit('maintenance/monthly.sh', r'koalaman/shellcheck:v[\d.]+', f'koalaman/shellcheck:{latest}')
        report('UPGRADE', f'image ShellCheck : {match.group(1)} => {latest}')


def docker_hub_digest(repository, tag):
    """Empreinte de l'index multi-plateforme d'une image Docker Hub"""
    name = repository if '/' in repository else f'library/{repository}'
    return http_json(f'https://hub.docker.com/v2/repositories/{name}/tags/{tag}')['digest']


def pin_base_images():
    """Fige les images de base sur l'empreinte actuelle de leur étiquette"""
    path = ROOT / 'Dockerfile'
    original = path.read_text(encoding='utf-8')
    text = original
    changes = []

    php_version = re.search(r'^ARG PHP_VERSION=([\d.]+)$', text, re.M).group(1)
    php_digest = docker_hub_digest('php', f'{php_version}-apache')
    previous = re.search(r'^ARG PHP_IMAGE_DIGEST=(\S*)$', text, re.M)
    if previous:
        text = re.sub(r'^ARG PHP_IMAGE_DIGEST=\S*$', f'ARG PHP_IMAGE_DIGEST={php_digest}', text, flags=re.M)
    else:
        text = re.sub(
            r'^(ARG PHP_VERSION=[\d.]+)$',
            rf'\1\n# empreinte de php:${{PHP_VERSION}}-apache, mise à jour par maintenance/upgrade.py\n'
            rf'ARG PHP_IMAGE_DIGEST={php_digest}',
            text,
            flags=re.M,
        )
    text = re.sub(
        r'FROM php:\$\{PHP_VERSION\}-apache(@\$\{PHP_IMAGE_DIGEST\})?',
        'FROM php:${PHP_VERSION}-apache@${PHP_IMAGE_DIGEST}',
        text,
    )
    if not previous or previous.group(1) != php_digest:
        changes.append(f'php:{php_version}-apache@{php_digest[:19]}…')

    major = re.search(r'FROM composer:(\d+)', text).group(1)
    composer_digest = docker_hub_digest('composer', major)
    pinned = re.search(rf'composer:{major}@(sha256:[0-9a-f]{{64}})', text)
    text = re.sub(rf'\bcomposer:{major}(@sha256:[0-9a-f]{{64}})?', f'composer:{major}@{composer_digest}', text)
    if not pinned or pinned.group(1) != composer_digest:
        changes.append(f'composer:{major}@{composer_digest[:19]}…')

    if text != original:
        if not dry_run:
            path.write_text(text, encoding='utf-8')
        report('UPGRADE', 'images de base figées sur leur dernière version : ' + ', '.join(changes))


# ---------------------------------------------------------------------------
def main():
    global dry_run
    parser = argparse.ArgumentParser(description='Montées de version des composants de Secret Share')
    parser.add_argument('--dry-run', action='store_true', help='affiche les montées possibles sans rien modifier')
    parser.add_argument('--pin-only', action='store_true', help='fige uniquement les images de base')
    options = parser.parse_args()
    dry_run = options.dry_run
    if options.pin_only:
        pin_base_images()
        return 0

    steps = [
        ('PHP', upgrade_php),
        ('Composer', upgrade_composer_packages),
        ('npm', upgrade_npm_packages),
        ('Node.js', upgrade_ci_node),
        ('actions GitHub', upgrade_github_actions),
        ('images d\'outils', upgrade_tool_images),
        # en dernier : fige les images après une éventuelle montée de PHP ou de Composer
        ('images de base', pin_base_images),
    ]
    for label, step in steps:
        try:
            step()
        except Exception as error:  # une source indisponible ne doit pas bloquer les autres montées
            report('WARNING', f'{label} : vérification impossible ({error})')
    if php_upgraded:
        rector()

    state = os.environ.get('UPGRADE_STATE')
    if state and minor_release and not dry_run:
        Path(state).write_text('minor\n', encoding='utf-8')
    return 0


if __name__ == '__main__':
    sys.exit(main())
