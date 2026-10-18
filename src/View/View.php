<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\View;

use RuntimeException;
use Throwable;

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
        private string $publicDirectory = '',
    ) {
    }

    /**
     * URL d'un fichier statique, suffixée de sa date de modification : le
     * navigateur peut le garder en cache et le recharge dès qu'il change
     */
    public function asset(string $path): string
    {
        $mtime = @filemtime($this->publicDirectory . '/' . $path);
        $url = '/' . ltrim($path, '/');
        return $this->e($mtime === false ? $url : $url . '?v=' . $mtime);
    }

    /**
     * @param array<string, mixed> $vars
     * @throws Throwable
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

    /**
     * Métadonnées de traduction destinées au navigateur, échappées pour un attribut HTML.
     *
     * @param array<string, string|int> $params
     */
    public function message(string $key, array $params = []): string
    {
        return $this->e(json_encode([$key, (object)$params], JSON_THROW_ON_ERROR));
    }

    /**
     * Texte traduisible sans remplacer son élément parent ni ses voisins.
     *
     * @param array<string, string|int> $params
     */
    public function text(string $key, array $params = []): string
    {
        return '<span data-i18n="' . $this->message($key, $params) . '">'
            . $this->t($key, $params) . '</span>';
    }

    public function locale(): string
    {
        return $this->translator->locale;
    }

    /**
     * Sens d'écriture de la langue courante (attribut dir)
     */
    public function direction(): string
    {
        return $this->translator->direction();
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
     * @throws Throwable
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
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string)ob_get_clean();
    }
}
