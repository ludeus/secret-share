<?php

/**
 * Résultat de la destruction d'un secret
 *
 * @var View $this
 * @var bool                   $revoked
 */

use SecretShare\View\View;

?>
<section class="card card-centered">
    <h1><?= $this->text($revoked ? 'revoked.title' : 'revoked.not_found_title') ?></h1>
    <p class="lead"><?= $this->text($revoked ? 'revoked.lead' : 'revoked.not_found') ?></p>
    <a class="btn btn-secondary" href="/"><?= $this->text('action.new_secret') ?></a>
</section>
