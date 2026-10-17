<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Crypto;

/**
 * Dérive, à partir de la clé maître APP_SECRET, des sous-clés indépendantes
 * pour chaque usage (signature JWT, index de recherche, chiffrement…).
 * Ainsi une sous-clé compromise ne permet pas de remonter aux autres.
 */
final readonly class KeyRing
{
    private string $jwtKey;
    private string $lookupKey;
    private string $encryptionSalt;

    public function __construct(#[\SensitiveParameter] string $appSecret)
    {
        $this->jwtKey = hash_hkdf('sha256', $appSecret, 32, 'secret-share/v1/jwt');
        $this->lookupKey = hash_hkdf('sha256', $appSecret, 32, 'secret-share/v1/lookup');
        $this->encryptionSalt = hash_hkdf('sha256', $appSecret, 32, 'secret-share/v1/encryption');
    }

    /**
     * Clé de signature HMAC des jetons JWT
     */
    public function jwtKey(): string
    {
        return $this->jwtKey;
    }

    /**
     * Empreinte servant d'identifiant en base : l'identifiant réel (présent
     * uniquement dans le lien) n'est jamais stocké en clair.
     */
    public function lookupHash(string $namespace, string $publicId): string
    {
        return hash_hmac('sha256', $namespace . ':' . $publicId, $this->lookupKey);
    }

    /**
     * Clé de chiffrement d'un secret. Elle combine trois éléments :
     * APP_SECRET (configuration), la clé aléatoire stockée en base (serveur)
     * et l'identifiant public (lien). Il faut les trois pour déchiffrer, et
     * supprimer la ligne en base rend le secret définitivement illisible.
     */
    public function dataKey(#[\SensitiveParameter] string $serverKey, string $publicId): string
    {
        return hash_hkdf(
            'sha256',
            $serverKey,
            SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES,
            'secret-share/v1/data/' . $publicId,
            $this->encryptionSalt
        );
    }
}
