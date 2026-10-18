<?php

/**
 * Gabarit principal
 *
 * @var View $this
 * @var string                  $content
 * @var string                  $sourceUrl
 * @var string                  $version
 * @var array<string, string>   $languages libellés (drapeau et nom) par code de langue
 * @var bool                    $languageReloadSafe
 * @var string                  $currentPath
 */

use SecretShare\View\View;

?>
<!doctype html>
<html lang="<?= $this->e($this->locale()) ?>" dir="<?= $this->e($this->direction()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#f5f6f8" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0e1014" media="(prefers-color-scheme: dark)">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <meta name="description" content="<?= $this->t('app.tagline') ?>"
    data-i18n-content="<?= $this->message('app.tagline') ?>">
    <title data-i18n="<?= $this->message('app.name') ?>"><?= $this->t('app.name') ?></title>
    <link rel="icon" href="<?= $this->asset('assets/favicon.svg') ?>" type="image/svg+xml">
    <script src="<?= $this->asset('assets/theme.js') ?>"></script>
    <link rel="stylesheet" href="<?= $this->asset('assets/app.css') ?>">
    <script src="<?= $this->asset('assets/app.js') ?>" defer></script>
</head>
<body>
    <a class="skip-link" href="#main"><?= $this->text('app.skip_to_content') ?></a>
    <header class="site-header">
        <a class="brand" href="/">
            <img class="brand-icon" src="<?= $this->asset('assets/favicon.svg') ?>"
                 width="28" height="28" alt="" aria-hidden="true">
            <span><?= $this->text('app.name') ?></span>
        </a>
        <div class="header-controls">
            <div class="theme-switch" data-theme-switch hidden>
                <button type="button" class="theme-trigger" aria-expanded="false"
                        aria-controls="theme-options" aria-label="<?= $this->t('app.theme') ?>"
                        data-i18n-aria-label="<?= $this->message('app.theme') ?>">
                    <svg data-theme-icon="auto" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"/><path d="M12 3a9 9 0 0 0 0 18Z" fill="currentColor" stroke="none"/></svg>
                    <svg data-theme-icon="light" hidden viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.93 4.93l1.42 1.42M17.65 17.65l1.42 1.42M4.93 19.07l1.42-1.42M17.65 6.35l1.42-1.42"/></svg>
                    <svg data-theme-icon="dark" hidden viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20.5 14.1A8.75 8.75 0 0 1 9.9 3.5a8.75 8.75 0 1 0 10.6 10.6Z"/></svg>
                    <svg class="theme-caret" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="m6 9 6 6 6-6"/>
                    </svg>
                </button>
                <div id="theme-options" class="theme-options" role="group"
                     aria-label="<?= $this->t('app.theme') ?>" hidden data-i18n-aria-label="<?= $this->message('app.theme') ?>">
                    <button type="button" class="theme-option" data-theme-value="auto" aria-pressed="false">
                        <svg  viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"/><path d="M12 3a9 9 0 0 0 0 18Z" fill="currentColor" stroke="none"/></svg>
                        <span><?= $this->text('app.theme_auto') ?></span>
                    </button>
                    <button type="button" class="theme-option" data-theme-value="light" aria-pressed="false">
                        <svg  viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.93 4.93l1.42 1.42M17.65 17.65l1.42 1.42M4.93 19.07l1.42-1.42M17.65 6.35l1.42-1.42"/></svg>
                        <span><?= $this->text('app.theme_light') ?></span>
                    </button>
                    <button type="button" class="theme-option" data-theme-value="dark" aria-pressed="false">
                        <svg  viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20.5 14.1A8.75 8.75 0 0 1 9.9 3.5a8.75 8.75 0 1 0 10.6 10.6Z"/></svg>
                        <span><?= $this->text('app.theme_dark') ?></span>
                    </button>
                </div>
            </div>
        <?php if (count($languages) > 1) : ?>
            <form class="lang-switch" method="get" action="<?= $this->e($currentPath) ?>" data-lang-switch<?= $languageReloadSafe ? '' : ' hidden' ?>>
                <label for="lang-select" class="visually-hidden"><?= $this->text('app.language') ?></label>
                <select id="lang-select" name="lang">
                    <?php foreach ($languages as $code => $name) : ?>
                        <option value="<?= $this->e($code) ?>" lang="<?= $this->e($code) ?>"
                            <?= $code === $this->locale() ? 'selected' : '' ?>><?= $this->e($name) ?></option>
                    <?php endforeach; ?>
                </select>
                <noscript><button type="submit" class="btn btn-secondary">OK</button></noscript>
            </form>
        <?php endif; ?>
        </div>
    </header>
    <main id="main" class="container">
        <?= $content ?>
    </main>
    <footer class="site-footer">
        <p><?= $this->text('app.footer') ?></p>
        <p>
            <a href="<?= $this->e($sourceUrl) ?>" rel="noopener noreferrer"><?= $this->text('app.source_code') ?></a>
            · v<?= $this->e($version) ?> · AGPL-3.0
        </p>
    </footer>
    <div class="visually-hidden" aria-live="polite" id="live-region"></div>
</body>
</html>
