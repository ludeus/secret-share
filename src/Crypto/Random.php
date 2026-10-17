<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Crypto;

use Random\RandomException;
use SodiumException;

/**
 * Génération d'identifiants aléatoires et encodage base64url (RFC 4648 §5)
 */
final class Random
{
    /**
     * Alphabet des codes courts : sans caractères ambigus (0/O, 1/l/I)
     */
    public const string SHORT_CODE_ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

    /**
     * Jeton aléatoire encodé en base64url
     *
     * @param positive-int $bytes
     * @throws RandomException
     * @throws SodiumException
     */
    public static function token(int $bytes): string
    {
        return self::base64UrlEncode(random_bytes($bytes));
    }

    /**
     * Code court aléatoire, tiré uniformément dans SHORT_CODE_ALPHABET
     *
     * @throws RandomException
     */
    public static function shortCode(int $length): string
    {
        $max = strlen(self::SHORT_CODE_ALPHABET) - 1;
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= self::SHORT_CODE_ALPHABET[random_int(0, $max)];
        }
        return $code;
    }

    /**
     * Indique si une chaîne a la forme d'un code court
     */
    public static function isShortCode(string $value, int $minLength, int $maxLength): bool
    {
        $length = strlen($value);
        return $length >= $minLength
            && $length <= $maxLength
            && strspn($value, self::SHORT_CODE_ALPHABET) === $length;
    }

    /**
     * @throws SodiumException
     */
    public static function base64UrlEncode(string $data): string
    {
        return sodium_bin2base64($data, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
    }

    /**
     * @throws DecryptionException si l'entrée n'est pas du base64url valide
     */
    public static function base64UrlDecode(string $data): string
    {
        try {
            return sodium_base642bin($data, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
        } catch (SodiumException) {
            throw new DecryptionException('Encodage base64url invalide');
        }
    }
}
