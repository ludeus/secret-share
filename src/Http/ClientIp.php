<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Http;

/**
 * Détermine l'adresse IP du visiteur. Derrière un reverse proxy, l'adresse
 * de connexion est celle du proxy : l'en-tête X-Forwarded-For n'est alors
 * lu que si la connexion provient d'un proxy de confiance, sinon il serait
 * trivial de falsifier son adresse.
 */
final readonly class ClientIp
{
    /**
     * @param list<string> $trustedProxies adresses ou plages CIDR des proxys de confiance
     */
    public function __construct(private array $trustedProxies)
    {
    }

    /**
     * Retourne l'adresse du client, ou null si elle est indéterminable
     */
    public function resolve(Request $request): ?string
    {
        if (!self::isIp($request->remoteAddr)) {
            return null;
        }
        $chain = [];
        foreach (explode(',', (string)$request->header('x-forwarded-for')) as $hop) {
            $hop = trim($hop);
            if ($hop !== '') {
                $chain[] = $hop;
            }
        }

        // remonte la chaîne depuis le proxy le plus proche : le premier maillon non fiable est le client
        $client = $request->remoteAddr;
        while ($chain !== [] && $this->isTrusted($client)) {
            $client = (string)array_pop($chain);
            if (!self::isIp($client)) {
                return null;
            }
        }
        return $client;
    }

    /**
     * Clé de regroupement : l'adresse IPv4, ou le préfixe /64 en IPv6
     * (un même abonné dispose généralement de tout un /64)
     */
    public static function bucket(string $ip): string
    {
        $binary = (string)inet_pton($ip);
        return strlen($binary) === 16 ? bin2hex(substr($binary, 0, 8)) . '::/64' : $ip;
    }

    public static function isIp(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Indique si une adresse appartient à une plage CIDR (ou est égale à une adresse)
     */
    public static function inRange(string $ip, string $range): bool
    {
        [$network, $bits] = self::splitRange($range);
        $ipBinary = @inet_pton($ip);
        $networkBinary = @inet_pton($network);
        if ($ipBinary === false || $networkBinary === false || strlen($ipBinary) !== strlen($networkBinary)) {
            return false;
        }
        $maxBits = strlen($ipBinary) * 8;
        $bits = $bits === null ? $maxBits : (int)$bits;
        if ($bits < 0 || $bits > $maxBits) {
            return false;
        }
        $bytes = intdiv($bits, 8);
        if (substr($ipBinary, 0, $bytes) !== substr($networkBinary, 0, $bytes)) {
            return false;
        }
        $remaining = $bits % 8;
        if ($remaining === 0) {
            return true;
        }
        // compare les bits de poids fort de l'octet partiel
        $mask = (0xFF << (8 - $remaining)) & 0xFF;
        return (ord($ipBinary[$bytes]) & $mask) === (ord($networkBinary[$bytes]) & $mask);
    }

    /**
     * Valide une plage CIDR ou une adresse
     */
    public static function isValidRange(string $range): bool
    {
        [$network, $bits] = self::splitRange($range);
        if (!self::isIp($network)) {
            return false;
        }
        if ($bits === null) {
            return true;
        }
        $maxBits = str_contains($network, ':') ? 128 : 32;
        return preg_match('/^\d{1,3}$/', $bits) === 1 && (int)$bits <= $maxBits;
    }

    /**
     * « 10.0.0.0/8 » => ['10.0.0.0', '8'], « 10.1.2.3 » => ['10.1.2.3', null]
     *
     * @return array{string, ?string}
     */
    private static function splitRange(string $range): array
    {
        $position = strpos($range, '/');
        if ($position === false) {
            return [$range, null];
        }
        return [substr($range, 0, $position), substr($range, $position + 1)];
    }

    private function isTrusted(string $ip): bool
    {
        foreach ($this->trustedProxies as $range) {
            if (self::inRange($ip, $range)) {
                return true;
            }
        }
        return false;
    }
}
