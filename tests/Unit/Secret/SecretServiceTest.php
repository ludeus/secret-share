<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Test\Unit\Secret;

use PDO;
use PHPUnit\Framework\TestCase;
use Random\RandomException;
use SecretShare\Crypto\PasswordRequiredException;
use SecretShare\Secret\Duration;
use SecretShare\Secret\InvalidPasswordException;
use SecretShare\Secret\QuotaExceededException;
use SecretShare\Secret\SecretNotFoundException;
use SecretShare\Secret\SecretService;
use SecretShare\Secret\ShareRequest;
use SecretShare\Storage\SecretRepository;
use SecretShare\Test\Factory;
use SecretShare\Test\FrozenClock;
use SodiumException;

final class SecretServiceTest extends TestCase
{
    private PDO $pdo;
    private FrozenClock $clock;
    private SecretService $service;

    protected function setUp(): void
    {
        $this->pdo = Factory::pdo();
        $this->clock = new FrozenClock();
        $this->service = Factory::service($this->pdo, $this->clock);
    }

    private static function request(
        string $secret = 'mon secret',
        Duration $duration = Duration::OneHour,
        bool $singleUse = true,
        ?string $password = null,
        bool $shortUrl = false,
    ): ShareRequest {
        return new ShareRequest($secret, $duration, $singleUse, $password, $shortUrl);
    }

