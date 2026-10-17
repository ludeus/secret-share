<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

use SecretShare\App;
use SecretShare\Config;
use SecretShare\Http\Request;
use SecretShare\Http\Response;

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    $app = App::create(Config::fromEnvironment(getenv()));
    $app->handle(Request::fromGlobals())->send();
} catch (Throwable $e) {
    // seul le type et le message sont journalisés : jamais les données de la requête
    error_log(sprintf('[secret-share] %s: %s', $e::class, $e->getMessage()));
    (new Response(
        'Service temporarily unavailable.',
        503,
        [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; frame-ancestors 'none'",
        ]
    ))->send();
}
