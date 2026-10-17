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
use Random\RandomException;
use SecretShare\View\LanguageCatalog;
use SecretShare\View\TranslationChecker;

final class LanguageCatalogTest extends TestCase
{
    private string $directory;

    /**
     * @throws RandomException
     */
    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/secret-share-lang-' . bin2hex(random_bytes(4));
        mkdir($this->directory);
        $this->write(
            'en',
            [
                'lang.name' => 'English',
                'lang.dir' => 'ltr',
                'lang.flag' => '🇬🇧',
                'hello' => 'Hello {name}',
                'bye' => 'Bye',
            ]
        );
        $this->write('xx', ['lang.name' => 'Xish', 'lang.dir' => 'rtl', 'hello' => 'Xello {name}']);
        file_put_contents($this->directory . '/README.txt', 'ignoré');
        file_put_contents($this->directory . '/Invalid.php', '<?php return [];');
    }

    protected function tearDown(): void
    {
        array_map(unlink(...), glob($this->directory . '/*') ?: []);
        rmdir($this->directory);
    }

    /**
     * @param array<string, string> $messages
     */
    private function write(string $code, array $messages): void
    {
        file_put_contents($this->directory . '/' . $code . '.php', '<?php return ' . var_export($messages, true) . ';');
    }

    public function testLanguagesAreDiscoveredFromFiles(): void
    {
        $catalog = new LanguageCatalog($this->directory);

        $this->assertSame(['en' => 'English', 'xx' => 'Xish'], $catalog->languages());
        $this->assertSame(['en' => '🇬🇧 English', 'xx' => 'Xish'], $catalog->labels());
        $this->assertTrue($catalog->has('xx'));
        $this->assertFalse($catalog->has('zz'));
        $this->assertFalse($catalog->has('../en'));
    }

    public function testMissingTextsFallBackToReference(): void
    {
        $translator = new LanguageCatalog($this->directory)->translator('xx');

        $this->assertSame('Xello Ada', $translator->t('hello', ['name' => 'Ada']));
        $this->assertSame('Bye', $translator->t('bye'));
        $this->assertSame('rtl', $translator->direction());
    }

    public function testCheckerReportsProblems(): void
    {
        $this->write('xx', ['lang.name' => 'Xish', 'lang.dir' => 'up', 'hello' => 'Xello', 'extra' => 'x']);
        $problems = new TranslationChecker(new LanguageCatalog($this->directory))->check('xx');

        $this->assertSame(
            [
                'clé obligatoire absente : lang.flag',
                'lang.dir doit valoir « ltr » ou « rtl »',
                'paramètres différents pour hello : attendu name',
                'texte manquant : bye',
                'texte inconnu de la langue de référence : extra',
            ],
            $problems
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function projectLanguages(): iterable
    {
        foreach (array_keys(new LanguageCatalog(dirname(__DIR__, 3) . '/lang')->languages()) as $code) {
            yield $code => [$code];
        }
    }

    #[DataProvider('projectLanguages')]
    public function testProjectLanguagesAreComplete(string $code): void
    {
        $catalog = new LanguageCatalog(dirname(__DIR__, 3) . '/lang');

        $this->assertSame([], new TranslationChecker($catalog)->check($code));
    }

    public function testProjectProvidesTheMostSpokenLanguages(): void
    {
        $languages = new LanguageCatalog(dirname(__DIR__, 3) . '/lang')->languages();

        foreach (['en', 'zh', 'hi', 'es', 'fr', 'ar'] as $code) {
            $this->assertArrayHasKey($code, $languages);
        }
    }
}
