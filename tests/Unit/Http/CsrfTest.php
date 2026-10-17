<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Test\Unit\Http;

use PHPUnit\Framework\TestCase;
use Random\RandomException;
use SecretShare\Http\Csrf;
use SecretShare\Http\Request;
use SecretShare\Http\Response;
use SodiumException;

final class CsrfTest extends TestCase
{
    private const string TOKEN = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFG';

    private static function post(?string $cookie, string $field, ?string $fetchSite = null): Request
    {
        return new Request(
            'POST',
            '/',
            [Csrf::FIELD => $field],
            $cookie === null ? [] : ['csrf' => $cookie],
            $fetchSite === null ? [] : ['sec-fetch-site' => $fetchSite]
        );
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function testTokenReusesValidCookie(): void
    {
        $csrf = new Csrf(false);

        $this->assertSame(self::TOKEN, $csrf->token(new Request('GET', '/', cookies: ['csrf' => self::TOKEN])));
        $this->assertMatchesRegularExpression(
            '/^[A-Za-z0-9_-]{43}$/',
            $csrf->token(new Request('GET', '/', cookies: ['csrf' => 'invalide']))
        );
    }

    public function testAttachSetsCookieOnlyWhenNeeded(): void
    {
        $csrf = new Csrf(true);
        $response = $csrf->attach(new Request('GET', '/'), new Response(), self::TOKEN);

        $this->assertCount(1, $response->cookies);
        $this->assertStringStartsWith('__Host-csrf=' . self::TOKEN, $response->cookies[0]);
        $this->assertStringContainsString('HttpOnly', $response->cookies[0]);
        $this->assertStringContainsString('SameSite=Strict', $response->cookies[0]);
        $this->assertStringContainsString('Secure', $response->cookies[0]);

        $request = new Request('GET', '/', cookies: ['__Host-csrf' => self::TOKEN]);
        $this->assertSame([], $csrf->attach($request, new Response(), self::TOKEN)->cookies);
    }

    public function testInsecureCookieWithoutHttps(): void
    {
        $response = (new Csrf(false))->attach(new Request('GET', '/'), new Response(), self::TOKEN);

        $this->assertStringStartsWith('csrf=', $response->cookies[0]);
        $this->assertStringNotContainsString('Secure', $response->cookies[0]);
    }

    public function testValidation(): void
    {
        $csrf = new Csrf(false);

        $this->assertTrue($csrf->isValid(self::post(self::TOKEN, self::TOKEN)));
        $this->assertTrue($csrf->isValid(self::post(self::TOKEN, self::TOKEN, 'same-origin')));
        $this->assertFalse($csrf->isValid(self::post(self::TOKEN, self::TOKEN, 'cross-site')));
        $this->assertFalse($csrf->isValid(self::post(self::TOKEN, self::TOKEN, 'same-site')));
        $this->assertFalse($csrf->isValid(self::post(null, self::TOKEN)));
        $this->assertFalse($csrf->isValid(self::post(self::TOKEN, '')));
        $this->assertFalse($csrf->isValid(self::post(self::TOKEN, strrev(self::TOKEN))));
        $this->assertFalse($csrf->isValid(self::post('court', 'court')));
    }
}
