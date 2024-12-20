<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

$xcomm = "";
$xpatr = "";
$page = 1;
$program = "tab_deces.php";
$comdep  = html_entity_decode($xcomm, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
$Commune = communede($comdep);
$Depart  = departementde($comdep);
$xord  = getparam('xord', 'D'); // N = Nom, D = dates
$pg = getparam('pg');
if ($pg <> "") {
    $page = $pg;
}
$xannee = "";
if (mb_substr($xpatr, 0, 1) == "!") {
    $xannee = mb_substr($xpatr, 1);
}
$gid = 0;
$note = geoNote($Commune, $Depart, 'D');

pathroot($root, $path, $xcomm, $xpatr, $page);

if ($xpatr == "" or mb_substr($xpatr, 0, 1) == "_") {
    // Lister les patronymes avec groupements si trop nombreux
    if (!$userAuthorizer->isGranted(2)) {
        $response = new RedirectResponse("$root/");
        $response->send();
        exit();
    }

    ob_start();
    open_page($xcomm . " : " . $admtxt . "Décès/Sépultures", $root); ?>
    <div class="main">
        <?php zone_menu(ADM, $session->get('user')['level']); ?>
        <div class="main-col-center text-center">
        <?php
        navigation($root, ADM + 2, 'D', $xcomm);
        liste_patro_1($program, $path, $xcomm, $xpatr, "Décès / Sépultures", $config->get('EA_DB') . "_dec3", $gid, $note);
    } else {
        if (!$userAuthorizer->isGranted(3)) {
            $response = new RedirectResponse("$root/");
            $response->send();
            exit();
        }

        ob_start();
        open_page($xcomm . " : " . $admtxt . "Table des décès/sépultures", $root); ?>
            <div class="main">
                <?php zone_menu(ADM, $session->get('user')['level']); ?>
                <div class="main-col-center text-center">
                <?php
                navigation($root, ADM + 3, 'D', $xcomm, $xpatr);
                // Lister les actes
                echo '<h2>Actes de décès/sépulture</h2>';

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

                $baselink = $path . '/' . $program . '/' . urlencode($xcomm) . '/' . urlencode($xpatr);
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

                //	$request = "SELECT act.NOM, act.PRE, DATETXT, act.ID, P_NOM, dep.NOM, dep.PRENOM, LOGIN, dep.ID, ORI, T1_NOM, COM, COTE"
                $request = "SELECT act.NOM, act.PRE, DATETXT, act.ID, act.DEPOSANT"
                    . " FROM " . $config->get('EA_DB') . "_dec3 AS act"
                    . " WHERE COMMUNE = '" . sql_quote($Commune) . "'" . $condDep
                    . $condit . " ORDER BY " . $order;

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
                    $i = 1 + ($page - 1) * iif((ADM > 0), $config->get('MAX_PAGE_ADM'), $config->get('MAX_PAGE'));
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
                        echo '<td>&nbsp;' . annee_seulement($ligne[2]) . '&nbsp;</td>';
                        echo '<td>&nbsp;<a href="' . $path . '/acte_deces.php?xid=' . $ligne[3] . '&amp;xct=' . ctrlxid($ligne[0], $ligne[1]) . '">' . $ligne[0] . ' ' . $ligne[1] . '</a></td>';
                        if (ADM == 10) {
                            actions_deposant($userid, $ligne[4], $ligne[3], 'D');
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
