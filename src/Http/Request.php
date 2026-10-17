<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Http;

/**
 * Requête HTTP minimale et immuable
 */
final readonly class Request
{
    /**
     * @param array<array-key, mixed>   $post
     * @param array<array-key, mixed>   $cookies
     * @param array<string, string>     $headers en minuscules
     * @param string                    $remoteAddr adresse IP de la connexion (proxy éventuel)
     * @param array<array-key, mixed>   $query paramètres de l'URL
     */
    public function __construct(
        public string $method,
        public string $path,
        public array $post = [],
        public array $cookies = [],
        public array $headers = [],
        public string $remoteAddr = '',
        public array $query = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (is_string($name) && str_starts_with($name, 'HTTP_') && is_string($value)) {
                $headers[strtolower(str_replace('_', '-', substr($name, 5)))] = $value;
            }
        }
        $uri = is_string($_SERVER['REQUEST_URI'] ?? null) ? $_SERVER['REQUEST_URI'] : '/';
        $method = is_string($_SERVER['REQUEST_METHOD'] ?? null) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        return new self(
            method: strtoupper($method),
            path: (string)(parse_url($uri, PHP_URL_PATH) ?: '/'),
            post: $_POST,
            cookies: $_COOKIE,
            headers: $headers,
            remoteAddr: is_string($_SERVER['REMOTE_ADDR'] ?? null) ? $_SERVER['REMOTE_ADDR'] : '',
            query: $_GET,
        );
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function postString(string $name): string
    {
        $value = $this->post[$name] ?? '';
        return is_string($value) ? $value : '';
    }

    public function queryString(string $name): ?string
    {
        $value = $this->query[$name] ?? null;
        return is_string($value) ? $value : null;
    }

    public function cookie(string $name): ?string
    {
        $value = $this->cookies[$name] ?? null;
        return is_string($value) ? $value : null;
    }
}
