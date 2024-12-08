<?php
define('ADM', 0);
require(__DIR__ . '/tools/_COMMUN_env.inc.php');

$xcomm = $xpatr = $page = "";
pathroot($root, $path, $xcomm, $xpatr, $page);

$userlogin = "";
$userlevel = logonok(1);

open_page(SITENAME . " : Dépouillement d'actes de l'état-civil et des registres paroissiaux", $root, null, null, null, '../index.htm', 'rss.php');
navigation($root, 2, 'A', "Conditions d'accès");

zone_menu(0, 0);

echo '<div id="col_main">';
echo "<h2>Conditions d'accès aux détails des données</h2>";
require(__DIR__ . '/_config/commentaire.htm');

echo '</div>';
close_page(1, $root);
