<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Crypto;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use SecretShare\Clock;
use stdClass;
use Throwable;

/**
 * Encode et décode les jetons JWT (HS256) transportant les secrets chiffrés
 */
final readonly class JwtCodec
{
    private const string ALGORITHM = 'HS256';

    public function __construct(
        private KeyRing $keyRing,
        private Clock $clock,
    ) {
    }

    /**
     * @param array<string, scalar> $claims
     */
    public function encode(array $claims): string
    {
        return JWT::encode($claims, $this->keyRing->jwtKey(), self::ALGORITHM);
    }

    /**
     * Vérifie la signature et l'expiration puis retourne les revendications
     *
     * @return array<string, mixed>
     * @throws DecryptionException si le jeton est invalide ou expiré
     */
    public function decode(string $jwt): array
    {
        $previous = JWT::$timestamp;
        JWT::$timestamp = $this->clock->now();
        try {
            $claims = JWT::decode($jwt, new Key($this->keyRing->jwtKey(), self::ALGORITHM));
        } catch (Throwable $e) {
            throw new DecryptionException('Jeton invalide : ' . $e::class, previous: $e);
        } finally {
            JWT::$timestamp = $previous;
        }
        return self::toArray($claims);
    }

    /**
     * @return array<string, mixed>
     */
    private static function toArray(stdClass $claims): array
    {
        $result = [];
        foreach (get_object_vars($claims) as $name => $value) {
            $result[(string)$name] = $value;
        }
        return $result;
    }
}
