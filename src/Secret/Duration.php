<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Secret;

/**
 * Durées de validité proposées à la création d'un secret
 */
enum Duration: string
{
    case TenMinutes = '10m';
    case OneHour = '1h';
    case FourHours = '4h';
    case OneDay = '1d';
    case OneWeek = '1w';
    case OneMonth = '1mo';
    case OneYear = '1y';
    case Unlimited = 'none';

    public const self DEFAULT = self::OneDay;

    /**
     * Durée en secondes, null si illimitée
     */
    public function seconds(): ?int
    {
        return match ($this) {
            self::TenMinutes => 600,
            self::OneHour => 3600,
            self::FourHours => 4 * 3600,
            self::OneDay => 86400,
            self::OneWeek => 7 * 86400,
            self::OneMonth => 30 * 86400,
            self::OneYear => 365 * 86400,
            self::Unlimited => null,
        };
    }

    /**
     * Date d'expiration (timestamp) à partir de maintenant, null si illimitée
     */
    public function expiresAt(int $now): ?int
    {
        $seconds = $this->seconds();
        return $seconds === null ? null : $now + $seconds;
    }

    /**
     * Clé de traduction du libellé
     */
    public function labelKey(): string
    {
        return 'duration.' . $this->value;
    }
}
