<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Test\Functional;

use PHPUnit\Framework\TestCase;
use SecretShare\App;
use SecretShare\Http\Request;
use SecretShare\Http\Response;
use SecretShare\Test\Factory;
use SecretShare\Test\FrozenClock;
use Throwable;

/**
 * Parcours complets à travers le routeur, les contrôleurs et les gabarits
 */
final class AppTest extends TestCase
{
    private const string CSRF = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFG';

    private App $app;
    private FrozenClock $clock;

    protected function setUp(): void
    {
        $this->clock = new FrozenClock();
        $this->app = App::create(Factory::config(), Factory::pdo(), $this->clock, Factory::envelope());
    }

    /**
     * @throws Throwable
     */
    private function get(string $path, string $language = 'fr'): Response
    {
        return $this->app->handle(new Request('GET', $path, headers: ['accept-language' => $language]));
    }

    /**
     * @param array<string, string> $data
     * @throws Throwable
     */
    private function post(string $path, array $data): Response
    {
        return $this->app->handle(
            new Request(
                'POST',
                $path,
                $data + ['csrf_token' => self::CSRF],
                ['__Host-csrf' => self::CSRF],
                ['sec-fetch-site' => 'same-origin', 'accept-language' => 'fr']
            )
        );
    }

    /**
     * Extrait la partie après « # » de l'URL affichée dans le champ $id
     */
    private function fragment(Response $response, string $id): string
    {
        $pattern = '#id="' . $id . '" value="https://secret\.example\.org/[sr]\#([^"]+)"#';
        if (preg_match($pattern, $response->body, $m) !== 1) {
            $this->fail(sprintf('Champ %s introuvable', $id));
        }
        return html_entity_decode($m[1]);
    }

    /**
     * Crée un secret via le formulaire et retourne le jeton du lien de partage
     *
     * @param array<string, string> $data
     * @throws Throwable
     */
    private function createSecret(array $data = []): string
    {
        $response = $this->post('/', $data + ['secret' => 'mon <secret>', 'duration' => '1h', 'single_use' => '1']);
        $this->assertSame(201, $response->status);
        return $this->fragment($response, 'share-url');
    }

    /**
     * @throws Throwable
     */
    public function testHomePage(): void
    {
        $response = $this->get('/');

        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('<html lang="fr" dir="ltr">', $response->body);
        $this->assertStringContainsString('name="secret"', $response->body);
        $this->assertStringContainsString('<option value="1d"', $response->body);
        $this->assertMatchesRegularExpression('/id="single_use"[^>]*checked/', $response->body);
        $this->assertDoesNotMatchRegularExpression('/id="short_url"[^>]*checked/', $response->body);
        $this->assertSame('text/html; charset=UTF-8', $response->headers['Content-Type']);
        $this->assertArrayHasKey('Content-Security-Policy', $response->headers);
        $this->assertCount(1, $response->cookies);
    }

    /**
     * @throws Throwable
     */
    public function testHomePageInEnglish(): void
    {
        $this->assertStringContainsString('<html lang="en" dir="ltr">', $this->get('/', 'en-GB,en')->body);
    }

    /**
     * @throws Throwable
     */
    public function testLanguageSelectorAndDetection(): void
    {
        $response = $this->get('/', 'es-ES,es;q=0.9');

        $this->assertStringContainsString('<html lang="es" dir="ltr">', $response->body);
        $this->assertStringContainsString('Compartir un secreto', $response->body);
        $this->assertMatchesRegularExpression('/<option value="zh" lang="zh"\s*>🇨🇳 中文<\/option>/u', $response->body);
        $this->assertMatchesRegularExpression(
            '/<option value="es" lang="es"\s*selected>🇪🇸 Español/u',
            $response->body
        );
        // version des fichiers statiques : date de modification
        $this->assertMatchesRegularExpression('#href="/assets/app\.css\?v=\d+"#', $response->body);
    }

    /**
     * @throws Throwable
     */
    public function testLanguageChosenInUrlIsRemembered(): void
    {
        $response = $this->app->handle(new Request('GET', '/', query: ['lang' => 'ar']));

        $this->assertStringContainsString('<html lang="ar" dir="rtl">', $response->body);
        $this->assertContains('lang=ar; Path=/; Max-Age=31536000; SameSite=Lax; Secure', $response->cookies);

        // le cookie l'emporte ensuite sur les préférences du navigateur
        $remembered = $this->app->handle(
            new Request('GET', '/s', cookies: ['lang' => 'de'], headers: ['accept-language' => 'fr'])
        );
        $this->assertStringContainsString('<html lang="de" dir="ltr">', $remembered->body);
    }

