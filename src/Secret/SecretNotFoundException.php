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
 * Secret introuvable, expiré, déjà consulté, révoqué ou lien altéré.
 * Ces cas sont volontairement indiscernables pour le visiteur.
 */
final class SecretNotFoundException extends RuntimeException
{
}
