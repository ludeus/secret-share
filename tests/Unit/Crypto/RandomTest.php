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
use SecretShare\Crypto\Random;

final class RandomTest extends TestCase
{
    public function testTokenIsBase64UrlWithoutPadding(): void
    {
        $token = Random::token(32);

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', $token);
        $this->assertNotSame($token, Random::token(32));
    }

    public function testShortCodeUsesUnambiguousAlphabet(): void
    {
        $code = Random::shortCode(64);

        $this->assertSame(64, strlen($code));
        $this->assertDoesNotMatchRegularExpression('/[0O1lI]/', $code);
        $this->assertTrue(Random::isShortCode($code, 8, 64));
    }

    public function testIsShortCode(): void
    {
        $this->assertTrue(Random::isShortCode('abcdefgh', 8, 64));
        $this->assertFalse(Random::isShortCode('abcdefg', 8, 64));
        $this->assertFalse(Random::isShortCode('abcdefgh0', 8, 64));
        $this->assertFalse(Random::isShortCode('abc.def.gh', 8, 64));
        $this->assertFalse(Random::isShortCode(str_repeat('a', 65), 8, 64));
    }

    public function testBase64UrlRoundTrip(): void
    {
        $data = random_bytes(100);

        $this->assertSame($data, Random::base64UrlDecode(Random::base64UrlEncode($data)));
    }

    public function testInvalidBase64UrlIsRejected(): void
    {
        $this->expectException(DecryptionException::class);
        Random::base64UrlDecode('***');
    }
}
