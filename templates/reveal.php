<?php

/**
 * Ouverture d'un secret : le jeton est lu dans le fragment de l'URL par le JavaScript
 *
 * @var View $this
 * @var string                 $csrfToken
 * @var string                 $token
 * @var bool|null              $passwordRequired
 * @var int|null               $remainingAttempts
 */

use SecretShare\View\View;

$passwordRequired ??= false;
$remainingAttempts ??= null;
?>
<section class="card">
    <h1><?= $this->text('reveal.title') ?></h1>
    <p class="lead"><?= $this->text('reveal.lead') ?></p>

    <noscript>
        <div class="alert alert-error"><?= $this->text('reveal.noscript') ?></div>
    </noscript>
    <div class="alert alert-error" role="alert" data-missing-token hidden><?= $this->text('reveal.missing_token') ?></div>

    <form method="post" action="/s" class="form" data-fragment-form>
        <input type="hidden" name="csrf_token" value="<?= $this->e($csrfToken) ?>">
        <input type="hidden" name="token" value="<?= $this->e($token) ?>" data-fragment-token>

        <?php if ($passwordRequired) : ?>
            <?php if ($remainingAttempts !== null) : ?>
                <div class="alert alert-error" role="alert">
                    <?= $this->text('reveal.wrong_password', ['count' => $remainingAttempts]) ?>
                </div>
            <?php endif; ?>
            <div class="field">
                <label for="password"><?= $this->text('reveal.password') ?></label>
                <div class="input-group">
                    <input type="password" id="password" name="password" required autofocus
                           autocomplete="off" maxlength="1024"
                        <?= $remainingAttempts !== null ? 'aria-invalid="true"' : '' ?>>
                    <button type="button" class="btn btn-ghost btn-icon" data-toggle-password="password"
                            aria-pressed="false" title="<?= $this->t('action.show_password') ?>"
                            data-label-show="<?= $this->t('action.show_password') ?>"
                            data-label-hide="<?= $this->t('action.hide_password') ?>"
                            data-i18n-title="<?= $this->message('action.show_password') ?>"
                            data-i18n-data-label-show="<?= $this->message('action.show_password') ?>"
                            data-i18n-data-label-hide="<?= $this->message('action.hide_password') ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <span class="visually-hidden"><?= $this->text('action.show_password') ?></span>
                    </button>
                </div>
                <p class="hint"><?= $this->text('reveal.password_hint') ?></p>
            </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary btn-block"><?= $this->text('reveal.submit') ?></button>
    </form>
</section>
