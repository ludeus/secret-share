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
 * Tâches de nettoyage (secrets expirés, compteurs périmés), exécutées au
 * plus une fois par intervalle pour éviter une écriture à chaque requête
 */
final readonly class Maintenance
{
    private const string LAST_RUN = 'maintenance_last_run';

    public function __construct(
        private PDO $pdo,
        private SecretRepository $secrets,
        private RateLimitRepository $rateLimits,
        private int $interval = 60,
    ) {
    }

    /**
     * Lance le nettoyage s'il n'a pas eu lieu depuis $interval secondes
     */
    public function runIfDue(int $now): bool
    {
        $select = $this->pdo->prepare('SELECT value FROM meta WHERE name = :name');
        $select->execute([':name' => self::LAST_RUN]);
        $lastRun = $select->fetchColumn();
        $select->closeCursor();
        if (is_int($lastRun) && $lastRun > $now - $this->interval) {
            return false;
        }

        // seule la requête qui met à jour l'horodatage exécute le nettoyage
        $claim = $this->pdo->prepare(
            'INSERT INTO meta (name, value) VALUES (:name, :now)
            ON CONFLICT (name) DO UPDATE SET value = excluded.value WHERE value <= :threshold'
        );
        $claim->execute([':name' => self::LAST_RUN, ':now' => $now, ':threshold' => $now - $this->interval]);
        if ($claim->rowCount() !== 1) {
            return false;
        }
        $this->secrets->purgeExpired($now);
        $this->rateLimits->purgeExpired($now);
        return true;
    }
}
