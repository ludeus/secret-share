<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Crypto;

use RuntimeException;

/**
 * Levée lorsque le secret est protégé par un mot de passe qui n'a pas été fourni
 */
final class PasswordRequiredException extends RuntimeException
{
}
