<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare;

/**
 * Horloge système
 */
final class SystemClock implements Clock
{
    public function now(): int
    {
        return time();
    }
}
