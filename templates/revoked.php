<?php

/**
 * Résultat de la destruction d'un secret
 *
 * @var \SecretShare\View\View $this
 * @var bool                   $revoked
 */

?>
<section class="card card-centered">
    <h1><?= $this->t($revoked ? 'revoked.title' : 'revoked.not_found_title') ?></h1>
    <p class="lead"><?= $this->t($revoked ? 'revoked.lead' : 'revoked.not_found') ?></p>
    <a class="btn btn-secondary" href="/"><?= $this->t('action.new_secret') ?></a>
</section>
