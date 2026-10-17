<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * Configuration de Rector : modernise le code pour la version de PHP exigée par
 * composer.json. Lancé automatiquement par maintenance/upgrade.py après une montée
 * de version de PHP, ou à la main : vendor/bin/rector process --dry-run
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths(
        [
            __DIR__ . '/bin',
            __DIR__ . '/lang',
            __DIR__ . '/public',
            __DIR__ . '/src',
            __DIR__ . '/templates',
            __DIR__ . '/tests',
        ]
    )
    // version cible déduite de la contrainte « php » de composer.json
    ->withPhpSets();
