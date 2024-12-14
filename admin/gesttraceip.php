<?php
define('ADM', 10); // Compatibility only
$admtxt = 'Gestion '; // Compatibility only
$bypassTIP = 1; // pas de tracing ici
require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only

$userlogin = "";
$userlevel = logonok(9);
while ($userlevel < 9) {
    login($root);
}

pathroot($root, $path, $xcomm, $xpatr, $page);

$T0 = time();
$missingargs = true;
$emailfound = false;
$cptok = 0;
$cptko = 0;

ob_start();
open_page("Gestion du filtrage IP", $root);
navadmin($root, "Gestion du filtrage IP");
zone_menu(ADM, $userlevel, array());//ADMIN STANDARD
echo '<div id="col_main_adm">';
menu_software('F');
admin_traceip();
echo '</div>';
include(__DIR__ . '/../templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
