<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Test\Unit\Storage;

use PDO;
use PHPUnit\Framework\TestCase;
use SecretShare\Storage\Maintenance;
use SecretShare\Storage\RateLimitRepository;
use SecretShare\Storage\SecretRecord;
use SecretShare\Storage\SecretRepository;
use SecretShare\Test\Factory;

final class MaintenanceTest extends TestCase
{
    private PDO $pdo;
    private SecretRepository $secrets;
    private RateLimitRepository $rateLimits;
    private Maintenance $maintenance;

    protected function setUp(): void
    {
        $this->pdo = Factory::pdo();
        $this->secrets = new SecretRepository($this->pdo);
        $this->rateLimits = new RateLimitRepository($this->pdo);
        $this->maintenance = new Maintenance($this->pdo, $this->secrets, $this->rateLimits, 60);
    }

    private function insertSecret(string $id, ?int $expiresAt): void
    {
        $this->secrets->insert(new SecretRecord($id, 'key', null, true, $expiresAt, 'revoke-' . $id));
    }

    private function rateLimitRows(): int
    {
        $statement = $this->pdo->query('SELECT COUNT(*) FROM rate_limits');
        $this->assertNotFalse($statement);
        $count = $statement->fetchColumn();
        $this->assertIsInt($count);
        return $count;
    }

    public function testPurgesExpiredData(): void
    {
        $this->insertSecret('a', 1000);
        $this->insertSecret('b', 5000);
        $this->insertSecret('c', null);
        $this->rateLimits->hit('old', 1000);
        $this->rateLimits->hit('current', 5000);

        $this->assertTrue($this->maintenance->runIfDue(1000));

        $this->assertSame(2, $this->secrets->count());
        $this->assertSame(1, $this->rateLimitRows());
    }

    public function testRunsAtMostOncePerInterval(): void
    {
        $this->assertTrue($this->maintenance->runIfDue(1000));
        $this->insertSecret('a', 1000);

        $this->assertFalse($this->maintenance->runIfDue(1059));
        $this->assertSame(1, $this->secrets->count());

        $this->assertTrue($this->maintenance->runIfDue(1060));
        $this->assertSame(0, $this->secrets->count());
    }
}
