<?php

/**
 * Gabarit principal
 *
 * @var \SecretShare\View\View $this
 * @var string                  $content
 * @var string                  $sourceUrl
 * @var string                  $version
 */

?>
<!doctype html>
<html lang="<?= $this->e($this->locale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#f5f6f8" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0e1014" media="(prefers-color-scheme: dark)">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <meta name="description" content="<?= $this->t('app.tagline') ?>">
    <title><?= $this->t('app.name') ?></title>
    <link rel="icon" href="/assets/favicon.svg?v=<?= $this->e($version) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/app.css?v=<?= $this->e($version) ?>">
    <script src="/assets/app.js?v=<?= $this->e($version) ?>" defer></script>
</head>
<body>
    <a class="skip-link" href="#main"><?= $this->t('app.skip_to_content') ?></a>
    <header class="site-header">
        <a class="brand" href="/">
            <svg class="brand-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M7 10V7a5 5 0 0 1 10 0v3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <rect x="4" y="10" width="16" height="11" rx="2.5" fill="currentColor"/>
                <circle cx="12" cy="15.5" r="1.6" class="brand-icon-hole"/>
            </svg>
            <span><?= $this->t('app.name') ?></span>
        </a>
    </header>
    <main id="main" class="container">
        <?= $content ?>
    </main>
    <footer class="site-footer">
        <p><?= $this->t('app.footer') ?></p>
        <p>
            <a href="<?= $this->e($sourceUrl) ?>" rel="noopener noreferrer"><?= $this->t('app.source_code') ?></a>
            · v<?= $this->e($version) ?> · AGPL-3.0
        </p>
    </footer>
    <div class="visually-hidden" aria-live="polite" id="live-region"></div>
</body>
</html>
