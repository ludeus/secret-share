<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Secret;

use RuntimeException;

/**
 * Mot de passe incorrect ; à zéro tentative restante le secret est détruit
 */
final class InvalidPasswordException extends RuntimeException
{
    public function __construct(public readonly int $remainingAttempts)
    {
        parent::__construct('Mot de passe incorrect');
    }
}
