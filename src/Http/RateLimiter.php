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
    public const string SCOPE_CREATE = 'create';
    public const string SCOPE_READ = 'read';

    /**
     * Fenêtres (en secondes) utilisées par chaque type d'action
     */
    public const array WINDOWS = [
        self::SCOPE_CREATE => [60, 3600],
        self::SCOPE_READ => [60],
    ];

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
        [$bucket, $windowEnd] = $this->bucket($scope, $client, $window, $now);
        $hits = $this->repository->hit($bucket, $windowEnd);
        return $hits > $limit ? max(1, $windowEnd - $now) : null;
    }

    /**
     * Nombre d'actions comptabilisées pour un client dans les fenêtres en cours
     *
     * @return array<string, int> « type/durée de fenêtre » => nombre d'actions
     */
    public function status(string $client): array
    {
        $now = $this->clock->now();
        $status = [];
        foreach (self::WINDOWS as $scope => $windows) {
            foreach ($windows as $window) {
                [$bucket] = $this->bucket($scope, $client, $window, $now);
                $status[sprintf('%s/%ds', $scope, $window)] = $this->repository->hits($bucket);
            }
        }
        return $status;
    }

    /**
     * Remet à zéro les compteurs d'un client ; retourne le nombre de compteurs supprimés
     */
    public function reset(string $client): int
    {
        $now = $this->clock->now();
        $deleted = 0;
        foreach (self::WINDOWS as $scope => $windows) {
            foreach ($windows as $window) {
                [$bucket] = $this->bucket($scope, $client, $window, $now);
                $deleted += (int)$this->repository->delete($bucket);
            }
        }
        return $deleted;
    }

    /**
     * Remet à zéro les compteurs de tous les clients
     */
    public function resetAll(): int
    {
        return $this->repository->deleteAll();
    }

    /**
     * Identifiant du compteur d'un client pour la fenêtre en cours, et fin de cette fenêtre.
     * L'empreinte ne peut être recalculée qu'en connaissant l'adresse du client.
     *
     * @return array{string, int}
     */
    private function bucket(string $scope, string $client, int $window, int $now): array
    {
        $windowStart = intdiv($now, $window) * $window;
        $bucket = $this->keyRing->clientHash(
            sprintf('%s|%d|%d|%s', $scope, $window, $windowStart, $client),
            gmdate('Y-m-d', $now)
        );
        return [$bucket, $windowStart + $window];
    }
}
