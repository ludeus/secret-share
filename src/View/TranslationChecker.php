<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\View;

/**
 * Contrôle d'une traduction par rapport à la langue de référence :
 * textes manquants ou inconnus, paramètres {nom} différents
 */
final readonly class TranslationChecker
{
    /**
     * Clés obligatoires dans chaque fichier de langue
     */
    public const array REQUIRED_KEYS = ['lang.name', 'lang.dir'];

    public function __construct(private LanguageCatalog $catalog)
    {
    }

    /**
     * Problèmes détectés dans une langue (liste vide si la traduction est complète)
     *
     * @return list<string>
     */
    public function check(string $code): array
    {
        $reference = $this->catalog->messages(LanguageCatalog::REFERENCE);
        $messages = $this->catalog->messages($code);
        $problems = [];

        foreach (self::REQUIRED_KEYS as $key) {
            if (!isset($messages[$key])) {
                $problems[] = sprintf('clé obligatoire absente : %s', $key);
            }
        }
        if (isset($messages['lang.dir']) && !in_array($messages['lang.dir'], ['ltr', 'rtl'], true)) {
            $problems[] = 'lang.dir doit valoir « ltr » ou « rtl »';
        }
        foreach ($reference as $key => $text) {
            if (in_array($key, self::REQUIRED_KEYS, true)) {
                continue;
            }
            if (!isset($messages[$key])) {
                $problems[] = sprintf('texte manquant : %s', $key);
            } elseif (self::placeholders($text) !== self::placeholders($messages[$key])) {
                $problems[] = sprintf(
                    'paramètres différents pour %s : attendu %s',
                    $key,
                    implode(', ', self::placeholders($text)) ?: 'aucun'
                );
            }
        }
        foreach (array_diff_key($messages, $reference) as $key => $text) {
            $problems[] = sprintf('texte inconnu de la langue de référence : %s', $key);
        }
        return $problems;
    }

    /**
     * Paramètres {nom} d'un texte, triés
     *
     * @return list<string>
     */
    private static function placeholders(string $text): array
    {
        preg_match_all('/\{([a-z_]+)\}/', $text, $matches);
        $names = array_values(array_unique($matches[1]));
        sort($names);
        return $names;
    }
}
