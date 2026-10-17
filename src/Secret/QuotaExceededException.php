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
 * Un quota de stockage est atteint : la création est refusée
 */
final class QuotaExceededException extends RuntimeException
{
    /**
     * @param string $messageKey clé de traduction du message à afficher
     */
    public function __construct(public readonly string $messageKey)
    {
        parent::__construct('Quota atteint : ' . $messageKey);
    }
}
