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
use SecretShare\Crypto\JwtCodec;
use SecretShare\Crypto\KeyRing;
use SecretShare\Test\FrozenClock;

final class JwtCodecTest extends TestCase
{
    private FrozenClock $clock;
    private JwtCodec $codec;

    protected function setUp(): void
    {
        $this->clock = new FrozenClock();
        $this->codec = new JwtCodec(new KeyRing(str_repeat('k', 32)), $this->clock);
    }

    public function testRoundTrip(): void
    {
        $jwt = $this->codec->encode(['jti' => 'abc', 'exp' => $this->clock->now() + 60]);

        $this->assertSame(2, substr_count($jwt, '.'));
        $this->assertSame('abc', $this->codec->decode($jwt)['jti']);
    }

    public function testExpiredTokenIsRejected(): void
    {
        $jwt = $this->codec->encode(['jti' => 'abc', 'exp' => $this->clock->now() + 60]);
        $this->clock->advance(61);

        $this->expectException(DecryptionException::class);
        $this->codec->decode($jwt);
    }

    public function testTokenSignedWithAnotherKeyIsRejected(): void
    {
        $jwt = (new JwtCodec(new KeyRing(str_repeat('x', 32)), $this->clock))->encode(['jti' => 'abc']);

        $this->expectException(DecryptionException::class);
        $this->codec->decode($jwt);
    }

    public function testTamperedPayloadIsRejected(): void
    {
        [$header, , $signature] = explode('.', $this->codec->encode(['jti' => 'abc']));
        $payload = rtrim(strtr(base64_encode('{"jti":"other"}'), '+/', '-_'), '=');

        $this->expectException(DecryptionException::class);
        $this->codec->decode($header . '.' . $payload . '.' . $signature);
    }

    public function testUnsignedTokenIsRejected(): void
    {
        $header = rtrim(strtr(base64_encode('{"alg":"none","typ":"JWT"}'), '+/', '-_'), '=');
        $payload = rtrim(strtr(base64_encode('{"jti":"abc"}'), '+/', '-_'), '=');

        $this->expectException(DecryptionException::class);
        $this->codec->decode($header . '.' . $payload . '.');
    }
}
