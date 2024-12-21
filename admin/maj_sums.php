<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

define('ADM', 10); // Compatibility only
$admtxt = 'Gestion '; // Compatibility only
require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only

if (!$userAuthorizer->isGranted(6)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

pathroot($root, $path, $xtyp, $xpatr, $page);

$xtyp = "";
$xtyp = strtoupper(getparam('xtyp'));
$mode = strtoupper(getparam('mode'));
$com  = urldecode(getparam('com'));
$missingargs = true;
$emailfound = false;
$oktype = false;
$cptact = 0;
$cptfil = 0;

$menu_actes = "";
$menu_actes .= '<a href="' . $root . '/admin/maj_sums.php?xtyp=N&amp;mode=A&amp;com=">Naissances</a> | ';
$menu_actes .= '<a href="' . $root . '/admin/maj_sums.php?xtyp=M&amp;mode=A&amp;com=">Mariages</a> | ';
$menu_actes .= '<a href="' . $root . '/admin/maj_sums.php?xtyp=D&amp;mode=A&amp;com=">Décès</a> | ';
$menu_actes .= '<a href="' . $root . '/admin/maj_sums.php?xtyp=V&amp;mode=A&amp;com=">Divers</a>';

$menu_data_active = 'S';

ob_start();
open_page("Mise à jour des statistiques", $root);
navadmin($root, "Mise à jour des statistiques");
zone_menu(ADM, $session->get('user')['level'], array());//ADMIN STANDARD
echo '<div id="col_main">';
require(__DIR__ . '/../templates/admin/_menu-data.php');

echo '<h2 align="center">Mise à jour des statistiques</h2>';
echo '<p><b>' . $menu_actes . '</b></p>';

if ($xtyp == "") {
    $request = "SELECT TYPACT, max(DER_MAJ) AS DERMAJ, count(COMMUNE) AS CPTCOM "
                    . " FROM " . $config->get('EA_DB') . "_sums"
                    . " GROUP BY TYPACT"
                    . " ORDER BY INSTR('NMDV',TYPACT)"     // cette ligne permet de trier dans l'ordre voulu
    ;

    // echo $request;
    if ($result = EA_sql_query($request)) {
        while ($ligne = EA_sql_fetch_array($result)) {
            echo '<p><b>' . typact_txt($ligne['TYPACT']) . '</b> : ' . $ligne['CPTCOM'] . ' localités mises-à-jour le ' . $ligne['DERMAJ'] . '</p>';
        }
    }
    echo "<p><b>Utilisez les liens ci-dessus pour recalculer les statistiques d'un type d'actes</b></p>";
} else {
    maj_stats($xtyp, $T0, $path, $mode, $com);
}

echo '</div>';
include(__DIR__ . '/../templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
