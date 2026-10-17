<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare;

use RuntimeException;

/**
 * Levée lorsque la configuration de l'application est invalide
 */
final class ConfigException extends RuntimeException
{
}
