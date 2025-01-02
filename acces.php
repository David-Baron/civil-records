<?php

require(__DIR__ . '/next/bootstrap.php');

ob_start();
open_page('Conditions d\'acces', $root); ?>
<div class="main">
    <?php zone_menu(0, $session->get('user', ['level' => 0])['level']); ?>
    <div class="main-col-center text-center">
        <?php navigation($root, 2, 'A', "Conditions d'accès"); ?>
        <h2>Conditions d'accès aux détails des données</h2>
        <?php include(__DIR__ . '/templates/front/_commentaire.php'); ?>
    </div>
</div>
<?php include(__DIR__ . '/templates/front/_footer.php');

$response->setContent(ob_get_clean());
$response->send();
