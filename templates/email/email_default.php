<?php 
/**
 * Requirements:
 * - $sitename
 * - $urlsite
 * - $message
 */
?>
<h1>Message provenant de <a href="<?= $urlsite; ?>"><?= $sitename; ?></a></h1>
<p>
    <?= $message; ?>
</p>