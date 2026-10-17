<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Test\Unit\View;

use PHPUnit\Framework\TestCase;
use SecretShare\View\QrCodeRenderer;

final class QrCodeRendererTest extends TestCase
{
    public function testRendersSvgDataUri(): void
    {
        $uri = new QrCodeRenderer()->dataUri('https://secret.example.org/s#abcdef');

        $this->assertIsString($uri);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $uri);
        $svg = base64_decode(substr($uri, strlen('data:image/svg+xml;base64,')), true);
        $this->assertIsString($svg);
        $this->assertStringStartsWith('<svg xmlns="http://www.w3.org/2000/svg"', $svg);
        $this->assertMatchesRegularExpression('/d="(M\d+ \d+h\d+v1h-\d+z)+"/', $svg);
    }

    public function testTooLongContentReturnsNull(): void
    {
        $this->assertNull(new QrCodeRenderer()->dataUri(str_repeat('a', 5000)));
    }
}
