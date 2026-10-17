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
 * Levée lorsque le mot de passe fourni est incorrect
 */
final class WrongPasswordException extends RuntimeException
{
}
