<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

define('ADM', 0); // Compatibility only
$admtxt = ''; // Compatibility only
require(__DIR__ . '/next/bootstrap.php');
require(__DIR__ . '/next/_COMMUN_env.inc.php'); // Compatibility only

if (PUBLIC_LEVEL < 4 && !$userAuthorizer->isGranted(1)) {
    $response = new RedirectResponse("$root/login.php");
    $response->send();
    exit();
}

$xcomm = $xpatr = $page = "";

pathroot($root, $path, $xcomm, $xpatr, $page);

ob_start();
open_page(
    SITENAME . " : Dépouillement d'actes de l'état-civil et des registres paroissiaux",
    $root,
    null,
    null,
    null,
    '../index.htm',
    'rss.php'
);
navigation($root, 2, 'A', "Conditions d'accès");
zone_menu(0, 0);
?>
<div id="col_main">
    <h2>Conditions d'accès aux détails des données</h2>
    <?php include(__DIR__ . '/templates/front/_commentaire.php'); ?>
</div>
<?php include(__DIR__ . '/templates/front/_footer.php');

$response->setContent(ob_get_clean());
$response->send();
