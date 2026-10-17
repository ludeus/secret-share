<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Test\Unit\Crypto;

use PHPUnit\Framework\TestCase;
use Random\RandomException;
use RuntimeException;
use SecretShare\Crypto\Semaphore;

final class SemaphoreTest extends TestCase
{
    private string $directory;

    /**
     * @throws RandomException
     */
    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/secret-share-sem-' . bin2hex(random_bytes(4));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->directory . '/*') ?: []);
        rmdir($this->directory);
    }

    public function testRunReturnsOperationResult(): void
    {
        $this->assertSame(42, (new Semaphore($this->directory, 1))->run(fn (): int => 42));
    }

    public function testTimesOutWhenAllSlotsAreTaken(): void
    {
        // occupe l'unique emplacement depuis un autre descripteur, comme le ferait un autre processus
        $handle = fopen($this->directory . '/secret-share-0.lock', 'c');
        $this->assertNotFalse($handle);
        $this->assertTrue(flock($handle, LOCK_EX));

        try {
            $this->expectException(RuntimeException::class);
            (new Semaphore($this->directory, 1, 50))->run(fn (): int => 42);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function testUsesAnotherFreeSlot(): void
    {
        $handle = fopen($this->directory . '/secret-share-0.lock', 'c');
        $this->assertNotFalse($handle);
        flock($handle, LOCK_EX);

        $this->assertSame('ok', (new Semaphore($this->directory, 2, 50))->run(fn (): string => 'ok'));

        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
