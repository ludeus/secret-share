<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Secret;

/**
 * Secret déchiffré
 */
final readonly class RevealedSecret
{
    public function __construct(
        #[\SensitiveParameter]
        public string $secret,
        public bool $destroyed,
        public ?int $expiresAt,
    ) {
    }
}
