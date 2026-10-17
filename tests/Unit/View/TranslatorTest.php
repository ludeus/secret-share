<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Test\Unit\View;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SecretShare\View\Translator;

final class TranslatorTest extends TestCase
{
    /**
     * @return iterable<string, array{?string, string}>
     */
    public static function acceptLanguages(): iterable
    {
        yield 'absent' => [null, 'fr'];
        yield 'vide' => ['', 'fr'];
        yield 'anglais' => ['en-US,en;q=0.9', 'en'];
        yield 'français' => ['fr-FR,fr;q=0.9,en;q=0.8', 'fr'];
        yield 'priorité' => ['de;q=1,en;q=0.5,fr;q=0.8', 'fr'];
        yield 'non supportée' => ['de-DE,de', 'fr'];
        yield 'invalide' => ['***', 'fr'];
    }

    #[DataProvider('acceptLanguages')]
    public function testNegotiate(?string $header, string $expected): void
    {
        $this->assertSame($expected, Translator::negotiate($header, ['fr', 'en'], 'fr'));
    }

    public function testTranslateWithParameters(): void
    {
        $translator = new Translator('fr', ['hello' => 'Bonjour {name} ({count})']);

        $this->assertSame('Bonjour Alice (2)', $translator->t('hello', ['name' => 'Alice', 'count' => 2]));
        $this->assertSame('missing.key', $translator->t('missing.key'));
    }

    public function testForRequestLoadsLanguageFile(): void
    {
        $translator = Translator::forRequest('en', 'fr', dirname(__DIR__, 3) . '/lang');

        $this->assertSame('en', $translator->locale);
        $this->assertSame('Yes', $translator->t('common.yes'));
    }

    public function testLanguagesHaveSameKeys(): void
    {
        $fr = require dirname(__DIR__, 3) . '/lang/fr.php';
        $en = require dirname(__DIR__, 3) . '/lang/en.php';
        $this->assertIsArray($fr);
        $this->assertIsArray($en);

        $this->assertSame(array_keys($fr), array_keys($en));
    }
}
