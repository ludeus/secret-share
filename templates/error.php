<?php

/**
 * Page d'erreur générique
 *
 * @var \SecretShare\View\View $this
 * @var string                 $messageKey
 * @var int                    $status
 */

?>
<section class="card card-centered">
    <h1><?= $this->t('error.title_' . ($status === 404 || $status === 410 ? 'gone' : 'generic')) ?></h1>
    <p class="lead"><?= $this->t($messageKey) ?></p>
    <a class="btn btn-secondary" href="/"><?= $this->t('action.home') ?></a>
</section>
