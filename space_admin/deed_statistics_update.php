<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

if (!$userAuthorizer->isGranted(6)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

$xtyp = "";
$xtyp = strtoupper(getparam('xtyp'));
$mode = strtoupper(getparam('mode'));
$com  = urldecode(getparam('com'));
$missingargs = true;
$emailfound = false;
$oktype = false;
$cptact = 0;
$cptfil = 0;
$T0 = time();

$menu_actes = "";
$menu_actes .= '<a href="' . $root . '/admin/actes/statistiques?xtyp=N&amp;mode=A&amp;com=">Naissances</a> | ';
$menu_actes .= '<a href="' . $root . '/admin/actes/statistiques?xtyp=M&amp;mode=A&amp;com=">Mariages</a> | ';
$menu_actes .= '<a href="' . $root . '/admin/actes/statistiques?xtyp=D&amp;mode=A&amp;com=">Décès</a> | ';
$menu_actes .= '<a href="' . $root . '/admin/actes/statistiques?xtyp=V&amp;mode=A&amp;com=">Divers</a>';

$menu_data_active = 'S';

ob_start();
open_page("Mise à jour des statistiques", $root); ?>
<div class="main">
    <?php zone_menu(10, $session->get('user')['level']); ?>
    <div class="main-col-center text-center">
        <?php
        navadmin($root, "Mise à jour des statistiques");

        require(__DIR__ . '/../templates/admin/_menu-data.php');

        echo '<h2 align="center">Mise à jour des statistiques</h2>';
        echo '<p><b>' . $menu_actes . '</b></p>';

        if ($xtyp == "") {
            $sql = "SELECT TYPACT, max(DER_MAJ) AS DERMAJ, count(COMMUNE) AS CPTCOM "
                . " FROM " . $config->get('EA_DB') . "_sums"
                . " GROUP BY TYPACT"
                . " ORDER BY INSTR('NMDV',TYPACT)"     // cette ligne permet de trier dans l'ordre voulu
            ;

            if ($result = EA_sql_query($sql)) {
                while ($ligne = EA_sql_fetch_array($result)) {
                    echo '<p><b>' . typact_txt($ligne['TYPACT']) . '</b> : ' . $ligne['CPTCOM'] . ' localités mises-à-jour le ' . $ligne['DERMAJ'] . '</p>';
                }
            }
            echo "<p><b>Utilisez les liens ci-dessus pour recalculer les statistiques d'un type d'actes</b></p>";
        } else {
            maj_stats($xtyp, $T0, $path, $mode, $com);
        } ?>
    </div>
</div>
<?php include(__DIR__ . '/../templates/front/_footer.php');
return (ob_get_clean());
