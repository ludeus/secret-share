<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\View;

use RuntimeException;

/**
 * Rendu de gabarits PHP natifs avec échappement explicite.
 * Chaque gabarit est rendu à l'intérieur de templates/layout.php.
 */
final readonly class View
{
    /**
     * @param array<string, mixed> $globals variables disponibles dans tous les gabarits
     */
    public function __construct(
        private string $directory,
        private Translator $translator,
        private array $globals = [],
    ) {
    }

    /**
     * @param array<string, mixed> $vars
     */
    public function render(string $template, array $vars = []): string
    {
        $vars += $this->globals;
        $content = $this->renderFile($template, $vars);
        return $this->renderFile('layout', $vars + ['content' => $content]);
    }

    /**
     * Échappe une valeur pour un contexte HTML (texte ou attribut entre guillemets)
     */
    public function e(string|int|null $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    /**
     * Traduit puis échappe
     *
     * @param array<string, string|int> $params
     */
    public function t(string $key, array $params = []): string
    {
        return $this->e($this->translator->t($key, $params));
    }

    public function locale(): string
    {
        return $this->translator->locale;
    }

    /**
     * Date ISO 8601 (UTC) affichée en heure locale par le navigateur
     */
    public function time(int $timestamp): string
    {
        $iso = gmdate('Y-m-d\TH:i:s\Z', $timestamp);
        return sprintf(
            '<time datetime="%s" data-local-time>%s</time>',
            $this->e($iso),
            $this->e(gmdate('Y-m-d H:i', $timestamp) . ' UTC')
        );
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function renderFile(string $template, array $vars): string
    {
        $file = $this->directory . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new RuntimeException(sprintf('Gabarit introuvable : %s', $template));
        }
        $render = function (string $__file, array $__vars): void {
            extract($__vars, EXTR_SKIP);
            require $__file;
        };
        ob_start();
        try {
            $render($file, $vars);
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string)ob_get_clean();
    }
}
