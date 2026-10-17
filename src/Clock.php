<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare;

/**
 * Source de temps, injectable pour rendre les expirations testables
 */
interface Clock
{
    /**
     * Timestamp Unix courant
     */
    public function now(): int;
}
