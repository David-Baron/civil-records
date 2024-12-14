<?php
define('ADM', 10); // Compatibility only
$admtxt = 'Gestion '; // Compatibility only
require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only

pathroot($root, $path, $xcomm, $xpatr, $page);

$userlogin = "";
$userlevel = logonok(7);
while ($userlevel < 7) {
    login($root);
}
$title = "Ajout d'un acte";

ob_start();
open_page($title, $root);

include("../tools/PHPLiveX/PHPLiveX.php");
$ajax = new PHPLiveX(array("getCommunes"));
$ajax->Run(false, "../tools/PHPLiveX/phplivex.js");

navadmin($root, $title);

zone_menu(ADM, $userlevel, array());//ADMIN STANDARD

echo '<div id="col_main_adm">';
menu_datas('A');

$ok = false;
$missingargs = false;
$oktype = false;
$today = today();


{
    //{ print '<pre>';  print_r($col); echo '</pre>'; }
    echo '<form method="post" action="edit_acte.php">' . "\n";
    echo '<h2 align="center">' . $title . '</h2>';
    echo '<table  align="center" cellspacing="0" cellpadding="1" border="0" summary="Formulaire">' . "\n";

    //echo " <tr><td colspan=\"2\"><h3>Acte à ajouter : </h3></td></tr>\n";
    form_typeactes_communes('', 0);
    echo " <tr>\n";

    echo " <tr><td colspan=\"2\">&nbsp;</td></tr>\n";

    echo ' <tr><td>' . "\n";
    echo '  <input type="hidden" name="action" value="submitted" />';
    echo '  <input type="hidden" name="xid" value="-1" />';
    echo '  <input type="reset" value="Annuler" />' . "\n";
    echo '</td><td><input type="submit" value=" >> AJOUTER >> " />' . "\n";
    echo "</td></tr></table>\n";
    echo "</form>\n";
}

echo '</div>';
include(__DIR__ . '/../templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
