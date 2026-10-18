<?php

/**
 * Page d'erreur générique
 *
 * @var View $this
 * @var string                 $messageKey
 * @var int                    $status
 */

use SecretShare\View\View;

?>
<section class="card card-centered">
    <h1><?= $this->text('error.title_' . ($status === 404 || $status === 410 ? 'gone' : 'generic')) ?></h1>
    <p class="lead"><?= $this->text($messageKey) ?></p>
    <a class="btn btn-secondary" href="/"><?= $this->text('action.home') ?></a>
</section>
