<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare;

use SecretShare\Http\ClientIp;

/**
 * Configuration de l'application, construite et validée à partir des
 * variables d'environnement. Toute valeur invalide empêche le démarrage :
 * mieux vaut refuser de servir que de servir sans sécurité.
 */
final readonly class Config
{
    public const int MIN_SECRET_KEY_LENGTH = 32;
    public const int MIN_SHORT_CODE_LENGTH = 8;
    public const int MAX_SHORT_CODE_LENGTH = 64;
    public const array SUPPORTED_LOCALES = ['fr', 'en'];
    public const string DEFAULT_SOURCE_URL = 'https://github.com/ludeus/secret-share';

    public function __construct(
        #[\SensitiveParameter]
        public string $appSecret,
        public string $appUrl,
        public int $shortCodeLength = 10,
        public int $maxSecretLength = 10000,
        public int $maxPasswordAttempts = 5,
        public string $databasePath = '/app/data/secret-share.sqlite',
        public string $defaultLocale = 'fr',
        public string $sourceUrl = self::DEFAULT_SOURCE_URL,
        public int $maxStoredSecrets = 100000,
        public int $argon2Concurrency = 2,
        public int $maxUnlimitedSecrets = 1000,
        /** @var list<string> */
        public array $trustedProxies = [],
        public int $createLimitPerMinute = 5,
        public int $createLimitPerHour = 30,
        public int $readLimitPerMinute = 30,
    ) {
        if (strlen($appSecret) < self::MIN_SECRET_KEY_LENGTH) {
            throw new ConfigException(
                sprintf(
                    'APP_SECRET doit contenir au moins %d caractères (lancer ./install.sh pour en générer un).',
                    self::MIN_SECRET_KEY_LENGTH
                )
            );
        }
        if (!preg_match('#^https?://[^/?\#\s]+(/[^?\#\s]*)?$#', $appUrl) || str_ends_with($appUrl, '/')) {
            throw new ConfigException(
                'APP_URL doit être une URL http(s) absolue sans slash final, ex: https://secret.example.org'
            );
        }
        if ($shortCodeLength < self::MIN_SHORT_CODE_LENGTH || $shortCodeLength > self::MAX_SHORT_CODE_LENGTH) {
            throw new ConfigException(
                sprintf(
                    'SHORT_CODE_LENGTH doit être compris entre %d et %d.',
                    self::MIN_SHORT_CODE_LENGTH,
                    self::MAX_SHORT_CODE_LENGTH
                )
            );
        }
        if ($maxSecretLength < 1 || $maxSecretLength > 100000) {
            throw new ConfigException('SECRET_MAX_LENGTH doit être compris entre 1 et 100000.');
        }
        if ($maxPasswordAttempts < 1 || $maxPasswordAttempts > 100) {
            throw new ConfigException('PASSWORD_MAX_ATTEMPTS doit être compris entre 1 et 100.');
        }
        if ($maxStoredSecrets < 1) {
            throw new ConfigException('MAX_STORED_SECRETS doit être un entier strictement positif.');
        }
        foreach ($trustedProxies as $range) {
            if (!ClientIp::isValidRange($range)) {
                throw new ConfigException(sprintf('TRUSTED_PROXIES : adresse ou plage CIDR invalide « %s ».', $range));
            }
        }
        if ($argon2Concurrency < 1 || $argon2Concurrency > 64) {
            throw new ConfigException('ARGON2_CONCURRENCY doit être compris entre 1 et 64.');
        }
        if ($databasePath === '') {
            throw new ConfigException('DATABASE_PATH ne peut pas être vide.');
        }
        if (!in_array($defaultLocale, self::SUPPORTED_LOCALES, true)) {
            throw new ConfigException('DEFAULT_LOCALE doit valoir : ' . implode(', ', self::SUPPORTED_LOCALES));
        }
    }

    /**
     * Construit la configuration à partir d'un tableau de type $_ENV / getenv()
     *
     * @param array<string, mixed> $env
     */
    public static function fromEnvironment(array $env): self
    {
        return new self(
            appSecret: self::string($env, 'APP_SECRET', ''),
            appUrl: self::string($env, 'APP_URL', ''),
            shortCodeLength: self::int($env, 'SHORT_CODE_LENGTH', 10),
            maxSecretLength: self::int($env, 'SECRET_MAX_LENGTH', 10000),
            maxPasswordAttempts: self::int($env, 'PASSWORD_MAX_ATTEMPTS', 5),
            databasePath: self::string($env, 'DATABASE_PATH', '/app/data/secret-share.sqlite'),
            defaultLocale: self::string($env, 'DEFAULT_LOCALE', 'fr'),
            sourceUrl: self::string($env, 'SOURCE_URL', self::DEFAULT_SOURCE_URL),
            maxStoredSecrets: self::int($env, 'MAX_STORED_SECRETS', 100000),
            argon2Concurrency: self::int($env, 'ARGON2_CONCURRENCY', 2),
            maxUnlimitedSecrets: self::int($env, 'MAX_UNLIMITED_SECRETS', 1000),
            trustedProxies: self::list($env, 'TRUSTED_PROXIES'),
            createLimitPerMinute: self::int($env, 'RATE_LIMIT_CREATE_PER_MINUTE', 5),
            createLimitPerHour: self::int($env, 'RATE_LIMIT_CREATE_PER_HOUR', 30),
            readLimitPerMinute: self::int($env, 'RATE_LIMIT_READ_PER_MINUTE', 30),
        );
    }

    /**
     * Indique si l'application est servie en HTTPS (cookies Secure, HSTS)
     */
    public function isHttps(): bool
    {
        return str_starts_with($this->appUrl, 'https://');
    }

    /**
     * @param array<string, mixed> $env
     */
    private static function string(array $env, string $name, string $default): string
    {
        $value = $env[$name] ?? null;
        if (!is_string($value) || trim($value) === '') {
            return $default;
        }
        return trim($value);
    }

    /**
     * Liste séparée par des virgules
     *
     * @param array<string, mixed> $env
     * @return list<string>
     */
    private static function list(array $env, string $name): array
    {
        return array_values(array_filter(array_map('trim', explode(',', self::string($env, $name, '')))));
    }

    /**
     * @param array<string, mixed> $env
     */
    private static function int(array $env, string $name, int $default): int
    {
        $value = self::string($env, $name, (string)$default);
        if (!preg_match('/^\d{1,9}$/', $value)) {
            throw new ConfigException(sprintf('%s doit être un entier positif.', $name));
        }
        return (int)$value;
    }
}
