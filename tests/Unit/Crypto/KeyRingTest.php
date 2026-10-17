<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Test\Unit\Crypto;

use PHPUnit\Framework\TestCase;
use SecretShare\Crypto\KeyRing;

final class KeyRingTest extends TestCase
{
    public function testSubKeysAreDistinctAndDeterministic(): void
    {
        $keyRing = new KeyRing(str_repeat('a', 32));
        $same = new KeyRing(str_repeat('a', 32));
        $other = new KeyRing(str_repeat('b', 32));

        $this->assertSame(32, strlen($keyRing->jwtKey()));
        $this->assertSame($keyRing->jwtKey(), $same->jwtKey());
        $this->assertNotSame($keyRing->jwtKey(), $other->jwtKey());
        $this->assertNotSame($keyRing->jwtKey(), $keyRing->dataKey($keyRing->jwtKey(), 'x'));
    }

    public function testLookupHashDependsOnNamespaceAndId(): void
    {
        $keyRing = new KeyRing(str_repeat('a', 32));

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $keyRing->lookupHash('t', 'id'));
        $this->assertNotSame($keyRing->lookupHash('t', 'id'), $keyRing->lookupHash('c', 'id'));
        $this->assertNotSame($keyRing->lookupHash('t', 'id'), $keyRing->lookupHash('t', 'id2'));
        $other = new KeyRing(str_repeat('b', 32));
        $this->assertNotSame($keyRing->lookupHash('t', 'id'), $other->lookupHash('t', 'id'));
    }

    public function testDataKeyCombinesServerKeyPublicIdAndAppSecret(): void
    {
        $keyRing = new KeyRing(str_repeat('a', 32));
        $serverKey = random_bytes(32);
        $reference = $keyRing->dataKey($serverKey, 'id');

        $this->assertSame(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES, strlen($reference));
        $this->assertSame($reference, $keyRing->dataKey($serverKey, 'id'));
        $this->assertNotSame($reference, $keyRing->dataKey(random_bytes(32), 'id'));
        $this->assertNotSame($reference, $keyRing->dataKey($serverKey, 'id2'));
        $this->assertNotSame($reference, (new KeyRing(str_repeat('b', 32)))->dataKey($serverKey, 'id'));
    }
}
