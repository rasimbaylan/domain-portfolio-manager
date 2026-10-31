<?php

declare(strict_types=1);

/** @var int $status */
/** @var string $message */
?>
<div class="error-page">
    <span class="error-code"><?= e($status) ?></span>
    <h1>Bir sorun oluştu</h1>
    <p class="muted"><?= e($message) ?></p>
    <a class="btn btn--primary" href="<?= e(url('domains')) ?>">Portföye dön</a>
</div>
