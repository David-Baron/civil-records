<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

define('ADM', 0); // Compatibility only
$admtxt = ''; // Compatibility only
require(__DIR__ . '/next/bootstrap.php');
require(__DIR__ . '/next/_COMMUN_env.inc.php'); // Compatibility only


$xcomm = "";
$xpatr = "";
$xord  = $request->get('xord', 'D'); // N = Nom, D = dates
$page = $request->get('page', 1);

$comdep  = html_entity_decode($xcomm, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
$commune = communede($comdep);
$departement  = departementde($comdep);

$xannee = "";
if (mb_substr($xpatr, 0, 1) == "!") {
    $xannee = mb_substr($xpatr, 1);
}

$gid = 0;
$note = geoNote($commune, $departement, 'N');

if (($xpatr == "" or mb_substr($xpatr, 0, 1) == "_")) {
    // Lister les patronymes avec groupements si trop nombreux
    if (!$config->get('PUBLIC_LEVEL') >= 3 || !$userAuthorizer->isGranted(2)) {
        $session->getFlashBag()->add('warning', 'Vous n\'êtes pas connecté ou vous n\'avez pas les autorisations nécessaires!');
        $response = new RedirectResponse("$root/");
        $response->send();
        exit();
    }
    ob_start();
    open_page($xcomm . " : " . $admtxt . "Naissances/Baptêmes", $root); ?>
    <div class="main">
        <?php zone_menu(0, $session->get('user', ['level' => 0])['level']); ?>
        <div class="main-col-center text-center">
            <?php 
    navigation($root, 2, 'N', $xcomm);

    liste_patro_1('tab_naiss.php', $path, $xcomm, $xpatr, "Naissances / Baptêmes", $config->get('EA_DB') . "_nai3", $gid, $note);
} else {
    // Lister les actes
    if (!$config->get('PUBLIC_LEVEL') >= 3 || !$userAuthorizer->isGranted(3)) {
        $session->getFlashBag()->add('warning', 'Vous n\'êtes pas connecté ou vous n\'avez pas les autorisations nécessaires!');
        $response = new RedirectResponse("$root/");
        $response->send();
        exit();
    }

    ob_start();
    open_page($xcomm . " : " . $admtxt . "Table des naissances/baptêmes", $root); ?>
    <div class="main">
        <?php zone_menu(0, $session->get('user', ['level' => 0])['level']); ?>
        <div class="main-col-center text-center">
            <?php 
    navigation($root, 2, 'N', $xcomm, $xpatr);
    echo '<h2>Actes de naissance/baptême</h2>';
    echo '<p>';

    echo 'Commune/Paroisse : <a href="' . mkurl($path . '/tab_naiss.php', $xcomm) . '"><b>' . $xcomm . '</b></a>' . geoUrl($gid) . '<br />';
    if ($note <> '') {
        echo "</p><p>" . $note . "</p><p>";
    }
    if (mb_substr($xpatr, 0, 1) == "!") {
        echo 'Année : <b>' . $xannee . '</b>';
        $preorder = "act.NOM";
        $nameorder = "Patronymes";
    } else {
        echo 'Patronyme : <b>' . $xpatr . '</b>';
        $preorder = "PRE";
        $nameorder = "Prénoms";
    }

    echo '</p>';

    if ($xord == "N") {
        $order = $preorder . ", LADATE";
        $hdate = '<a href="' . mkurl($path . '/tab_naiss.php', $xcomm, $xpatr, 'xord=D') . '">Dates</a>';
        $baselink = mkurl($path . '/tab_naiss.php', $xcomm, $xpatr, 'xord=N');
        $hnoms = '<b>' . $nameorder . '</b>';
    } else {
        $order = "LADATE, " . $preorder;
        $hnoms = '<a href="' . mkurl($path . '/tab_naiss.php', $xcomm, $xpatr, 'xord=N') . '">' . $nameorder . '</a>';
        $baselink = mkurl($path . '/tab_naiss.php', $xcomm, $xpatr, 'xord=D');
        $hdate = '<b>Dates</b>';
    }
    if ($xannee <> "") {
        $condit = " AND year(act.LADATE)=" . $xannee;
    } else {
        $condit = " AND act.NOM = '" . sql_quote($xpatr) . "'";
    }

    if ($departement <> "") {
        $condDep = " AND DEPART = '" . sql_quote($departement) . "'";
    } else {
        $condDep = "";
    }

    $sql = "SELECT act.NOM, act.PRE, DATETXT, act.ID, act.DEPOSANT"
                . " FROM " . $config->get('EA_DB') . "_nai3 AS act"
                . " WHERE COMMUNE = '" . sql_quote($commune) . "'" . $condDep
                . $condit . " ORDER BY " . $order;
    $result = EA_sql_query($sql);
    $nbtot = EA_sql_num_rows($result);

    $limit = "";
    $listpages = "";
    pagination($nbtot, $page, $baselink, $listpages, $limit);

    if ($limit <> "") {
        $sql = $sql . $limit;
        $result = EA_sql_query($sql);
        $nb = EA_sql_num_rows($result);
    } else {
        $nb = $nbtot;
    }

    if ($nb > 0) {
        if ($listpages <> "") {
            echo '<p>' . $listpages . '</p>';
        }
        $i = 1 + ($page - 1) * iif((ADM > 0), $config->get('MAX_PAGE_ADM'), $config->get('MAX_PAGE'));
        echo '<table class="m-auto" summary="Liste des patronymes">';
        echo '<tr class="rowheader">';
        echo '<th> Tri : </th>';
        echo '<th>' . $hdate . '</th>';
        echo '<th>' . $hnoms . '</th>';
        if (ADM == 10) {
            echo '<th>Déposant</th>';
        }
        echo '</tr>';

        while ($ligne = EA_sql_fetch_row($result)) {
            echo '<tr class="row' . (fmod($i, 2)) . '">';
            echo '<td>' . $i . '. </td>';
            echo '<td>' . annee_seulement($ligne[2]) . '</td>';
            echo '<td><a href="' . $path . '/acte_naiss.php?xid=' . $ligne[3] . '&amp;xct=' . ctrlxid($ligne[0], $ligne[1]) . '">' . $ligne[0] . ' ' . $ligne[1] . '</a></td>';
            if (ADM == 10) {
                actions_deposant($session->get('user')['ID'], $ligne[4], $ligne[3], 'N');
            }
            echo '</tr>';
            $i++;
        }
        echo '</table>';
        if ($listpages <> "") {
            echo '<p>' . $listpages . '</p>';
        }
        show_solde();
    } else {
        msg('Aucun acte trouvé');
    }
}
echo '</div>';
echo '</div>';
include(__DIR__ . '/templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();

