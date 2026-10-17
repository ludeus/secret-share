<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Test\Unit\Crypto;

use PHPUnit\Framework\TestCase;
use SecretShare\Crypto\DecryptionException;
use SecretShare\Crypto\Envelope;
use SecretShare\Crypto\PasswordRequiredException;
use SecretShare\Crypto\WrongPasswordException;
use SecretShare\Test\Factory;

final class EnvelopeTest extends TestCase
{
    private Envelope $envelope;
    private string $key;

    protected function setUp(): void
    {
        $this->envelope = Factory::envelope();
        $this->key = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES);
    }

    public function testRoundTripWithoutPassword(): void
    {
        $blob = $this->envelope->seal("ligne 1\nligne 2 é 🔑", null, $this->key, 'ctx');

        $this->assertStringNotContainsString('ligne', $blob);
        $this->assertSame("ligne 1\nligne 2 é 🔑", $this->envelope->open($blob, null, $this->key, 'ctx'));
    }

    public function testEmptyPasswordMeansNoPassword(): void
    {
        $blob = $this->envelope->seal('secret', '', $this->key, 'ctx');

        $this->assertSame('secret', $this->envelope->open($blob, null, $this->key, 'ctx'));
    }

    public function testRoundTripWithPassword(): void
    {
        $blob = $this->envelope->seal('secret', 'motdepasse', $this->key, 'ctx');

        $this->assertSame('secret', $this->envelope->open($blob, 'motdepasse', $this->key, 'ctx'));
    }

    public function testMissingPasswordIsReported(): void
    {
        $blob = $this->envelope->seal('secret', 'motdepasse', $this->key, 'ctx');

        $this->expectException(PasswordRequiredException::class);
        $this->envelope->open($blob, null, $this->key, 'ctx');
    }

    public function testWrongPasswordIsReported(): void
    {
        $blob = $this->envelope->seal('secret', 'motdepasse', $this->key, 'ctx');

        $this->expectException(WrongPasswordException::class);
        $this->envelope->open($blob, 'autre', $this->key, 'ctx');
    }

    public function testWrongKeyFails(): void
    {
        $blob = $this->envelope->seal('secret', null, $this->key, 'ctx');

        $this->expectException(DecryptionException::class);
        $this->envelope->open($blob, null, random_bytes(32), 'ctx');
    }

    public function testWrongContextFails(): void
    {
        $blob = $this->envelope->seal('secret', null, $this->key, 'ctx');

        $this->expectException(DecryptionException::class);
        $this->envelope->open($blob, null, $this->key, 'autre-contexte');
    }

    public function testTamperedBlobFails(): void
    {
        $blob = $this->envelope->seal('secret', null, $this->key, 'ctx');
        $blob[30] = $blob[30] === 'a' ? 'b' : 'a';

        $this->expectException(DecryptionException::class);
        $this->envelope->open($blob, null, $this->key, 'ctx');
    }

    public function testTruncatedBlobFails(): void
    {
        $this->expectException(DecryptionException::class);
        $this->envelope->open('court', null, $this->key, 'ctx');
    }

    public function testPaddingHidesExactLength(): void
    {
        $short = $this->envelope->seal('a', null, $this->key, 'ctx');
        $longer = $this->envelope->seal(str_repeat('a', 50), null, $this->key, 'ctx');

        $this->assertSame(strlen($short), strlen($longer));
    }

    public function testSameSecretGivesDifferentCiphertexts(): void
    {
        $this->assertNotSame(
            $this->envelope->seal('secret', null, $this->key, 'ctx'),
            $this->envelope->seal('secret', null, $this->key, 'ctx')
        );
    }
}
