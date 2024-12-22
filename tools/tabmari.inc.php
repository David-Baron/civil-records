<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

$xcomm = $request->get('xcomm');
$xpatr = $request->get('xpatr', '');
$xord  = $request->get('xord', 'D'); // N = Nom, D = dates, F = Femme
$page = $request->get('page', 1);

$comdep  = html_entity_decode($xcomm, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
$Commune = communede($comdep);
$Depart  = departementde($comdep);


$xannee = "";
if (mb_substr($xpatr, 0, 1) == "!") {
    $xannee = mb_substr($xpatr, 1);
}

$gid = 0;
$note = geoNote($Commune, $Depart, 'M');

// pathroot($root, $path, $xcomm, $xpatr, $page);

if ($xpatr == "" or mb_substr($xpatr, 0, 1) == "_") {
    // Lister les patronymes avec groupements si trop nombreux
    if (!$userAuthorizer->isGranted(2)) {
        $response = new RedirectResponse("$root/");
        $response->send();
        exit();
    }

    ob_start();
    open_page($xcomm . " : " . $admtxt . "Mariages", $root); ?>
    <div class="main">
        <?php zone_menu(ADM, $session->get('user')['level']); ?>
        <div class="main-col-center text-center">
            <?php 
    navigation($root, ADM + 2, 'M', $xcomm);

    liste_patro_2('tab_mari.php', $path, $xcomm, $xpatr, "Mariages", $config->get('EA_DB') . "_mar3", "", $gid, $note);
} else {
    if (!$userAuthorizer->isGranted(3)) {
        $response = new RedirectResponse("$root/");
        $response->send();
        exit();
    }

    ob_start();
    open_page($xcomm . " : " . $admtxt . "Table des mariages", $root); ?>
    <div class="main">
        <?php zone_menu(ADM, $session->get('user')['level']); ?>
        <div class="main-col-center text-center">
            <?php 
    navigation($root, ADM + 3, 'M', $xcomm, $xpatr);
    echo '<h2>Actes de mariage</h2>';

    echo '<p>';
    echo 'Commune/Paroisse : <a href="' . $path . '/tab_mari.php?xcomm=' . $xcomm . '"><b>' . $xcomm . '</b></a>' . geoUrl($gid) . '<br />';
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

    // $baselink = $path . '/tab_mari.php?xcomm=' . $xcomm . '&xpatr=' . $xpatr;
    if ($xord == "N") {
        $order = "act.NOM, PRE, LADATE";
        $hdate = '<a href="' . $path . '/tab_mari.php?xcomm=' . $xcomm . '&xpatr=' . $xpatr . '&xord=D">Dates</a>';
        $hnoms = '<b>Epoux</b>';
        $hfemm = '<a href="' . $path . '/tab_mari.php?xcomm=' . $xcomm . '&xpatr=' . $xpatr . '&xord=F">Epouses</a>';
        $baselink = $path . '/tab_mari.php?xcomm=' . $xcomm . '&xpatr=' . $xpatr . '&xord=N';
    } elseif ($xord == "F") {
        $order = "C_NOM, C_PRE, LADATE";
        $hnoms = '<a href="' . $path . '/tab_mari.php?xcomm=' . $xcomm . '&xpatr=' . $xpatr . '&xord=N">Epoux</a>';
        $hdate = '<a href="' . $path . '/tab_mari.php?xcomm=' . $xcomm . '&xpatr=' . $xpatr . '&xord=D">Dates</a>';
        $hfemm = '<b>Epouses</b>';
        $baselink = $path . '/tab_mari.php?xcomm=' . $xcomm . '&xpatr=' . $xpatr . '&xord=F';
    } else {
        $order = "LADATE, act.NOM, C_NOM";
        $hnoms = '<a href="' . $path . '/tab_mari.php?xcomm=' . $xcomm . '&xpatr=' . $xpatr . '&xord=N">Epoux</a>';
        $hdate = '<b>Dates</b>';
        $hfemm = '<a href="' . $path . '/tab_mari.php?xcomm=' . $xcomm . '&xpatr=' . $xpatr . '&xord=F">Epouses</a>';
        $baselink =$path . '/tab_mari.php?xcomm=' . $xcomm . '&xpatr=' . $xpatr . '&xord=D';
    }
    if ($xannee <> "") {
        $condit = " AND year(act.LADATE)=" . $xannee;
    } else {
        $condit = " AND (act.NOM  = '" . sql_quote($xpatr) . "' OR C_NOM  = '" . sql_quote($xpatr) . "')";
    }

    if ($Depart <> "") {
        $condDep = " AND DEPART = '" . sql_quote($Depart) . "'";
    } else {
        $condDep = "";
    }

    $sql = "SELECT act.NOM, act.PRE, C_NOM, C_PRE, DATETXT, act.ID, act.DEPOSANT"
                . " FROM " . $config->get('EA_DB') . "_mar3 AS act"
            . " WHERE COMMUNE = '" . sql_quote($Commune) . "'" . $condDep
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
        echo '<th>' . $hfemm . '</th>';
        echo '<th>&nbsp;</th>';
        if (ADM == 10) {
            echo '<th>Déposant</th>';
        }
        echo '</tr>';

        $xpatr = remove_accent($xpatr);
        while ($ligne = EA_sql_fetch_row($result)) {
            echo '<tr class="row' . (fmod($i, 2)) . '">';
            echo '<td>' . $i . '. </td>';
            echo '<td>&nbsp;' . annee_seulement($ligne[4]) . '&nbsp;</td>';
            if (remove_accent($ligne[0]) == $xpatr) {
                echo '<td>&nbsp;<b>' . $ligne[0] . ' ' . $ligne[1] . '</b></td>';
            } else {
                echo '<td>&nbsp;' . $ligne[0] . ' ' . $ligne[1] . '</td>';
            }
            if (remove_accent($ligne[2]) == $xpatr) {
                echo '<td>&nbsp;<b>' . $ligne[2] . ' ' . $ligne[3] . '</b></td>';
            } else {
                echo '<td>&nbsp;' . $ligne[2] . ' ' . $ligne[3] . '</td>';
            }

            echo '<td>&nbsp;<a href="' . $path . '/acte_mari.php?xid=' . $ligne[5] . '&amp;xct=' . ctrlxid($ligne[0], $ligne[1]) . '">' . "Détails" . '</a>&nbsp;</td>';
            if (ADM == 10) {
                actions_deposant($session->get('user')['ID'], $ligne[6], $ligne[5], 'M');
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
include(__DIR__ . '/../templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
