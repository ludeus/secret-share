<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Crypto;

use Closure;
use Random\RandomException;
use SensitiveParameter;
use SodiumException;

/**
 * Chiffrement d'un secret en une ou deux couches :
 *
 *   secret ─ sodium_pad ─┬────────────────────────────┬─ [drapeau] ─ XChaCha20-Poly1305(clé serveur)
 *                        └─ XChaCha20-Poly1305(Argon2id(mot de passe)) ┘
 *
 * La couche interne (optionnelle) dépend du mot de passe, la couche externe
 * de la clé dérivée par KeyRing::dataKey(). Le remplissage (padding) masque
 * la longueur exacte du secret.
 */
final readonly class Envelope
{
    private const string FLAG_PLAIN = "\x00";
    private const string FLAG_PASSWORD = "\x01";
    private const int PAD_BLOCK_SIZE = 64;

    public function __construct(
        private int $pwhashOpsLimit = SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
        private int $pwhashMemLimit = SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE,
        private ?Semaphore $pwhashSemaphore = null,
    ) {
    }

    /**
     * Chiffre un secret, avec une couche supplémentaire si un mot de passe est fourni
     *
     * @param string $context données associées authentifiées (empêche de
     *                        déplacer un chiffré d'un secret vers un autre)
     * @throws RandomException
     * @throws SodiumException
     */
    public function seal(
        #[SensitiveParameter] string $secret,
        #[SensitiveParameter] ?string $password,
        #[SensitiveParameter] string $key,
        string $context
    ): string {
        $content = sodium_pad($secret, self::PAD_BLOCK_SIZE);
        if ($password !== null && $password !== '') {
            $salt = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
            $content = self::FLAG_PASSWORD . $salt
                . $this->encrypt($content, $this->passwordKey($password, $salt), $context);
        } else {
            $content = self::FLAG_PLAIN . $content;
        }
        return $this->encrypt($content, $key, $context);
    }

    /**
     * Déchiffre un secret produit par seal()
     *
     * @param (Closure(): void)|null $beforePasswordCheck appelé juste avant de tester
     *        le mot de passe ; peut lever une exception pour refuser l'essai
     * @throws DecryptionException   chiffré invalide ou clé erronée
     * @throws PasswordRequiredException
     * @throws WrongPasswordException
     * @throws SodiumException
     */
    public function open(
        string $blob,
        #[SensitiveParameter] ?string $password,
        #[SensitiveParameter] string $key,
        string $context,
        ?Closure $beforePasswordCheck = null
    ): string {
        $content = $this->decrypt($blob, $key, $context);
        $flag = substr($content, 0, 1);
        $content = substr($content, 1);

        if ($flag === self::FLAG_PASSWORD) {
            if ($password === null || $password === '') {
                throw new PasswordRequiredException();
            }
            if ($beforePasswordCheck !== null) {
                $beforePasswordCheck();
            }
            $salt = substr($content, 0, SODIUM_CRYPTO_PWHASH_SALTBYTES);
            try {
                $content = $this->decrypt(
                    substr($content, SODIUM_CRYPTO_PWHASH_SALTBYTES),
                    $this->passwordKey($password, $salt),
                    $context
                );
            } catch (DecryptionException) {
                throw new WrongPasswordException();
            }
        } elseif ($flag !== self::FLAG_PLAIN) {
            throw new DecryptionException('Format de contenu inconnu');
        }

        try {
            return sodium_unpad($content, self::PAD_BLOCK_SIZE);
        } catch (SodiumException) {
            throw new DecryptionException('Remplissage invalide');
        }
    }

    /**
     * Chiffrement authentifié : nonce (24 octets) || chiffré || tag
     *
     * @throws RandomException
     * @throws SodiumException
     */
    private function encrypt(string $plain, #[SensitiveParameter] string $key, string $context): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        return $nonce . sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plain, $context, $nonce, $key);
    }

    /**
     * @throws DecryptionException
     * @throws SodiumException
     */
    private function decrypt(string $blob, #[SensitiveParameter] string $key, string $context): string
    {
        $nonceLength = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
        if (strlen($blob) < $nonceLength + SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_ABYTES) {
            throw new DecryptionException('Chiffré trop court');
        }
        $plain = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            substr($blob, $nonceLength),
            $context,
            substr($blob, 0, $nonceLength),
            $key
        );
        if ($plain === false) {
            throw new DecryptionException('Échec de l\'authentification du chiffré');
        }
        return $plain;
    }

    /**
     * Dérive une clé du mot de passe avec Argon2id (résistant au brute-force GPU).
     * Le sémaphore borne le nombre de dérivations simultanées : chacune alloue
     * 64 Mio hors du memory_limit de PHP.
     *
     * @throws DecryptionException
     * @throws SodiumException
     */
    private function passwordKey(#[SensitiveParameter] string $password, string $salt): string
    {
        if (strlen($salt) !== SODIUM_CRYPTO_PWHASH_SALTBYTES) {
            throw new DecryptionException('Sel invalide');
        }
        $derive = fn (): string => sodium_crypto_pwhash(
            SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES,
            $password,
            $salt,
            $this->pwhashOpsLimit,
            $this->pwhashMemLimit,
            SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13
        );
        return $this->pwhashSemaphore?->run($derive) ?? $derive();
    }
}
