<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\View;

/**
 * Traductions de l'interface dans une langue (voir LanguageCatalog)
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
     * Sens d'écriture de la langue : « rtl » pour l'arabe, l'hébreu…, « ltr » sinon
     */
    public function direction(): string
    {
        return ($this->messages['lang.dir'] ?? 'ltr') === 'rtl' ? 'rtl' : 'ltr';
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
