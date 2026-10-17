<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Secret;

/**
 * Résultat de la création d'un secret
 */
final readonly class CreatedSecret
{
    /**
     * @param string $token     jeton JWT ou code court, à placer dans le fragment de l'URL de partage
     * @param string $revokeToken jeton permettant au créateur de détruire le secret
     */
    public function __construct(
        public string $token,
        public string $revokeToken,
        public ?int $expiresAt,
        public bool $singleUse,
        public bool $shortUrl,
        public bool $passwordProtected,
    ) {
    }
}
