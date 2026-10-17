<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Secret;

use RuntimeException;
use SecretShare\Clock;
use SecretShare\Config;
use SecretShare\Crypto\DecryptionException;
use SecretShare\Crypto\Envelope;
use SecretShare\Crypto\JwtCodec;
use SecretShare\Crypto\KeyRing;
use SecretShare\Crypto\PasswordRequiredException;
use SecretShare\Crypto\Random;
use SecretShare\Crypto\WrongPasswordException;
use SecretShare\Storage\SecretRecord;
use SecretShare\Storage\SecretRepository;

/**
 * Création, lecture et révocation des secrets.
 *
 * Deux modes de transport :
 * - JWT (défaut) : le chiffré voyage dans le lien, le serveur ne conserve
 *   que la clé aléatoire nécessaire au déchiffrement ;
 * - code court : le chiffré est conservé sur le serveur, le lien ne contient
 *   qu'un code aléatoire.
 *
 * Dans les deux cas le lien ET le serveur sont nécessaires pour lire le
 * secret, et supprimer la ligne en base suffit à le détruire définitivement.
 */
final readonly class SecretService
{
    private const int JWT_VERSION = 1;
    private const int JTI_BYTES = 16;
    private const int SERVER_KEY_BYTES = 32;
    private const int REVOKE_TOKEN_BYTES = 24;
    private const int MAX_INSERT_ATTEMPTS = 5;
    private const string NS_JWT = 't';
    private const string NS_CODE = 'c';
    private const string NS_REVOKE = 'r';

    public function __construct(
        private Config $config,
        private SecretRepository $repository,
        private KeyRing $keyRing,
        private Envelope $envelope,
        private JwtCodec $jwtCodec,
        private Clock $clock,
    ) {
    }

    /**
     * @throws QuotaExceededException si un quota de stockage est atteint
     */
    public function create(ShareRequest $request): CreatedSecret
    {
        // quota global : empêche de remplir le disque par des créations en masse
        if ($this->repository->count() >= $this->config->maxStoredSecrets) {
            throw new QuotaExceededException('error.storage_full');
        }
        // quota dédié aux secrets sans expiration : ils ne doivent jamais pouvoir saturer durablement le quota global
        if (
            $request->duration === Duration::Unlimited
            && $this->repository->countUnlimited() >= $this->config->maxUnlimitedSecrets
        ) {
            throw new QuotaExceededException('error.unlimited_quota');
        }
        $expiresAt = $request->duration->expiresAt($this->clock->now());
        $revokeToken = Random::token(self::REVOKE_TOKEN_BYTES);

        for ($attempt = 0; $attempt < self::MAX_INSERT_ATTEMPTS; $attempt++) {
            $serverKey = random_bytes(self::SERVER_KEY_BYTES);
            if ($request->shortUrl) {
                $publicId = Random::shortCode($this->config->shortCodeLength);
                $lookupHash = $this->keyRing->lookupHash(self::NS_CODE, $publicId);
            } else {
                $publicId = Random::token(self::JTI_BYTES);
                $lookupHash = $this->keyRing->lookupHash(self::NS_JWT, $publicId);
            }
            $blob = $this->envelope->seal(
                $request->secret,
                $request->password,
                $this->keyRing->dataKey($serverKey, $publicId),
                $publicId
            );
            $record = new SecretRecord(
                lookupHash: $lookupHash,
                serverKey: $serverKey,
                payload: $request->shortUrl ? $blob : null,
                singleUse: $request->singleUse,
                expiresAt: $expiresAt,
                revokeHash: $this->keyRing->lookupHash(self::NS_REVOKE, $revokeToken),
            );
            if ($this->repository->insert($record)) {
                return new CreatedSecret(
                    token: $request->shortUrl ? $publicId : $this->encodeJwt($publicId, $blob, $expiresAt),
                    revokeToken: $revokeToken,
                    expiresAt: $expiresAt,
                    singleUse: $request->singleUse,
                    shortUrl: $request->shortUrl,
                    passwordProtected: $request->password !== null,
                );
            }
        }
        throw new RuntimeException('Impossible de générer un identifiant unique, augmenter SHORT_CODE_LENGTH');
    }

    /**
     * Déchiffre un secret à partir du jeton présent dans le lien
     *
     * @throws SecretNotFoundException
     * @throws PasswordRequiredException
     * @throws InvalidPasswordException
     */
    public function reveal(string $token, #[\SensitiveParameter] ?string $password): RevealedSecret
    {
        [$publicId, $lookupHash, $blob] = $this->parseToken($token);

        $record = $this->repository->find($lookupHash);
        if ($record === null) {
            throw new SecretNotFoundException();
        }
        if ($record->isExpired($this->clock->now())) {
            $this->repository->delete($lookupHash);
            throw new SecretNotFoundException();
        }
        $blob ??= $record->payload;
        if ($blob === null) {
            throw new SecretNotFoundException();
        }

        $attempts = null;
        $reserveAttempt = function () use ($lookupHash, &$attempts): void {
            $attempts = $this->repository->reserveAttempt($lookupHash, $this->config->maxPasswordAttempts);
            if ($attempts === null) {
                $this->repository->delete($lookupHash);
                throw new InvalidPasswordException(0);
            }
        };
        try {
            $secret = $this->envelope->open(
                $blob,
                $password,
                $this->keyRing->dataKey($record->serverKey, $publicId),
                $publicId,
                $reserveAttempt
            );
        } catch (DecryptionException) {
            throw new SecretNotFoundException();
        } catch (WrongPasswordException) {
            $remaining = $this->config->maxPasswordAttempts - (int)$attempts;
            if ($remaining <= 0) {
                // trop d'essais : le secret est détruit pour bloquer toute attaque par force brute
                $this->repository->delete($lookupHash);
                $remaining = 0;
            }
            throw new InvalidPasswordException($remaining);
        }
        if ($attempts !== null && !$record->singleUse) {
            $this->repository->releaseAttempt($lookupHash);
        }

        // usage unique : seul l'appel qui supprime effectivement la ligne obtient le secret
        if ($record->singleUse && !$this->repository->delete($lookupHash)) {
            throw new SecretNotFoundException();
        }
        return new RevealedSecret($secret, $record->singleUse, $record->expiresAt);
    }

    /**
     * Détruit un secret à partir de son jeton de révocation
     */
    public function revoke(string $revokeToken): bool
    {
        if ($revokeToken === '' || strlen($revokeToken) > 128) {
            return false;
        }
        return $this->repository->deleteByRevokeHash(
            $this->keyRing->lookupHash(self::NS_REVOKE, $revokeToken)
        );
    }

    private function encodeJwt(string $jti, string $blob, ?int $expiresAt): string
    {
        $claims = [
            'v' => self::JWT_VERSION,
            'jti' => $jti,
            'd' => Random::base64UrlEncode($blob),
        ];
        if ($expiresAt !== null) {
            $claims['exp'] = $expiresAt;
        }
        return $this->jwtCodec->encode($claims);
    }

    /**
     * Identifie le type de jeton (JWT ou code court)
     *
     * @return array{string, string, ?string} identifiant public, empreinte de recherche, chiffré éventuel
     * @throws SecretNotFoundException
     */
    private function parseToken(string $token): array
    {
        if (Random::isShortCode($token, Config::MIN_SHORT_CODE_LENGTH, Config::MAX_SHORT_CODE_LENGTH)) {
            return [$token, $this->keyRing->lookupHash(self::NS_CODE, $token), null];
        }
        if (substr_count($token, '.') !== 2) {
            throw new SecretNotFoundException();
        }
        try {
            $claims = $this->jwtCodec->decode($token);
            $jti = $claims['jti'] ?? null;
            $data = $claims['d'] ?? null;
            if (($claims['v'] ?? null) !== self::JWT_VERSION || !is_string($jti) || !is_string($data)) {
                throw new SecretNotFoundException();
            }
            return [$jti, $this->keyRing->lookupHash(self::NS_JWT, $jti), Random::base64UrlDecode($data)];
        } catch (DecryptionException) {
            throw new SecretNotFoundException();
        }
    }
}
