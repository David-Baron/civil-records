<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

define('ADM', 10); // Compatibility only
$admtxt = 'Gestion '; // Compatibility only
$bypassTIP = 1; // pas de tracing ici
require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only

if (!$userAuthorizer->isGranted(9)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

pathroot($root, $path, $xcomm, $xpatr, $page);

$T0 = time();
$missingargs = true;
$emailfound = false;
$cptok = 0;
$cptko = 0;

$menu_software_active = 'F';

ob_start();
open_page("Gestion du filtrage IP", $root); ?>
<div class="main">
    <?php zone_menu(ADM, $session->get('user')['level']); ?>
    <div class="main-col-center text-center">
        <?php
        navadmin($root, "Gestion du filtrage IP");
        require(__DIR__ . '/../templates/admin/_menu-software.php');
        admin_traceip(); ?>
    </div>
</div>
<?php include(__DIR__ . '/../templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
