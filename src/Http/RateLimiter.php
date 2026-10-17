<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Http;

use SecretShare\Clock;
use SecretShare\Crypto\KeyRing;
use SecretShare\Storage\RateLimitRepository;

/**
 * Limitation de débit par client, par fenêtres de temps fixes.
 * Seule une empreinte HMAC de l'adresse est stockée, avec une clé renouvelée
 * chaque jour, et chaque compteur est supprimé à la fin de sa fenêtre.
 */
final readonly class RateLimiter
{
    public function __construct(
        private RateLimitRepository $repository,
        private KeyRing $keyRing,
        private Clock $clock,
    ) {
    }

    /**
     * Comptabilise une action et indique si elle est autorisée
     *
     * @param int $limit nombre d'actions autorisées par fenêtre, 0 pour ne pas limiter
     * @return int|null null si autorisée, sinon le délai en secondes avant la prochaine fenêtre
     */
    public function consume(string $scope, string $client, int $limit, int $window): ?int
    {
        if ($limit <= 0) {
            return null;
        }
        $now = $this->clock->now();
        $windowStart = intdiv($now, $window) * $window;
        $windowEnd = $windowStart + $window;
        $bucket = $this->keyRing->clientHash(
            sprintf('%s|%d|%d|%s', $scope, $window, $windowStart, $client),
            gmdate('Y-m-d', $now)
        );
        $hits = $this->repository->hit($bucket, $windowEnd);
        return $hits > $limit ? max(1, $windowEnd - $now) : null;
    }
}
