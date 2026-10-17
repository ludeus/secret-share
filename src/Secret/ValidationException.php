<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Secret;

use InvalidArgumentException;

/**
 * Erreurs de validation d'une demande, indexées par nom de champ
 */
final class ValidationException extends InvalidArgumentException
{
    /**
     * @param array<string, string> $errors champ => clé de traduction du message
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('Données invalides : ' . implode(', ', array_keys($errors)));
    }
}
