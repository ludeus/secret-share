<?php

/**
 * Formulaire de création d'un secret
 *
 * @var View                  $this
 * @var string                $csrfToken
 * @var list<Duration>        $durations
 * @var int                   $maxSecretLength
 * @var int                   $maxPasswordLength
 * @var array<string, string> $old
 * @var array<string, string> $errors
 */

use SecretShare\Secret\Duration;
use SecretShare\View\View;

$fieldError = function (string $name) use ($errors, $maxSecretLength): string {
    $message = isset($errors[$name]) ? $this->text($errors[$name], ['max' => $maxSecretLength]) : '';
    return sprintf(
        '<p class="field-error" id="%s-error" data-error-for="%s" %s>%s</p>',
        $name,
        $name,
        $message === '' ? ' hidden' : '',
        $message
    );
};
$invalid = fn (string $name): string => isset($errors[$name])
    ? ' aria-invalid="true" aria-describedby="' . $name . '-error"'
    : '';
$shortUrlAllowed = $old['single_use'] === '1' || $old['duration'] !== 'none';
?>
<section class="card">
    <h1><?= $this->text('home.title') ?></h1>
    <p class="lead"><?= $this->text('home.lead') ?></p>

    <?php if ($errors !== []) : ?>
        <div class="alert alert-error" role="alert"><?= $this->text('home.errors') ?></div>
    <?php endif; ?>

    <form method="post" action="/" id="share-form" class="form" novalidate
          data-max-length="<?= $this->e($maxSecretLength) ?>"
          data-msg-secret-required="<?= $this->t('error.secret_required') ?>"
          data-msg-secret-too-long="<?= $this->t('error.secret_too_long', ['max' => $maxSecretLength]) ?>"
          data-msg-password-too-long="<?= $this->t('error.password_too_long') ?>"
          data-msg-short-url-forbidden="<?= $this->t('error.short_url_forbidden') ?>"
          data-i18n-data-msg-secret-required="<?= $this->message('error.secret_required') ?>"
          data-i18n-data-msg-secret-too-long="<?= $this->message('error.secret_too_long', ['max' => $maxSecretLength]) ?>"
          data-i18n-data-msg-password-too-long="<?= $this->message('error.password_too_long') ?>"
          data-i18n-data-msg-short-url-forbidden="<?= $this->message('error.short_url_forbidden') ?>">
        <input type="hidden" name="csrf_token" value="<?= $this->e($csrfToken) ?>">

        <div class="field">
            <label for="secret"><?= $this->text('home.secret') ?></label>
            <textarea id="secret" name="secret" rows="5" required dir="auto"
                      maxlength="<?= $this->e($maxSecretLength) ?>"
                      autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false"
                      placeholder="<?= $this->t('home.secret_placeholder') ?>"
                      <?= $invalid('secret') ?> data-i18n-placeholder="<?= $this->message('home.secret_placeholder') ?>"><?= $this->e($old['secret']) ?></textarea>
            <div class="field-meta">
                <?= $fieldError('secret') ?>
                <span class="counter" id="secret-counter" aria-hidden="true"></span>
            </div>
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="duration"><?= $this->text('home.duration') ?></label>
                <select id="duration" name="duration"<?= $invalid('duration') ?>>
                    <?php foreach ($durations as $duration) : ?>
                        <option value="<?= $this->e($duration->value) ?>"
                            <?= $old['duration'] === $duration->value ? 'selected' : '' ?> data-i18n="<?= $this->message($duration->labelKey()) ?>">
                            <?= $this->t($duration->labelKey()) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= $fieldError('duration') ?>
            </div>

            <div class="field">
                <label for="password">
                    <?= $this->text('home.password') ?>
                    <span class="optional"><?= $this->text('home.optional') ?></span>
                </label>
                <div class="input-group">
                    <input type="password" id="password" name="password" autocomplete="new-password" dir="auto"
                           maxlength="<?= $this->e($maxPasswordLength) ?>"
                           aria-describedby="password-hint"<?= $invalid('password') ?>>
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
                <p class="hint" id="password-hint"><?= $this->text('home.password_hint') ?></p>
                <?= $fieldError('password') ?>
            </div>
        </div>

        <fieldset class="options">
            <legend class="visually-hidden"><?= $this->text('home.options') ?></legend>

            <div class="check">
                <input type="checkbox" id="single_use" name="single_use" value="1"
                    <?= $old['single_use'] === '1' ? 'checked' : '' ?>>
                <div>
                    <label for="single_use"><?= $this->text('home.single_use') ?></label>
                    <p class="hint"><?= $this->text('home.single_use_hint') ?></p>
                </div>
            </div>

            <div class="check">
                <input type="checkbox" id="short_url" name="short_url" value="1"
                       aria-describedby="short-url-hint short-url-disabled"
                    <?= $old['short_url'] === '1' && $shortUrlAllowed ? 'checked' : '' ?>
                    <?= $shortUrlAllowed ? '' : 'disabled' ?><?= $invalid('short_url') ?>>
                <div>
                    <label for="short_url"><?= $this->text('home.short_url') ?></label>
                    <p class="hint" id="short-url-hint"><?= $this->text('home.short_url_hint') ?></p>
                    <p class="hint hint-warning" id="short-url-disabled"<?= $shortUrlAllowed ? ' hidden' : '' ?>>
                        <?= $this->text('home.short_url_disabled') ?>
                    </p>
                    <?= $fieldError('short_url') ?>
                </div>
            </div>
        </fieldset>

        <button type="submit" class="btn btn-primary btn-block">
            <?= $this->text('home.submit') ?>
        </button>
    </form>
</section>

<section class="features" aria-label="<?= $this->t('home.how_title') ?>"
data-i18n-aria-label="<?= $this->message('home.how_title') ?>">
    <div class="feature">
        <h2><?= $this->text('home.feature_encrypted_title') ?></h2>
        <p><?= $this->text('home.feature_encrypted') ?></p>
    </div>
    <div class="feature">
        <h2><?= $this->text('home.feature_split_title') ?></h2>
        <p><?= $this->text('home.feature_split') ?></p>
    </div>
    <div class="feature">
        <h2><?= $this->text('home.feature_traceless_title') ?></h2>
        <p><?= $this->text('home.feature_traceless') ?></p>
    </div>
</section>
