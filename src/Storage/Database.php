<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Storage;

use PDO;
use RuntimeException;

/**
 * Connexion SQLite et création automatique du schéma
 */
final class Database
{
    /**
     * Ouvre (et crée si besoin) la base SQLite
     */
    public static function connect(string $path): PDO
    {
        if ($path !== ':memory:') {
            $directory = dirname($path);
            if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
                throw new RuntimeException(sprintf('Impossible de créer le dossier %s', $directory));
            }
            // la base n'est lisible que par l'utilisateur du serveur web
            $previousUmask = umask(0077);
        }
        try {
            $pdo = new PDO(
                'sqlite:' . $path,
                options: [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } finally {
            if (isset($previousUmask)) {
                umask($previousUmask);
            }
        }
        $pdo->exec('PRAGMA busy_timeout = 5000');
        // écrase physiquement les données supprimées au lieu de seulement les marquer libres
        $pdo->exec('PRAGMA secure_delete = ON');
        // pas de WAL : les pages supprimées y survivraient jusqu'au prochain checkpoint
        $pdo->exec('PRAGMA journal_mode = DELETE');
        self::migrate($pdo);
        return $pdo;
    }

    /**
     * Crée le schéma s'il n'existe pas (idempotent)
     */
    public static function migrate(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS secrets (
                lookup_hash TEXT PRIMARY KEY NOT NULL,
                server_key BLOB NOT NULL,
                payload BLOB NULL,
                single_use INTEGER NOT NULL,
                expires_at INTEGER NULL,
                failed_attempts INTEGER NOT NULL DEFAULT 0,
                revoke_hash TEXT NOT NULL UNIQUE
            )'
        );
        $pdo->exec('CREATE INDEX IF NOT EXISTS secrets_expires_at ON secrets (expires_at)');
        // compteurs de limitation de débit : empreinte du client, jamais l'adresse IP
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS rate_limits (
                bucket TEXT PRIMARY KEY NOT NULL,
                hits INTEGER NOT NULL,
                expires_at INTEGER NOT NULL
            )'
        );
        $pdo->exec('CREATE INDEX IF NOT EXISTS rate_limits_expires_at ON rate_limits (expires_at)');
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS meta (
                name TEXT PRIMARY KEY NOT NULL,
                value INTEGER NOT NULL
            )'
        );
    }
}
