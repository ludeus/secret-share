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
 * Levée lorsqu'un chiffré ne peut pas être déchiffré (clé erronée, contenu altéré…)
 */
final class DecryptionException extends RuntimeException
{
}
