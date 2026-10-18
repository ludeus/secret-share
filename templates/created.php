<?php

/**
 * Résultat de la création : lien, QR code et lien de révocation
 *
 * @var View          $this
 * @var string                          $shareUrl
 * @var string                          $revokeUrl
 * @var string|null                     $qrCode
 * @var CreatedSecret $created
 */

use SecretShare\Secret\CreatedSecret;
use SecretShare\View\View;

?>
<section class="card">
    <h1><?= $this->text('created.title') ?></h1>
    <p class="lead"><?= $this->text('created.lead') ?></p>

    <?php if ($qrCode !== null) : ?>
        <figure class="qr">
            <img src="<?= $this->e($qrCode) ?>" alt="<?= $this->t('created.qr_alt') ?>" width="240" height="240"
            data-i18n-alt="<?= $this->message('created.qr_alt') ?>">
        </figure>
    <?php else : ?>
        <div class="alert alert-info"><?= $this->text('created.qr_too_long') ?></div>
    <?php endif; ?>

    <div class="field">
        <label for="share-url"><?= $this->text('created.share_url') ?></label>
        <div class="input-group">
            <input type="text" id="share-url" value="<?= $this->e($shareUrl) ?>" readonly dir="ltr"
                   class="mono" spellcheck="false" data-select-on-focus>
            <button type="button" class="btn btn-primary" data-copy="share-url"
                    data-label-copied="<?= $this->t('action.copied') ?>"
                    data-i18n-data-label-copied="<?= $this->message('action.copied') ?>">
                <?= $this->text('action.copy') ?>
            </button>
        </div>
    </div>

    <dl class="summary">
        <div>
            <dt><?= $this->text('created.expires') ?></dt>
            <dd>
                <?= $created->expiresAt === null ? $this->text('created.never') : $this->time($created->expiresAt) ?>
            </dd>
        </div>
        <div>
            <dt><?= $this->text('created.single_use') ?></dt>
            <dd><?= $this->text($created->singleUse ? 'common.yes' : 'common.no') ?></dd>
        </div>
        <div>
            <dt><?= $this->text('created.password') ?></dt>
            <dd><?= $this->text($created->passwordProtected ? 'common.yes' : 'common.no') ?></dd>
        </div>
        <div>
            <dt><?= $this->text('created.storage') ?></dt>
            <dd><?= $this->text($created->shortUrl ? 'created.storage_server' : 'created.storage_link') ?></dd>
        </div>
    </dl>

    <div class="alert alert-warning">
        <?= $this->text('created.warning_once') ?>
        <?php if ($created->passwordProtected) : ?>
            <?= $this->text('created.warning_password') ?>
        <?php endif; ?>
    </div>

    <details class="revoke">
        <summary><?= $this->text('created.revoke_title') ?></summary>
        <p class="hint"><?= $this->text('created.revoke_hint') ?></p>
        <div class="input-group">
            <input type="text" id="revoke-url" value="<?= $this->e($revokeUrl) ?>" readonly dir="ltr"
                   class="mono" spellcheck="false" aria-label="<?= $this->t('created.revoke_title') ?>"
                   data-select-on-focus data-i18n-aria-label="<?= $this->message('created.revoke_title') ?>">
            <button type="button" class="btn btn-secondary" data-copy="revoke-url"
                    data-label-copied="<?= $this->t('action.copied') ?>"
                    data-i18n-data-label-copied="<?= $this->message('action.copied') ?>">
                <?= $this->text('action.copy') ?>
            </button>
        </div>
    </details>

    <a class="btn btn-secondary btn-block" href="/"><?= $this->text('action.new_secret') ?></a>
</section>
