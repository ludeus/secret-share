<?php

/**
 * Résultat de la création : lien, QR code et lien de révocation
 *
 * @var \SecretShare\View\View          $this
 * @var string                          $shareUrl
 * @var string                          $revokeUrl
 * @var string|null                     $qrCode
 * @var \SecretShare\Secret\CreatedSecret $created
 */

?>
<section class="card">
    <h1><?= $this->t('created.title') ?></h1>
    <p class="lead"><?= $this->t('created.lead') ?></p>

    <?php if ($qrCode !== null) : ?>
        <figure class="qr">
            <img src="<?= $this->e($qrCode) ?>" alt="<?= $this->t('created.qr_alt') ?>" width="240" height="240">
        </figure>
    <?php else : ?>
        <div class="alert alert-info"><?= $this->t('created.qr_too_long') ?></div>
    <?php endif; ?>

    <div class="field">
        <label for="share-url"><?= $this->t('created.share_url') ?></label>
        <div class="input-group">
            <input type="text" id="share-url" value="<?= $this->e($shareUrl) ?>" readonly
                   class="mono" spellcheck="false" data-select-on-focus>
            <button type="button" class="btn btn-primary" data-copy="share-url"
                    data-label-copied="<?= $this->t('action.copied') ?>">
                <?= $this->t('action.copy') ?>
            </button>
        </div>
    </div>

    <dl class="summary">
        <div>
            <dt><?= $this->t('created.expires') ?></dt>
            <dd>
                <?= $created->expiresAt === null ? $this->t('created.never') : $this->time($created->expiresAt) ?>
            </dd>
        </div>
        <div>
            <dt><?= $this->t('created.single_use') ?></dt>
            <dd><?= $this->t($created->singleUse ? 'common.yes' : 'common.no') ?></dd>
        </div>
        <div>
            <dt><?= $this->t('created.password') ?></dt>
            <dd><?= $this->t($created->passwordProtected ? 'common.yes' : 'common.no') ?></dd>
        </div>
        <div>
            <dt><?= $this->t('created.storage') ?></dt>
            <dd><?= $this->t($created->shortUrl ? 'created.storage_server' : 'created.storage_link') ?></dd>
        </div>
    </dl>

    <div class="alert alert-warning">
        <?= $this->t('created.warning_once') ?>
        <?php if ($created->passwordProtected) : ?>
            <?= $this->t('created.warning_password') ?>
        <?php endif; ?>
    </div>

    <details class="revoke">
        <summary><?= $this->t('created.revoke_title') ?></summary>
        <p class="hint"><?= $this->t('created.revoke_hint') ?></p>
        <div class="input-group">
            <input type="text" id="revoke-url" value="<?= $this->e($revokeUrl) ?>" readonly
                   class="mono" spellcheck="false" aria-label="<?= $this->t('created.revoke_title') ?>"
                   data-select-on-focus>
            <button type="button" class="btn btn-secondary" data-copy="revoke-url"
                    data-label-copied="<?= $this->t('action.copied') ?>">
                <?= $this->t('action.copy') ?>
            </button>
        </div>
    </details>

    <a class="btn btn-secondary btn-block" href="/"><?= $this->t('action.new_secret') ?></a>
</section>
