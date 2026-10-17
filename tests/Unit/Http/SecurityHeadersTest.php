<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Test\Unit\Http;

use PHPUnit\Framework\TestCase;
use SecretShare\Http\Response;
use SecretShare\Http\SecurityHeaders;
use SecretShare\Test\Factory;

final class SecurityHeadersTest extends TestCase
{
    public function testHeaders(): void
    {
        $headers = SecurityHeaders::apply(new Response(), Factory::config())->headers;

        $this->assertStringContainsString("script-src 'self'", $headers['Content-Security-Policy']);
        $this->assertStringContainsString("frame-ancestors 'none'", $headers['Content-Security-Policy']);
        $this->assertStringNotContainsString('unsafe-inline', $headers['Content-Security-Policy']);
        $this->assertStringContainsString('no-store', $headers['Cache-Control']);
        $this->assertSame('no-referrer', $headers['Referrer-Policy']);
        $this->assertSame('nosniff', $headers['X-Content-Type-Options']);
        $this->assertArrayHasKey('Strict-Transport-Security', $headers);
    }

    public function testNoHstsOverHttp(): void
    {
        $config = Factory::config(appUrl: 'http://localhost:8080');

        $headers = SecurityHeaders::apply(new Response(), $config)->headers;

        $this->assertArrayNotHasKey('Strict-Transport-Security', $headers);
    }
}
