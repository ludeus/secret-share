<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Storage;

use SensitiveParameter;

/**
 * Ligne de la table secrets. Volontairement minimale : aucune date de
 * création, adresse IP ou autre métadonnée n'est conservée.
 */
final readonly class SecretRecord
{
    public function __construct(
        public string $lookupHash,
        #[SensitiveParameter]
        public string $serverKey,
        public ?string $payload,
        public bool $singleUse,
        public ?int $expiresAt,
        public string $revokeHash,
        public int $failedAttempts = 0,
    ) {
    }

    public function isExpired(int $now): bool
    {
        return $this->expiresAt !== null && $this->expiresAt <= $now;
    }
}
