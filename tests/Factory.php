<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Test;

use PDO;
use SecretShare\Config;
use SecretShare\Crypto\Envelope;
use SecretShare\Crypto\JwtCodec;
use SecretShare\Crypto\KeyRing;
use SecretShare\Secret\SecretService;
use SecretShare\Storage\Database;
use SecretShare\Storage\SecretRepository;

/**
 * Construction des objets testés, avec une base SQLite en mémoire et un
 * Argon2id au coût minimal pour garder des tests rapides
 */
final class Factory
{
    public const string APP_SECRET = 'test-secret-key-0123456789-abcdefghijklmnop';
    public const string APP_URL = 'https://secret.example.org';

    public static function config(
        string $appSecret = self::APP_SECRET,
        string $appUrl = self::APP_URL,
        int $shortCodeLength = 10,
        int $maxStoredSecrets = 1000,
    ): Config {
        return new Config(
            appSecret: $appSecret,
            appUrl: $appUrl,
            shortCodeLength: $shortCodeLength,
            maxSecretLength: 1000,
            maxPasswordAttempts: 3,
            databasePath: ':memory:',
            maxStoredSecrets: $maxStoredSecrets,
        );
    }

    public static function pdo(): PDO
    {
        return Database::connect(':memory:');
    }

    public static function envelope(): Envelope
    {
        // coût Argon2id minimal autorisé par libsodium (1 passe, 8 Kio)
        return new Envelope(1, 8192);
    }

    public static function service(
        ?PDO $pdo = null,
        ?FrozenClock $clock = null,
        ?Config $config = null
    ): SecretService {
        $config ??= self::config();
        $clock ??= new FrozenClock();
        $keyRing = new KeyRing($config->appSecret);
        return new SecretService(
            $config,
            new SecretRepository($pdo ?? self::pdo()),
            $keyRing,
            self::envelope(),
            new JwtCodec($keyRing, $clock),
            $clock,
        );
    }
}
