<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Secret;

/**
 * Demande de création d'un secret, validée.
 * Les mêmes règles sont vérifiées côté navigateur (public/assets/app.js).
 */
final readonly class ShareRequest
{
    public const int MAX_PASSWORD_LENGTH = 1024;

    public function __construct(
        #[\SensitiveParameter]
        public string $secret,
        public Duration $duration,
        public bool $singleUse,
        #[\SensitiveParameter]
        public ?string $password,
        public bool $shortUrl,
    ) {
    }

    /**
     * Valide les données d'un formulaire
     *
     * @param array<array-key, mixed> $input
     * @throws ValidationException
     */
    public static function fromInput(array $input, int $maxSecretLength): self
    {
        $errors = [];

        $secret = $input['secret'] ?? '';
        if (!is_string($secret) || !mb_check_encoding($secret, 'UTF-8')) {
            $errors['secret'] = 'error.secret_invalid';
            $secret = '';
        } else {
            // les navigateurs envoient des CRLF : on normalise pour un rendu identique
            $secret = str_replace("\r\n", "\n", $secret);
            if (trim($secret) === '') {
                $errors['secret'] = 'error.secret_required';
            } elseif (mb_strlen($secret) > $maxSecretLength) {
                $errors['secret'] = 'error.secret_too_long';
            }
        }

        $duration = is_string($input['duration'] ?? null) ? Duration::tryFrom($input['duration']) : null;
        if ($duration === null) {
            $errors['duration'] = 'error.duration_invalid';
        }

        $password = $input['password'] ?? '';
        if (!is_string($password) || !mb_check_encoding($password, 'UTF-8')) {
            $errors['password'] = 'error.password_invalid';
            $password = '';
        } elseif (mb_strlen($password) > self::MAX_PASSWORD_LENGTH) {
            $errors['password'] = 'error.password_too_long';
        }

        $singleUse = self::checked($input, 'single_use');
        $shortUrl = self::checked($input, 'short_url');
        if ($shortUrl && !self::shortUrlAllowed($singleUse, $duration)) {
            $errors['short_url'] = 'error.short_url_forbidden';
        }

        if ($errors !== [] || $duration === null) {
            throw new ValidationException($errors);
        }

        return new self(
            secret: $secret,
            duration: $duration,
            singleUse: $singleUse,
            password: $password === '' ? null : $password,
            shortUrl: $shortUrl,
        );
    }

    /**
     * L'URL courte implique de stocker le chiffré sur le serveur : interdit
     * s'il n'y a ni usage unique ni date limite (stockage indéfini).
     */
    public static function shortUrlAllowed(bool $singleUse, ?Duration $duration): bool
    {
        return $singleUse || ($duration !== null && $duration !== Duration::Unlimited);
    }

    /**
     * @param array<array-key, mixed> $input
     */
    private static function checked(array $input, string $name): bool
    {
        return ($input[$name] ?? null) === '1';
    }
}
