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
