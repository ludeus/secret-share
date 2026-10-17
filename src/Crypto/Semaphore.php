<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Crypto;

use Closure;
use RuntimeException;

/**
 * Sémaphore inter-processus à base de verrous de fichiers (flock) : limite
 * le nombre d'opérations coûteuses exécutées simultanément par les workers
 * Apache. Les verrous sont libérés par le système si le processus meurt.
 */
final readonly class Semaphore
{
    private const int POLL_INTERVAL_US = 20000;

    /**
     * @param positive-int $slots       nombre d'exécutions simultanées autorisées
     * @param int          $timeoutMs   attente maximale d'un emplacement libre
     */
    public function __construct(
        private string $directory,
        private int $slots,
        private int $timeoutMs = 10000,
    ) {
    }

    /**
     * Exécute $operation dès qu'un emplacement est libre
     *
     * @template T
     * @param Closure(): T $operation
     * @return T
     * @throws RuntimeException si aucun emplacement ne se libère à temps
     */
    public function run(Closure $operation): mixed
    {
        $deadline = hrtime(true) + $this->timeoutMs * 1_000_000;
        do {
            for ($slot = 0; $slot < $this->slots; $slot++) {
                $handle = fopen(sprintf('%s/secret-share-%d.lock', $this->directory, $slot), 'c');
                if ($handle === false) {
                    throw new RuntimeException('Impossible de créer le fichier de verrou');
                }
                if (flock($handle, LOCK_EX | LOCK_NB)) {
                    try {
                        return $operation();
                    } finally {
                        flock($handle, LOCK_UN);
                        fclose($handle);
                    }
                }
                fclose($handle);
            }
            usleep(self::POLL_INTERVAL_US);
        } while (hrtime(true) < $deadline);
        throw new RuntimeException('Serveur saturé : aucun emplacement libre pour le calcul Argon2id');
    }
}
