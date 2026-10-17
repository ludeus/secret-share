<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\View;

use SecretShare\Config;

/**
 * Traductions de l'interface, langue choisie selon l'en-tête Accept-Language
 */
final readonly class Translator
{
    /**
     * @param array<string, string> $messages
     */
    public function __construct(
        public string $locale,
        private array $messages,
    ) {
    }

    /**
     * Charge les messages de la langue préférée du navigateur parmi celles disponibles
     */
    public static function forRequest(?string $acceptLanguage, string $defaultLocale, string $directory): self
    {
        $locale = self::negotiate($acceptLanguage, Config::SUPPORTED_LOCALES, $defaultLocale);
        /** @var array<string, string> $messages */
        $messages = require $directory . '/' . $locale . '.php';
        return new self($locale, $messages);
    }

    /**
     * Choisit la langue de plus haute priorité (paramètre q) parmi celles supportées
     *
     * @param list<string> $supported
     */
    public static function negotiate(?string $acceptLanguage, array $supported, string $default): string
    {
        $best = $default;
        $bestQuality = 0.0;
        foreach (explode(',', (string)$acceptLanguage) as $part) {
            // "fr-FR;q=0.8" => langue "fr", qualité 0.8
            $pattern = '/^\s*([a-z]{1,8})(?:-[a-z0-9]{1,8})*\s*(?:;\s*q\s*=\s*([01](?:\.\d{0,3})?))?\s*$/i';
            if (!preg_match($pattern, $part, $m)) {
                continue;
            }
            $language = strtolower($m[1]);
            $quality = isset($m[2]) ? (float)$m[2] : 1.0;
            if ($quality > $bestQuality && in_array($language, $supported, true)) {
                $best = $language;
                $bestQuality = $quality;
            }
        }
        return $best;
    }

    /**
     * Traduit une clé ; les paramètres remplacent les marqueurs {nom}
     *
     * @param array<string, string|int> $params
     */
    public function t(string $key, array $params = []): string
    {
        $message = $this->messages[$key] ?? $key;
        foreach ($params as $name => $value) {
            $message = str_replace('{' . $name . '}', (string)$value, $message);
        }
        return $message;
    }
}