    /**
     * @throws Throwable
     */
    public function testTranslationDictionaryUsesTheChosenLocale(): void
    {
        $response = $this->app->handle(new Request('GET', '/translations', query: ['lang' => 'es']));
        $messages = json_decode($response->body, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->status);
        $this->assertSame('application/json; charset=UTF-8', $response->headers['Content-Type']);
        $this->assertIsArray($messages);
        $this->assertSame('Español', $messages['lang.name']);
        $this->assertArrayHasKey('home.title', $messages);
        $this->assertContains('lang=es; Path=/; Max-Age=31536000; SameSite=Lax; Secure', $response->cookies);
    }

    /**
     * @throws Throwable
     */
    public function testUnknownLanguageIsIgnored(): void
    {
        $response = $this->app->handle(
            new Request(
                'GET',
                '/',
                cookies: ['lang' => '../etc'],
                headers: ['accept-language' => 'en'],
                query: ['lang' => 'xx']
            )
        );

        $this->assertStringContainsString('<html lang="en"', $response->body);
        $this->assertSame([], array_filter($response->cookies, fn (string $c): bool => str_starts_with($c, 'lang=')));
    }

    /**
     * @throws Throwable
     */
    public function testLanguageSelectorOnResultPages(): void
    {
        $response = $this->post('/', ['secret' => 'x', 'duration' => '1h', 'single_use' => '1']);

        $this->assertSame(201, $response->status);
        $this->assertStringContainsString('data-lang-switch', $response->body);
        $this->assertStringContainsString('data-lang-switch', $this->get('/')->body);
    }

    /**
     * @throws Throwable
     */
    public function testHealth(): void
    {
        $response = $this->get('/health');

        $this->assertSame(200, $response->status);
        $this->assertSame('OK', $response->body);
    }

    /**
     * @throws Throwable
     */
    public function testUnknownRoute(): void
    {
        $this->assertSame(404, $this->get('/admin')->status);
    }

    /**
     * @throws Throwable
     */
    public function testPostWithoutCsrfIsRejected(): void
    {
        $response = $this->app->handle(new Request('POST', '/', ['secret' => 'x', 'duration' => '1h']));

        $this->assertSame(403, $response->status);
    }

    /**
     * @throws Throwable
     */
    public function testCrossSitePostIsRejected(): void
    {
        $response = $this->app->handle(
            new Request(
                'POST',
                '/',
                ['secret' => 'x', 'duration' => '1h', 'csrf_token' => self::CSRF],
                ['__Host-csrf' => self::CSRF],
                ['sec-fetch-site' => 'cross-site']
            )
        );

        $this->assertSame(403, $response->status);
    }

    /**
     * @throws Throwable
     */
    public function testValidationErrorsKeepInput(): void
    {
        $response = $this->post('/', ['secret' => '', 'duration' => 'none', 'short_url' => '1']);

        $this->assertSame(422, $response->status);
        $this->assertStringContainsString('Veuillez saisir le secret', $response->body);
        $this->assertStringContainsString('L&apos;URL courte nécessite', $response->body);
        $this->assertMatchesRegularExpression('/<option value="none"\s+selected/', $response->body);
    }

    /**
     * @throws Throwable
     */
    public function testCreateShowsQrCodeAndRevokeLink(): void
    {
        $response = $this->post('/', ['secret' => 'abc', 'duration' => '1h', 'single_use' => '1']);

        $this->assertSame(201, $response->status);
        $this->assertStringContainsString('src="data:image/svg+xml;base64,', $response->body);
        $this->assertStringContainsString('https://secret.example.org/r#', $response->body);
        $this->assertStringNotContainsString('abc<', $response->body);
    }

    /**
     * @throws Throwable
     */
    public function testRevealPageDoesNotRevealOnGet(): void
    {
        $token = $this->createSecret();
        $response = $this->get('/s');

        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('data-fragment-token', $response->body);
        // la ressource GET ne consomme pas le secret
        $this->assertSame(200, $this->post('/s', ['token' => $token])->status);
    }

    /**
     * @throws Throwable
     */
    public function testRevealSingleUseSecret(): void
    {
        $token = $this->createSecret();

        $response = $this->post('/s', ['token' => $token]);
        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('mon &lt;secret&gt;</textarea>', $response->body);
        $this->assertStringContainsString('vient d&apos;être détruit', $response->body);

        $this->assertSame(404, $this->post('/s', ['token' => $token])->status);
    }

