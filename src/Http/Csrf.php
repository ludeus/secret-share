<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Http;

use SecretShare\Crypto\Random;

/**
 * Protection CSRF par double soumission (cookie + champ caché), complétée par
 * les en-têtes Fetch Metadata qui permettent de rejeter les requêtes
 * inter-sites dans les navigateurs récents.
 */
final readonly class Csrf
{
    public const string FIELD = 'csrf_token';
    private string $cookie;

    public function __construct(private bool $secure)
    {
        // en HTTPS, le préfixe __Host- interdit à un sous-domaine d'imposer ce cookie
        $this->cookie = $secure ? '__Host-csrf' : 'csrf';
    }

    /**
     * Retourne le jeton à placer dans les formulaires (celui du cookie s'il est valide)
     */
    public function token(Request $request): string
    {
        $token = $request->cookie($this->cookie);
        return $token !== null && self::isWellFormed($token) ? $token : Random::token(32);
    }

    /**
     * Ajoute le cookie à la réponse si le navigateur ne l'a pas encore
     */
    public function attach(Request $request, Response $response, string $token): Response
    {
        if ($request->cookie($this->cookie) === $token) {
            return $response;
        }
        $cookie = sprintf('%s=%s; Path=/; HttpOnly; SameSite=Strict', $this->cookie, $token);
        if ($this->secure) {
            $cookie .= '; Secure';
        }
        return $response->withCookie($cookie);
    }

    public function isValid(Request $request): bool
    {
        $site = $request->header('sec-fetch-site');
        if ($site !== null && !in_array($site, ['same-origin', 'none'], true)) {
            return false;
        }
        $cookie = $request->cookie($this->cookie);
        $field = $request->postString(self::FIELD);
        return $cookie !== null
            && self::isWellFormed($cookie)
            && hash_equals($cookie, $field);
    }

    private static function isWellFormed(string $token): bool
    {
        return preg_match('/^[A-Za-z0-9_-]{43}$/', $token) === 1;
    }
}
