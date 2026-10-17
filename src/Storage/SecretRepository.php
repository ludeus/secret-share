<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Storage;

use PDO;
use PDOException;
use UnexpectedValueException;

/**
 * Accès à la table secrets
 */
final readonly class SecretRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Insère un secret ; retourne false si l'identifiant existe déjà (collision)
     */
    public function insert(SecretRecord $record): bool
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO secrets (lookup_hash, server_key, payload, single_use, expires_at, revoke_hash)
            VALUES (:lookup_hash, :server_key, :payload, :single_use, :expires_at, :revoke_hash)'
        );
        $statement->bindValue(':lookup_hash', $record->lookupHash);
        $statement->bindValue(':server_key', $record->serverKey, PDO::PARAM_LOB);
        $statement->bindValue(
            ':payload',
            $record->payload,
            $record->payload === null ? PDO::PARAM_NULL : PDO::PARAM_LOB
        );
        $statement->bindValue(':single_use', (int)$record->singleUse, PDO::PARAM_INT);
        $statement->bindValue(
            ':expires_at',
            $record->expiresAt,
            $record->expiresAt === null ? PDO::PARAM_NULL : PDO::PARAM_INT
        );
        $statement->bindValue(':revoke_hash', $record->revokeHash);
        try {
            return $statement->execute();
        } catch (PDOException $e) {
            // SQLITE_CONSTRAINT : identifiant déjà utilisé
            if ($e->getCode() === '23000') {
                return false;
            }
            throw $e;
        }
    }

    public function find(string $lookupHash): ?SecretRecord
    {
        $statement = $this->pdo->prepare('SELECT * FROM secrets WHERE lookup_hash = :lookup_hash');
        $statement->execute([':lookup_hash' => $lookupHash]);
        /** @var array<string, mixed>|false $row */
        $row = $statement->fetch();
        if ($row === false) {
            return null;
        }
        return new SecretRecord(
            lookupHash: self::blob($row['lookup_hash']),
            serverKey: self::blob($row['server_key']),
            payload: $row['payload'] === null ? null : self::blob($row['payload']),
            singleUse: self::int($row['single_use']) === 1,
            expiresAt: $row['expires_at'] === null ? null : self::int($row['expires_at']),
            revokeHash: self::blob($row['revoke_hash']),
            failedAttempts: self::int($row['failed_attempts']),
        );
    }

    /**
     * Supprime un secret. Le retour indique si c'est bien cet appel qui l'a
     * supprimé : en cas de lectures concurrentes d'un secret à usage unique,
     * une seule obtient true.
     */
    public function delete(string $lookupHash): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM secrets WHERE lookup_hash = :lookup_hash');
        $statement->execute([':lookup_hash' => $lookupHash]);
        return $statement->rowCount() === 1;
    }

    public function deleteByRevokeHash(string $revokeHash): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM secrets WHERE revoke_hash = :revoke_hash');
        $statement->execute([':revoke_hash' => $revokeHash]);
        return $statement->rowCount() === 1;
    }

    /**
     * Réserve atomiquement un essai de mot de passe, AVANT de le vérifier :
     * des requêtes concurrentes ne peuvent ainsi pas dépasser la limite.
     * Retourne le nombre d'essais consommés, ou null si la limite est atteinte
     * (ou le secret absent).
     */
    public function reserveAttempt(string $lookupHash, int $maxAttempts): ?int
    {
        $statement = $this->pdo->prepare(
            'UPDATE secrets SET failed_attempts = failed_attempts + 1
            WHERE lookup_hash = :lookup_hash AND failed_attempts < :max
            RETURNING failed_attempts'
        );
        $statement->execute([':lookup_hash' => $lookupHash, ':max' => $maxAttempts]);
        $value = $statement->fetchColumn();
        $statement->closeCursor();
        return $value === false ? null : self::int($value);
    }

    /**
     * Rend un essai réservé lorsque le mot de passe s'est révélé correct
     */
    public function releaseAttempt(string $lookupHash): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE secrets SET failed_attempts = failed_attempts - 1
            WHERE lookup_hash = :lookup_hash AND failed_attempts > 0'
        );
        $statement->execute([':lookup_hash' => $lookupHash]);
    }

    /**
     * Supprime les secrets expirés ; retourne le nombre de lignes supprimées
     */
    public function purgeExpired(int $now): int
    {
        $statement = $this->pdo->prepare('DELETE FROM secrets WHERE expires_at IS NOT NULL AND expires_at <= :now');
        $statement->execute([':now' => $now]);
        return $statement->rowCount();
    }

    /**
     * Nombre de secrets sans date d'expiration
     */
    public function countUnlimited(): int
    {
        $count = $this->pdo->query('SELECT COUNT(*) FROM secrets WHERE expires_at IS NULL');
        return $count === false ? 0 : self::int($count->fetchColumn());
    }

    /**
     * Nombre de secrets stockés
     */
    public function count(): int
    {
        $count = $this->pdo->query('SELECT COUNT(*) FROM secrets');
        return $count === false ? 0 : self::int($count->fetchColumn());
    }

    /**
     * PDO peut retourner un BLOB sous forme de flux ou de chaîne selon le pilote
     */
    private static function blob(mixed $value): string
    {
        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }
        if (!is_string($value)) {
            throw new UnexpectedValueException('Valeur texte ou binaire attendue en base');
        }
        return $value;
    }

    private static function int(mixed $value): int
    {
        if (is_string($value) && preg_match('/^-?\d+$/', $value)) {
            $value = (int)$value;
        }
        if (!is_int($value)) {
            throw new UnexpectedValueException('Valeur entière attendue en base');
        }
        return $value;
    }
}
