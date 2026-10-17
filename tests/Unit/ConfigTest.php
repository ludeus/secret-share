<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Test\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SecretShare\Config;
use SecretShare\ConfigException;

final class ConfigTest extends TestCase
{
    private const array VALID_ENV = [
        'APP_SECRET' => 'abcdefghijklmnopqrstuvwxyz0123456789',
        'APP_URL' => 'https://secret.example.org',
    ];

    public function testFromEnvironmentAppliesDefaults(): void
    {
        $config = Config::fromEnvironment(self::VALID_ENV);

        $this->assertSame(10, $config->shortCodeLength);
        $this->assertSame(10000, $config->maxSecretLength);
        $this->assertSame(5, $config->maxPasswordAttempts);
        $this->assertSame('fr', $config->defaultLocale);
        $this->assertTrue($config->isHttps());
        $this->assertSame([], $config->trustedProxies);
        $this->assertSame(5, $config->createLimitPerMinute);
        $this->assertSame(30, $config->createLimitPerHour);
        $this->assertSame(30, $config->readLimitPerMinute);
        $this->assertSame(1000, $config->maxUnlimitedSecrets);
    }

    public function testFromEnvironmentReadsValues(): void
    {
        $config = Config::fromEnvironment(
            self::VALID_ENV + [
                'SHORT_CODE_LENGTH' => '16',
                'SECRET_MAX_LENGTH' => '500',
                'PASSWORD_MAX_ATTEMPTS' => '3',
                'DATABASE_PATH' => '/tmp/x.sqlite',
                'DEFAULT_LOCALE' => 'en',
                'SOURCE_URL' => 'https://example.org/src',
                'MAX_STORED_SECRETS' => '42',
                'ARGON2_CONCURRENCY' => '4',
                'MAX_UNLIMITED_SECRETS' => '7',
                'TRUSTED_PROXIES' => ' 127.0.0.1, 172.16.0.0/12 ,::1 ',
                'RATE_LIMIT_CREATE_PER_MINUTE' => '0',
                'RATE_LIMIT_CREATE_PER_HOUR' => '12',
                'RATE_LIMIT_READ_PER_MINUTE' => '9',
            ]
        );

        $this->assertSame(16, $config->shortCodeLength);
        $this->assertSame(500, $config->maxSecretLength);
        $this->assertSame(3, $config->maxPasswordAttempts);
        $this->assertSame('/tmp/x.sqlite', $config->databasePath);
        $this->assertSame('en', $config->defaultLocale);
        $this->assertSame('https://example.org/src', $config->sourceUrl);
        $this->assertSame(42, $config->maxStoredSecrets);
        $this->assertSame(4, $config->argon2Concurrency);
        $this->assertSame(7, $config->maxUnlimitedSecrets);
        $this->assertSame(['127.0.0.1', '172.16.0.0/12', '::1'], $config->trustedProxies);
        $this->assertSame(0, $config->createLimitPerMinute);
        $this->assertSame(12, $config->createLimitPerHour);
        $this->assertSame(9, $config->readLimitPerMinute);
    }

    public function testHttpUrlIsNotHttps(): void
    {
        $config = Config::fromEnvironment(['APP_URL' => 'http://localhost:8080'] + self::VALID_ENV);

        $this->assertFalse($config->isHttps());
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function invalidEnvironments(): iterable
    {
        yield 'secret absent' => [['APP_SECRET' => ''] + self::VALID_ENV];
        yield 'secret trop court' => [['APP_SECRET' => 'short'] + self::VALID_ENV];
        yield 'url absente' => [['APP_URL' => ''] + self::VALID_ENV];
        yield 'url avec slash final' => [['APP_URL' => 'https://example.org/'] + self::VALID_ENV];
        yield 'url sans schéma' => [['APP_URL' => 'example.org'] + self::VALID_ENV];
        yield 'url javascript' => [['APP_URL' => 'javascript:alert(1)'] + self::VALID_ENV];
        yield 'code court trop court' => [['SHORT_CODE_LENGTH' => '4'] + self::VALID_ENV];
        yield 'code court trop long' => [['SHORT_CODE_LENGTH' => '65'] + self::VALID_ENV];
        yield 'code court non numérique' => [['SHORT_CODE_LENGTH' => '10abc'] + self::VALID_ENV];
        yield 'longueur max nulle' => [['SECRET_MAX_LENGTH' => '0'] + self::VALID_ENV];
        yield 'tentatives nulles' => [['PASSWORD_MAX_ATTEMPTS' => '0'] + self::VALID_ENV];
        yield 'quota nul' => [['MAX_STORED_SECRETS' => '0'] + self::VALID_ENV];
        yield 'concurrence argon2 nulle' => [['ARGON2_CONCURRENCY' => '0'] + self::VALID_ENV];
        yield 'proxy invalide' => [['TRUSTED_PROXIES' => '10.0.0.0/33'] + self::VALID_ENV];
        yield 'proxy non ip' => [['TRUSTED_PROXIES' => 'localhost'] + self::VALID_ENV];
        yield 'langue inconnue' => [['DEFAULT_LOCALE' => 'de'] + self::VALID_ENV];
    }

    /**
     * @param array<string, string> $env
     */
    #[DataProvider('invalidEnvironments')]
    public function testInvalidEnvironmentIsRejected(array $env): void
    {
        $this->expectException(ConfigException::class);

        Config::fromEnvironment($env);
    }
}
