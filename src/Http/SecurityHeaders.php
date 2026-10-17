<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Http;

use SecretShare\Config;

/**
 * En-têtes de sécurité appliqués à toutes les réponses dynamiques
 */
final class SecurityHeaders
{
    private const string CONTENT_SECURITY_POLICY = "default-src 'none'; script-src 'self'; style-src 'self'; "
        . "img-src 'self' data:; form-action 'self'; frame-ancestors 'none'; base-uri 'none'";

    public static function apply(Response $response, Config $config): Response
    {
        $headers = [
            'Content-Security-Policy' => self::CONTENT_SECURITY_POLICY,
            // les pages contiennent des secrets : aucun cache, nulle part
            'Cache-Control' => 'no-store, max-age=0',
            'Pragma' => 'no-cache',
            'Referrer-Policy' => 'no-referrer',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
        ];
        if ($config->isHttps()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000';
        }
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        return $response;
    }
}
