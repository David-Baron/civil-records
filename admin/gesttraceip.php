<?php

define('ADM', 10);

$bypassTIP = 1; // pas de tracing ici

require(__DIR__ . '/../tools/_COMMUN_env.inc.php');

my_ob_start_affichage_continu();

$userlogin = "";
$T0 = time();

pathroot($root, $path, $xcomm, $xpatr, $page);

//print '<pre>';  print_r($_REQUEST); echo '</pre>';

$userlogin = "";
$userlevel = logonok(9);
while ($userlevel < 9) {
    login($root);
}

open_page("Gestion du filtrage IP", $root);
navadmin($root, "Gestion du filtrage IP");

zone_menu(ADM, $userlevel, array());//ADMIN STANDARD

echo '<div id="col_main_adm">';
$missingargs = true;
$emailfound = false;
$cptok = 0;
$cptko = 0;

menu_software('F');

admin_traceip();

echo '</div>';
close_page(1, $root);
