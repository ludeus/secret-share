<?php

/**
 * Affichage du secret déchiffré
 *
 * @var View             $this
 * @var RevealedSecret $revealed
 */

use SecretShare\Secret\RevealedSecret;
use SecretShare\View\View;

$rows = min(max(substr_count($revealed->secret, "\n") + 1, 3), 16);
?>
<section class="card">
    <h1><?= $this->t('revealed.title') ?></h1>

    <div class="field">
        <label for="secret" class="visually-hidden"><?= $this->t('revealed.label') ?></label>
        <textarea id="secret" class="mono secret-output" rows="<?= $this->e($rows) ?>" readonly dir="auto"
                  spellcheck="false" autocomplete="off"
                  data-select-on-focus><?= $this->e($revealed->secret) ?></textarea>
    </div>
    <button type="button" class="btn btn-primary btn-block" data-copy="secret"
            data-label-copied="<?= $this->t('action.copied') ?>">
        <?= $this->t('action.copy_secret') ?>
    </button>

    <?php if ($revealed->destroyed) : ?>
        <div class="alert alert-warning"><?= $this->t('revealed.destroyed') ?></div>
    <?php elseif ($revealed->expiresAt !== null) : ?>
        <div class="alert alert-info">
            <?= $this->t('revealed.expires') ?> <?= $this->time($revealed->expiresAt) ?>.
        </div>
    <?php else : ?>
        <div class="alert alert-info"><?= $this->t('revealed.no_expiry') ?></div>
    <?php endif; ?>

    <a class="btn btn-secondary btn-block" href="/"><?= $this->t('action.share_own') ?></a>
</section>