    private function storedSecrets(): int
    {
        return (new SecretRepository($this->pdo))->count();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(): array
    {
        $statement = $this->pdo->query('SELECT * FROM secrets');
        $this->assertNotFalse($statement);
        /** @var list<array<string, mixed>> */
        return $statement->fetchAll();
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function testJwtSecretCanBeRevealedOnceWhenSingleUse(): void
    {
        $created = $this->service->create(self::request());

        $this->assertSame(2, substr_count($created->token, '.'));
        $this->assertSame($this->clock->now() + 3600, $created->expiresAt);

        $revealed = $this->service->reveal($created->token, null);
        $this->assertSame('mon secret', $revealed->secret);
        $this->assertTrue($revealed->destroyed);
        $this->assertSame(0, $this->storedSecrets());

        $this->expectException(SecretNotFoundException::class);
        $this->service->reveal($created->token, null);
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function testJwtDoesNotContainPlaintextAndServerDoesNotStoreCiphertext(): void
    {
        $created = $this->service->create(self::request('très-secret'));

        $this->assertStringNotContainsString('très-secret', $created->token);
        $this->assertNull($this->rows()[0]['payload']);
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function testMultiUseSecretCanBeRevealedSeveralTimes(): void
    {
        $created = $this->service->create(self::request(singleUse: false));

        $this->assertSame('mon secret', $this->service->reveal($created->token, null)->secret);
        $revealed = $this->service->reveal($created->token, null);
        $this->assertSame('mon secret', $revealed->secret);
        $this->assertFalse($revealed->destroyed);
        $this->assertSame($created->expiresAt, $revealed->expiresAt);
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function testExpiredSecretIsNotRevealedAndIsDeleted(): void
    {
        $created = $this->service->create(self::request(duration: Duration::TenMinutes, shortUrl: true));
        $this->clock->advance(601);

        try {
            $this->service->reveal($created->token, null);
            $this->fail('SecretNotFoundException attendue');
        } catch (SecretNotFoundException) {
            $this->assertSame(0, $this->storedSecrets());
        }
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function testExpiredJwtIsRejected(): void
    {
        $created = $this->service->create(self::request(duration: Duration::TenMinutes));
        $this->clock->advance(601);

        $this->expectException(SecretNotFoundException::class);
        $this->service->reveal($created->token, null);
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function testUnlimitedSecretHasNoExpiration(): void
    {
        $created = $this->service->create(self::request(duration: Duration::Unlimited, singleUse: false));
        $this->clock->advance(100 * 365 * 86400);

        $this->assertNull($created->expiresAt);
        $this->assertSame('mon secret', $this->service->reveal($created->token, null)->secret);
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function testShortCodeSecret(): void
    {
        $created = $this->service->create(self::request(shortUrl: true));

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{10}$/', $created->token);
        $this->assertSame('mon secret', $this->service->reveal($created->token, null)->secret);
        $this->assertSame(0, $this->storedSecrets());
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function testShortCodeLengthIsConfigurable(): void
    {
        $service = Factory::service(config: Factory::config(shortCodeLength: 24));

        $this->assertSame(24, strlen($service->create(self::request(shortUrl: true))->token));
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function testShortCodeStoresOnlyCiphertext(): void
    {
        $this->service->create(self::request('très-secret', shortUrl: true));

        $row = $this->rows()[0];
        $this->assertIsString($row['payload']);
        $this->assertStringNotContainsString('très-secret', $row['payload']);
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function testDatabaseDoesNotContainPublicIdentifiers(): void
    {
        $created = $this->service->create(self::request(shortUrl: true));

        $row = $this->rows()[0];
        $dump = json_encode([$row['lookup_hash'], $row['revoke_hash']]);
        $this->assertIsString($dump);
        $this->assertStringNotContainsString($created->token, $dump);
        $this->assertStringNotContainsString($created->revokeToken, $dump);
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function testDeletingServerRowMakesJwtUseless(): void
    {
        $created = $this->service->create(self::request(singleUse: false));
        $this->pdo->exec('DELETE FROM secrets');

        $this->expectException(SecretNotFoundException::class);
        $this->service->reveal($created->token, null);
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function testTokenFromAnotherInstanceIsRejected(): void
    {
        $other = Factory::service(config: Factory::config(appSecret: str_repeat('z', 40)));
        $created = $other->create(self::request());

        $this->expectException(SecretNotFoundException::class);
        $this->service->reveal($created->token, null);
    }

    /**
     * @throws SodiumException
     */
    public function testGarbageTokensAreRejected(): void
    {
        foreach (['', 'a', 'a.b.c', 'not a token', str_repeat('x', 5000), 'abcdefgh'] as $token) {
            try {
                $this->service->reveal($token, null);
                $this->fail('SecretNotFoundException attendue pour ' . $token);
            } catch (SecretNotFoundException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function testPasswordProtectedSecret(): void
    {
        $created = $this->service->create(self::request(password: 'motdepasse'));
        $this->assertTrue($created->passwordProtected);

        try {
            $this->service->reveal($created->token, null);
            $this->fail('PasswordRequiredException attendue');
        } catch (PasswordRequiredException) {
            $this->assertSame(1, $this->storedSecrets());
        }

        try {
            $this->service->reveal($created->token, 'mauvais');
            $this->fail('InvalidPasswordException attendue');
        } catch (InvalidPasswordException $e) {
            $this->assertSame(2, $e->remainingAttempts);
        }

        $this->assertSame('mon secret', $this->service->reveal($created->token, 'motdepasse')->secret);
        $this->assertSame(0, $this->storedSecrets());
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function testTooManyWrongPasswordsDestroyTheSecret(): void
    {
        $created = $this->service->create(self::request(password: 'motdepasse', shortUrl: true));

        foreach ([2, 1, 0] as $expectedRemaining) {
            try {
                $this->service->reveal($created->token, 'mauvais');
                $this->fail('InvalidPasswordException attendue');
            } catch (InvalidPasswordException $e) {
                $this->assertSame($expectedRemaining, $e->remainingAttempts);
            }
        }
        $this->assertSame(0, $this->storedSecrets());

        $this->expectException(SecretNotFoundException::class);
        $this->service->reveal($created->token, 'motdepasse');
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function testExhaustedAttemptsAreRefusedBeforeCheckingPassword(): void
    {
        $created = $this->service->create(self::request(singleUse: false, password: 'motdepasse'));
        // simule des essais concurrents ayant déjà réservé toutes les tentatives
        $this->pdo->exec('UPDATE secrets SET failed_attempts = 3');

        try {
            $this->service->reveal($created->token, 'motdepasse');
            $this->fail('InvalidPasswordException attendue');
        } catch (InvalidPasswordException $e) {
            $this->assertSame(0, $e->remainingAttempts);
        }
        $this->assertSame(0, $this->storedSecrets());
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function testSuccessfulPasswordReleasesTheReservedAttempt(): void
    {
        $created = $this->service->create(self::request(singleUse: false, password: 'motdepasse'));

        for ($i = 0; $i < 5; $i++) {
            $this->service->reveal($created->token, 'motdepasse');
        }

        $this->assertSame(0, $this->rows()[0]['failed_attempts']);
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function testStorageQuota(): void
    {
        $service = Factory::service(config: Factory::config(maxStoredSecrets: 2));
        $service->create(self::request());
        $service->create(self::request());

        try {
            $service->create(self::request());
            $this->fail('QuotaExceededException attendue');
        } catch (QuotaExceededException $e) {
            $this->assertSame('error.storage_full', $e->messageKey);
        }
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function testUnlimitedSecretsHaveTheirOwnQuota(): void
    {
        $service = Factory::service(config: Factory::config(maxUnlimitedSecrets: 1));
        $service->create(self::request(duration: Duration::Unlimited));

        try {
            $service->create(self::request(duration: Duration::Unlimited));
            $this->fail('QuotaExceededException attendue');
        } catch (QuotaExceededException $e) {
            $this->assertSame('error.unlimited_quota', $e->messageKey);
        }
        // les secrets avec une durée restent possibles
        $this->assertNotNull($service->create(self::request(duration: Duration::OneDay))->expiresAt);
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function testRevoke(): void
    {
        $created = $this->service->create(self::request(singleUse: false));

        $this->assertFalse($this->service->revoke('mauvais-jeton'));
        $this->assertFalse($this->service->revoke(''));
        $this->assertTrue($this->service->revoke($created->revokeToken));
        $this->assertFalse($this->service->revoke($created->revokeToken));

        $this->expectException(SecretNotFoundException::class);
        $this->service->reveal($created->token, null);
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function testLargeUnicodeSecret(): void
    {
        $secret = str_repeat("ligne é 🔐\n", 90);
        $created = $this->service->create(self::request($secret));

        $this->assertSame($secret, $this->service->reveal($created->token, null)->secret);
    }
}
