<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

$xcomm = "";
$xpatr = "";
$page = 1;
$program = "tab_naiss.php";
$comdep  = html_entity_decode($xcomm, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
$Commune = communede($comdep);
$Depart  = departementde($comdep);
$xord  = getparam('xord', 'D');// N = Nom, D = dates
$pg = getparam('pg');
if ($pg <> "") {
    $page = $pg;
}
$xannee = "";
if (mb_substr($xpatr, 0, 1) == "!") {
    $xannee = mb_substr($xpatr, 1);
}

$gid = 0;
$note = geoNote($Commune, $Depart, 'N');

pathroot($root, $path, $xcomm, $xpatr, $page);

if (($xpatr == "" or mb_substr($xpatr, 0, 1) == "_")) {
    // Lister les patronymes avec groupements si trop nombreux
    if (!$userAuthorizer->isGranted(2)) {
        $response = new RedirectResponse("$root/");
        $response->send();
        exit();
    }
    ob_start();
    open_page($xcomm . " : " . $admtxt . "Naissances/Baptêmes", $root);
    navigation($root, ADM + 2, 'N', $xcomm);
    zone_menu(ADM, $session->get('user')['level']);
    echo '<div id="col_main">' . "\n";
    liste_patro_1($program, $path, $xcomm, $xpatr, "Naissances / Baptêmes", EA_DB . "_nai3", $gid, $note);
} else {
    // Lister les actes
    if (!$userAuthorizer->isGranted(3)) {
        $response = new RedirectResponse("$root/");
        $response->send();
        exit();
    }

    ob_start();
    open_page($xcomm . " : " . $admtxt . "Table des naissances/baptêmes", $root);
    navigation($root, ADM + 3, 'N', $xcomm, $xpatr);
    zone_menu(ADM, $session->get('user')['level']);

    echo '<div id="col_main">' . "\n";
    echo '<h2>Actes de naissance/baptême</h2>';
    echo '<p>';

    echo 'Commune/Paroisse : <a href="' . mkurl($path . '/' . $program, $xcomm) . '"><b>' . $xcomm . '</b></a>' . geoUrl($gid) . '<br />';
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
        $hdate = '<a href="' . mkurl($path . '/' . $program, $xcomm, $xpatr, 'xord=D') . '">Dates</a>';
        $baselink = mkurl($path . '/' . $program, $xcomm, $xpatr, 'xord=N');
        $hnoms = '<b>' . $nameorder . '</b>';
    } else {
        $order = "LADATE, " . $preorder;
        $hnoms = '<a href="' . mkurl($path . '/' . $program, $xcomm, $xpatr, 'xord=N') . '">' . $nameorder . '</a>';
        $baselink = mkurl($path . '/' . $program, $xcomm, $xpatr, 'xord=D');
        $hdate = '<b>Dates</b>';
    }
    if ($xannee <> "") {
        $condit = " AND year(act.LADATE)=" . $xannee;
    } else {
        $condit = " AND act.NOM = '" . sql_quote($xpatr) . "'";
    }

    if ($Depart <> "") {
        $condDep = " AND DEPART = '" . sql_quote($Depart) . "'";
    } else {
        $condDep = "";
    }

    $request = "SELECT act.NOM, act.PRE, DATETXT, act.ID, act.DEPOSANT"
                . " FROM " . EA_DB . "_nai3 AS act"
                . " WHERE COMMUNE = '" . sql_quote($Commune) . "'" . $condDep
                . $condit . " ORDER BY " . $order;

    //echo $request;
    optimize($request);
    $result = EA_sql_query($request);
    $nbtot = EA_sql_num_rows($result);

    $limit = "";
    $listpages = "";
    pagination($nbtot, $page, $baselink, $listpages, $limit);

    if ($limit <> "") {
        $request = $request . $limit;
        $result = EA_sql_query($request);
        $nb = EA_sql_num_rows($result);
    } else {
        $nb = $nbtot;
    }

    if ($nb > 0) {
        if ($listpages <> "") {
            echo '<p>' . $listpages . '</p>';
        }
        $i = 1 + ($page - 1) * iif((ADM > 0), MAX_PAGE_ADM, MAX_PAGE);
        echo '<table summary="Liste des patronymes">';
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
include(__DIR__ . '/../templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
