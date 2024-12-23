<?php
foreach ($session->getFlashBag()->all() as $type => $messages) {
    foreach ($messages as $message) { ?>
        <div class="<?= $type; ?>"><?= $message; ?></div>
    <?php }
}