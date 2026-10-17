<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Test\Unit\Http;

use PDO;
use PHPUnit\Framework\TestCase;
use SecretShare\Crypto\KeyRing;
use SecretShare\Http\RateLimiter;
use SecretShare\Storage\RateLimitRepository;
use SecretShare\Test\Factory;
use SecretShare\Test\FrozenClock;

final class RateLimiterTest extends TestCase
{
    private PDO $pdo;
    private FrozenClock $clock;
    private RateLimiter $limiter;

    protected function setUp(): void
    {
        $this->pdo = Factory::pdo();
        // heure pile, pour que les fenêtres soient déterministes
        $this->clock = new FrozenClock(1_800_000_000 - 1_800_000_000 % 3600);
        $this->limiter = new RateLimiter(
            new RateLimitRepository($this->pdo),
            new KeyRing(Factory::APP_SECRET),
            $this->clock
        );
    }

    public function testLimitPerWindow(): void
    {
        $this->assertNull($this->limiter->consume('create', '1.2.3.4', 2, 60));
        $this->assertNull($this->limiter->consume('create', '1.2.3.4', 2, 60));
        $this->assertSame(60, $this->limiter->consume('create', '1.2.3.4', 2, 60));

        $this->clock->advance(45);
        $this->assertSame(15, $this->limiter->consume('create', '1.2.3.4', 2, 60));

        $this->clock->advance(15);
        $this->assertNull($this->limiter->consume('create', '1.2.3.4', 2, 60));
    }

    public function testClientsAndScopesAreIndependent(): void
    {
        $this->limiter->consume('create', '1.2.3.4', 1, 60);

        $this->assertNull($this->limiter->consume('create', '5.6.7.8', 1, 60));
        $this->assertNull($this->limiter->consume('read', '1.2.3.4', 1, 60));
        $this->assertNotNull($this->limiter->consume('create', '1.2.3.4', 1, 60));
    }

    public function testZeroDisablesTheLimit(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->assertNull($this->limiter->consume('create', '1.2.3.4', 0, 60));
        }
    }

    public function testAddressIsNeverStored(): void
    {
        $this->limiter->consume('create', '203.0.113.77', 5, 60);

        $statement = $this->pdo->query('SELECT * FROM rate_limits');
        $this->assertNotFalse($statement);
        $dump = (string)json_encode($statement->fetchAll());
        $this->assertStringNotContainsString('203.0.113.77', $dump);
        $this->assertMatchesRegularExpression('/"bucket":"[0-9a-f]{64}"/', $dump);
    }
}
