<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Test;

use SecretShare\Clock;

/**
 * Horloge figée, avançable manuellement
 */
final class FrozenClock implements Clock
{
    public function __construct(private int $now = 1_800_000_000)
    {
    }

    public function now(): int
    {
        return $this->now;
    }

    public function advance(int $seconds): void
    {
        $this->now += $seconds;
    }
}
