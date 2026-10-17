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
 * Le nombre maximal de secrets stockés (MAX_STORED_SECRETS) est atteint
 */
final class StorageFullException extends RuntimeException
{
}