    /**
     * @throws Throwable
     */
    public function testRevealShortUrlSecret(): void
    {
        $token = $this->createSecret(['short_url' => '1']);

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{10}$/', $token);
        $this->assertStringContainsString('mon &lt;secret&gt;', $this->post('/s', ['token' => $token])->body);
    }

    /**
     * @throws Throwable
     */
    public function testRevealExpiredSecret(): void
    {
        $token = $this->createSecret(['duration' => '10m']);
        $this->clock->advance(3600);

        $this->assertSame(404, $this->post('/s', ['token' => $token])->status);
    }

    /**
     * @throws Throwable
     */
    public function testPasswordFlow(): void
    {
        $token = $this->createSecret(['password' => 'p@ss']);

        $response = $this->post('/s', ['token' => $token]);
        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('type="password" id="password"', $response->body);

        $response = $this->post('/s', ['token' => $token, 'password' => 'faux']);
        $this->assertSame(401, $response->status);
        $this->assertStringContainsString("avant destruction du secret\u{202F}: 2", $response->body);

        $response = $this->post('/s', ['token' => $token, 'password' => 'p@ss']);
        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('mon &lt;secret&gt;', $response->body);
    }

    /**
     * @throws Throwable
     */
    public function testTooManyWrongPasswords(): void
    {
        $token = $this->createSecret(['password' => 'p@ss', 'single_use' => '0']);

        $this->post('/s', ['token' => $token, 'password' => 'a']);
        $this->post('/s', ['token' => $token, 'password' => 'b']);
        $this->assertSame(410, $this->post('/s', ['token' => $token, 'password' => 'c'])->status);
        $this->assertSame(404, $this->post('/s', ['token' => $token, 'password' => 'p@ss'])->status);
    }

    /**
     * @param array<string, string> $data
     * @throws Throwable
     */
    private function postFrom(string $ip, string $path, array $data): Response
    {
        return $this->app->handle(
            new Request(
                'POST',
                $path,
                $data + ['csrf_token' => self::CSRF],
                ['__Host-csrf' => self::CSRF],
                ['sec-fetch-site' => 'same-origin', 'accept-language' => 'fr', 'x-forwarded-for' => $ip],
                '172.20.0.1'
            )
        );
    }

    /**
     * @throws Throwable
     */
    public function testCreationIsRateLimitedPerClient(): void
    {
        $data = ['secret' => 'x', 'duration' => '1h', 'single_use' => '1'];
        for ($i = 0; $i < 5; $i++) {
            $this->assertSame(201, $this->postFrom('198.51.100.1', '/', $data)->status);
        }

        $limited = $this->postFrom('198.51.100.1', '/', $data);
        $this->assertSame(429, $limited->status);
        $this->assertSame('60', $limited->headers['Retry-After']);
        $this->assertStringContainsString('Trop de requêtes', $limited->body);

        // un autre visiteur derrière le même proxy n'est pas affecté
        $this->assertSame(201, $this->postFrom('198.51.100.2', '/', $data)->status);

        $this->clock->advance(60);
        $this->assertSame(201, $this->postFrom('198.51.100.1', '/', $data)->status);
    }

    /**
     * @throws Throwable
     */
    public function testRevealIsRateLimited(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->assertSame(404, $this->postFrom('198.51.100.1', '/s', ['token' => 'abcdefghjk'])->status);
        }
        $this->assertSame(429, $this->postFrom('198.51.100.1', '/s', ['token' => 'abcdefghjk'])->status);
    }

    /**
     * @throws Throwable
     */
    public function testUnlimitedQuotaMessage(): void
    {
        $app = App::create(
            Factory::config(maxUnlimitedSecrets: 0),
            Factory::pdo(),
            $this->clock,
            Factory::envelope()
        );
        $response = $app->handle(
            new Request(
                'POST',
                '/',
                ['secret' => 'x', 'duration' => 'none', 'single_use' => '1', 'csrf_token' => self::CSRF],
                ['__Host-csrf' => self::CSRF]
            )
        );

        $this->assertSame(503, $response->status);
        $this->assertStringContainsString('choisissez une durée de validité', $response->body);
    }

    /**
     * @throws Throwable
     */
    public function testRevokeFlow(): void
    {
        $response = $this->post('/', ['secret' => 'abc', 'duration' => '1h']);
        $share = $this->fragment($response, 'share-url');
        $revoke = $this->fragment($response, 'revoke-url');

        $this->assertSame(200, $this->get('/r')->status);
        $this->assertSame(200, $this->post('/r', ['token' => $revoke])->status);
        $this->assertSame(404, $this->post('/r', ['token' => $revoke])->status);
        $this->assertSame(404, $this->post('/s', ['token' => $share])->status);
    }
}
