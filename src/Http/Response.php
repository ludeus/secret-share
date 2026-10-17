<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Http;

/**
 * Réponse HTTP
 */
final class Response
{
    /**
     * @param array<string, string> $headers
     * @param list<string>          $cookies en-têtes Set-Cookie complets
     */
    public function __construct(
        public string $body = '',
        public int $status = 200,
        public array $headers = [],
        public array $cookies = [],
    ) {
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    public function withCookie(string $cookie): self
    {
        $clone = clone $this;
        $clone->cookies[] = $cookie;
        return $clone;
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        foreach ($this->cookies as $cookie) {
            header('Set-Cookie: ' . $cookie, false);
        }
        echo $this->body;
    }
}
