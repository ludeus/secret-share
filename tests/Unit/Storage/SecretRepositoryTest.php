<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Test\Unit\Storage;

use PHPUnit\Framework\TestCase;
use Random\RandomException;
use SecretShare\Storage\Database;
use SecretShare\Storage\SecretRecord;
use SecretShare\Storage\SecretRepository;
use SecretShare\Test\Factory;

final class SecretRepositoryTest extends TestCase
{
    private SecretRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new SecretRepository(Factory::pdo());
    }

    /**
     * @throws RandomException
     */
    private static function record(string $id = 'id', ?int $expiresAt = 2000, ?string $payload = null): SecretRecord
    {
        return new SecretRecord('lookup-' . $id, random_bytes(32) . "\0", $payload, true, $expiresAt, 'revoke-' . $id);
    }

    /**
     * @throws RandomException
     */
    public function testInsertAndFind(): void
    {
        $record = self::record(payload: "\0\xffbinaire");

        $this->assertTrue($this->repository->insert($record));
        $this->assertEquals($record, $this->repository->find('lookup-id'));
        $this->assertNull($this->repository->find('inconnu'));
    }

    /**
     * @throws RandomException
     */
    public function testDuplicateInsertReturnsFalse(): void
    {
        $this->assertTrue($this->repository->insert(self::record()));
        $this->assertFalse($this->repository->insert(self::record()));
    }

    /**
     * @throws RandomException
     */
    public function testDeleteOnlySucceedsOnce(): void
    {
        $this->repository->insert(self::record());

        $this->assertTrue($this->repository->delete('lookup-id'));
        $this->assertFalse($this->repository->delete('lookup-id'));
    }

    /**
     * @throws RandomException
     */
    public function testDeleteByRevokeHash(): void
    {
        $this->repository->insert(self::record());

        $this->assertTrue($this->repository->deleteByRevokeHash('revoke-id'));
        $this->assertSame(0, $this->repository->count());
    }

    /**
     * @throws RandomException
     */
    public function testReserveAttemptStopsAtLimit(): void
    {
        $this->repository->insert(self::record());

        $this->assertSame(1, $this->repository->reserveAttempt('lookup-id', 2));
        $this->assertSame(2, $this->repository->reserveAttempt('lookup-id', 2));
        $this->assertNull($this->repository->reserveAttempt('lookup-id', 2));
        $this->assertSame(2, $this->repository->find('lookup-id')?->failedAttempts);
        $this->assertNull($this->repository->reserveAttempt('inconnu', 2));
    }

    /**
     * @throws RandomException
     */
    public function testReleaseAttempt(): void
    {
        $this->repository->insert(self::record());
        $this->repository->reserveAttempt('lookup-id', 5);

        $this->repository->releaseAttempt('lookup-id');
        $this->repository->releaseAttempt('lookup-id');

        $this->assertSame(0, $this->repository->find('lookup-id')?->failedAttempts);
    }

    /**
     * @throws RandomException
     */
    public function testPurgeExpired(): void
    {
        $this->repository->insert(self::record('a', 1000));
        $this->repository->insert(self::record('b'));
        $this->repository->insert(self::record('c', null));

        $this->assertSame(1, $this->repository->purgeExpired(1000));
        $this->assertNull($this->repository->find('lookup-a'));
        $this->assertSame(2, $this->repository->count());
    }

    /**
     * @throws RandomException
     */
    public function testIsExpired(): void
    {
        $this->assertTrue(self::record(expiresAt: 1000)->isExpired(1000));
        $this->assertFalse(self::record(expiresAt: 1000)->isExpired(999));
        $this->assertFalse(self::record(expiresAt: null)->isExpired(PHP_INT_MAX));
    }

    /**
     * @throws RandomException
     */
    public function testDatabaseFileIsCreatedWithRestrictedPermissions(): void
    {
        $directory = sys_get_temp_dir() . '/secret-share-test-' . bin2hex(random_bytes(4));
        $path = $directory . '/sub/db.sqlite';
        try {
            $pdo = Database::connect($path);
            Database::migrate($pdo);

            $this->assertFileExists($path);
            $this->assertSame(0600, fileperms($path) & 0777);
            $this->assertSame(0700, fileperms(dirname($path)) & 0777);
            $statement = $pdo->query('PRAGMA secure_delete');
            $this->assertNotFalse($statement);
            $this->assertSame(1, $statement->fetchColumn());
        } finally {
            @unlink($path);
            @rmdir(dirname($path));
            @rmdir($directory);
        }
    }
}
