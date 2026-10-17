<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\View;

/**
 * Langues disponibles : chaque fichier lang/<code>.php en est une, il suffit
 * donc d'ajouter un fichier pour proposer une nouvelle langue. Les textes
 * absents d'une traduction sont affichés dans la langue de référence.
 */
final class LanguageCatalog
{
    /**
     * Langue de référence : complète, elle sert de repli pour les textes non traduits
     */
    public const string REFERENCE = 'en';

    /**
     * @var array<string, string>|null code => nom de la langue dans cette langue
     */
    private ?array $languages = null;

    public function __construct(private readonly string $directory)
    {
    }

    /**
     * Langues disponibles, triées par code
     *
     * @return array<string, string> code => nom natif (« Français », « 中文 »…)
     */
    public function languages(): array
    {
        if ($this->languages === null) {
            $this->languages = [];
            foreach (glob($this->directory . '/*.php') ?: [] as $file) {
                $code = basename($file, '.php');
                if (self::isValidCode($code)) {
                    $this->languages[$code] = $this->messages($code)['lang.name'] ?? $code;
                }
            }
            ksort($this->languages);
        }
        return $this->languages;
    }

    /**
     * Libellés du sélecteur de langue : drapeau puis nom natif (« 🇫🇷 Français »)
     *
     * @return array<string, string>
     */
    public function labels(): array
    {
        $labels = [];
        foreach ($this->languages() as $code => $name) {
            $labels[$code] = trim(($this->messages($code)['lang.flag'] ?? '') . ' ' . $name);
        }
        return $labels;
    }

    public function has(string $code): bool
    {
        return self::isValidCode($code) && is_file($this->file($code));
    }

    /**
     * Textes d'une langue, tels que définis dans son fichier
     *
     * @return array<string, string>
     */
    public function messages(string $code): array
    {
        if (!$this->has($code)) {
            return [];
        }
        $loaded = require $this->file($code);
        $messages = [];
        foreach (is_array($loaded) ? $loaded : [] as $key => $text) {
            if (is_string($text)) {
                $messages[(string)$key] = $text;
            }
        }
        return $messages;
    }

    /**
     * Traducteur d'une langue, complété par la langue de référence
     */
    public function translator(string $code): Translator
    {
        $messages = $this->messages($code);
        if ($code !== self::REFERENCE) {
            $messages += $this->messages(self::REFERENCE);
        }
        return new Translator($code, $messages);
    }

    /**
     * Code ISO 639-1 (ou 639-3) en minuscules : « fr », « zh »…
     */
    public static function isValidCode(string $code): bool
    {
        return preg_match('/^[a-z]{2,3}$/', $code) === 1;
    }

    private function file(string $code): string
    {
        return $this->directory . '/' . $code . '.php';
    }
}
