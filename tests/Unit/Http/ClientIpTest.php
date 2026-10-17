<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Test\Unit\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SecretShare\Http\ClientIp;
use SecretShare\Http\Request;

final class ClientIpTest extends TestCase
{
    private static function request(string $remoteAddr, ?string $forwardedFor = null): Request
    {
        return new Request(
            'POST',
            '/',
            headers: $forwardedFor === null ? [] : ['x-forwarded-for' => $forwardedFor],
            remoteAddr: $remoteAddr
        );
    }

    /**
     * @return iterable<string, array{string, ?string, ?string}>
     */
    public static function requests(): iterable
    {
        yield 'connexion directe' => ['203.0.113.7', null, '203.0.113.7'];
        yield 'en-tête ignoré si la connexion ne vient pas d\'un proxy' => ['203.0.113.7', '1.2.3.4', '203.0.113.7'];
        yield 'derrière le proxy' => ['172.20.0.1', '198.51.100.9', '198.51.100.9'];
        yield 'adresse falsifiée par le client ignorée' => ['172.20.0.1', '6.6.6.6, 198.51.100.9', '198.51.100.9'];
        yield 'plusieurs proxys de confiance' => ['172.20.0.1', '198.51.100.9, 10.0.0.5', '198.51.100.9'];
        yield 'proxy sans en-tête' => ['172.20.0.1', null, '172.20.0.1'];
        yield 'en-tête invalide' => ['172.20.0.1', 'pas-une-ip', null];
        yield 'connexion invalide' => ['', null, null];
        yield 'ipv6' => ['172.20.0.1', '2001:db8::1', '2001:db8::1'];
    }

    #[DataProvider('requests')]
    public function testResolve(string $remoteAddr, ?string $forwardedFor, ?string $expected): void
    {
        $clientIp = new ClientIp(['172.16.0.0/12', '10.0.0.0/8']);

        $this->assertSame($expected, $clientIp->resolve(self::request($remoteAddr, $forwardedFor)));
    }

    public function testInRange(): void
    {
        $this->assertTrue(ClientIp::inRange('172.20.0.1', '172.16.0.0/12'));
        $this->assertFalse(ClientIp::inRange('172.32.0.1', '172.16.0.0/12'));
        $this->assertTrue(ClientIp::inRange('10.1.2.3', '10.1.2.3'));
        $this->assertTrue(ClientIp::inRange('1.2.3.4', '0.0.0.0/0'));
        $this->assertTrue(ClientIp::inRange('2001:db8::42', '2001:db8::/32'));
        $this->assertFalse(ClientIp::inRange('2001:db9::42', '2001:db8::/32'));
        $this->assertFalse(ClientIp::inRange('10.0.0.1', '2001:db8::/32'));
        $this->assertFalse(ClientIp::inRange('pas-une-ip', '10.0.0.0/8'));
    }

    public function testIsValidRange(): void
    {
        $this->assertTrue(ClientIp::isValidRange('127.0.0.1'));
        $this->assertTrue(ClientIp::isValidRange('172.16.0.0/12'));
        $this->assertTrue(ClientIp::isValidRange('fc00::/7'));
        $this->assertFalse(ClientIp::isValidRange('10.0.0.0/33'));
        $this->assertFalse(ClientIp::isValidRange('10.0.0.0/a'));
        $this->assertFalse(ClientIp::isValidRange('localhost'));
    }

    public function testIpv6IsGroupedBySlash64(): void
    {
        $this->assertSame(ClientIp::bucket('2001:db8:1:2::1'), ClientIp::bucket('2001:db8:1:2:ffff::9'));
        $this->assertNotSame(ClientIp::bucket('2001:db8:1:2::1'), ClientIp::bucket('2001:db8:1:3::1'));
        $this->assertSame('203.0.113.7', ClientIp::bucket('203.0.113.7'));
    }
}
