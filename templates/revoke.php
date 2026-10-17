<?php

/**
 * Confirmation de la destruction d'un secret
 *
 * @var \SecretShare\View\View $this
 * @var string                 $csrfToken
 */

?>
<section class="card">
    <h1><?= $this->t('revoke.title') ?></h1>
    <p class="lead"><?= $this->t('revoke.lead') ?></p>

    <noscript>
        <div class="alert alert-error"><?= $this->t('reveal.noscript') ?></div>
    </noscript>
    <div class="alert alert-error" role="alert" data-missing-token hidden><?= $this->t('reveal.missing_token') ?></div>

    <form method="post" action="/r" class="form" data-fragment-form>
        <input type="hidden" name="csrf_token" value="<?= $this->e($csrfToken) ?>">
        <input type="hidden" name="token" value="" data-fragment-token>
        <button type="submit" class="btn btn-danger btn-block"><?= $this->t('revoke.submit') ?></button>
    </form>
</section>
