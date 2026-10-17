<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Test\Unit\View;

use PHPUnit\Framework\TestCase;
use SecretShare\View\Translator;
use SecretShare\View\View;

final class ViewTest extends TestCase
{
    public function testEscape(): void
    {
        $view = new View('/nonexistent', new Translator('fr', []));

        $this->assertSame('&lt;script&gt;&quot;&apos;&amp;', $view->e('<script>"\'&'));
        $this->assertSame('42', $view->e(42));
        $this->assertSame('', $view->e(null));
    }

    public function testTranslationsAreEscaped(): void
    {
        $view = new View('/nonexistent', new Translator('fr', ['key' => '<b>{x}</b>']));

        $this->assertSame('&lt;b&gt;&lt;i&gt;&lt;/b&gt;', $view->t('key', ['x' => '<i>']));
    }

    public function testTime(): void
    {
        $view = new View('/nonexistent', new Translator('fr', []));

        $this->assertSame(
            '<time datetime="2027-01-15T08:00:00Z" data-local-time>2027-01-15 08:00 UTC</time>',
            $view->time((int)gmmktime(8, 0, 0, 1, 15, 2027))
        );
    }

    public function testMissingTemplateThrows(): void
    {
        $view = new View('/nonexistent', new Translator('fr', []));

        $this->expectException(\RuntimeException::class);
        $view->render('missing');
    }
}
