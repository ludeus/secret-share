<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Storage;

use PDO;

/**
 * Compteurs de limitation de débit (fenêtres fixes)
 */
final readonly class RateLimitRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Incrémente atomiquement un compteur et retourne sa nouvelle valeur
     */
    public function hit(string $bucket, int $expiresAt): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO rate_limits (bucket, hits, expires_at) VALUES (:bucket, 1, :expires_at)
            ON CONFLICT (bucket) DO UPDATE SET hits = hits + 1
            RETURNING hits'
        );
        $statement->execute([':bucket' => $bucket, ':expires_at' => $expiresAt]);
        $hits = $statement->fetchColumn();
        $statement->closeCursor();
        return is_int($hits) ? $hits : (int)(is_scalar($hits) ? $hits : PHP_INT_MAX);
    }

    public function purgeExpired(int $now): int
    {
        $statement = $this->pdo->prepare('DELETE FROM rate_limits WHERE expires_at <= :now');
        $statement->execute([':now' => $now]);
        return $statement->rowCount();
    }
}
