<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

$xcomm = "";
$xpatr = "";
$page = 1;
$program = "tab_bans.php";
$xord  = getparam('xord', 'D');  // N = Nom, D = dates, F = Femme
$pg = getparam('pg');
if ($pg <> "") {
    $page = $pg;
}
$xannee = "";
if (mb_substr($xpatr, 0, 1) == "!") {
    $xannee = mb_substr($xpatr, 1);
}
$p = isin($xcomm, ";");
$stype = "";
$stitre = "";
$soustype = "";
$sousurl  = "";
if ($p > 0) {
    $stype = mb_substr($xcomm, $p + 1);
    $xcomm = mb_substr($xcomm, 0, $p);
    $stitre = " (" . $stype . ")";
    $soustype = " AND LIBELLE = '" . sql_quote($stype) . "'";
    $sousurl  = ";" . $stype;
}
$comdep  = html_entity_decode($xcomm, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
$Commune = communede($comdep);
$Depart  = departementde($comdep);
$gid = 0;
$note = geoNote($Commune, $Depart, 'V');

pathroot($root, $path, $xcomm, $xpatr, $page);

if ($xpatr == "" or mb_substr($xpatr, 0, 1) == "_") {
    // Lister les patronymes avec groupements si trop nombreux
    if (!$userAuthorizer->isGranted(2)) {
        $response = new RedirectResponse("$root/");
        $response->send();
        exit();
    }

    ob_start();
    open_page($xcomm . " : " . $admtxt . "Divers" . $stitre, $root); ?>
    <div class="main">
        <?php zone_menu(ADM, $userlevel); ?>
        <div class="main-col-center text-center">
        <?php
        navigation($root, ADM + 2, 'V', $xcomm);

        liste_patro_2($program, $path, $xcomm, $xpatr, "Divers $stitre", $config->get('EA_DB') . "_div3", $stype, $gid, $note);
    } else {
        // **** Lister les actes
        if (!$userAuthorizer->isGranted(3)) {
            $response = new RedirectResponse("$root/");
            $response->send();
            exit();
        }

        ob_start();
        open_page($xcomm . " : " . $admtxt . "Divers" . $stitre, $root); ?>
            <div class="main">
                <?php zone_menu(ADM, $session->get('user')['level']); ?>
                <div class="main-col-center text-center">
                <?php
                navigation($root, ADM + 3, 'V', $xcomm, $xpatr);
                echo '<h2>Divers' . $stitre . '</h2>';
                echo '<p>';
                echo 'Commune/Paroisse : <a href="' . mkurl($path . '/' . $program, $xcomm . $sousurl) . '"><b>' . $xcomm . '</b></a>' . geoUrl($gid) . '<br />';
                if ($note <> '') {
                    echo "</p><p>" . $note . "</p><p>";
                }
                if (mb_substr($xpatr, 0, 1) == "!") {
                    echo 'Année : <b>' . $xannee . '</b>';
                } else {
                    echo 'Patronyme : <b>' . $xpatr . '</b>';
                }
                echo '</p>';

                $baselink = $path . '/' . $program . '/' . urlencode($xcomm) . '/' . urlencode($xpatr);
                if ($xord == "N") {
                    $order = "act.NOM, PRE, LADATE, LIBELLE";
                    $hdate = '<a href="' . mkurl($path . '/' . $program, $xcomm . $sousurl, $xpatr, 'xord=D') . '">Dates</a>';
                    $hnoms = '<b>Intervenant 1</b>';
                    $hfemm = '<a href="' . mkurl($path . '/' . $program, $xcomm . $sousurl, $xpatr, 'xord=F') . '">Intervenant 2</a>';
                    $htype = '<b>Document</b>';
                    $baselink = mkurl($path . '/' . $program, $xcomm . $sousurl, $xpatr, 'xord=N');
                } elseif ($xord == "F") {
                    $order = "C_NOM, C_PRE, LADATE, LIBELLE";
                    $hnoms = '<a href="' . mkurl($path . '/' . $program, $xcomm . $sousurl, $xpatr, 'xord=N') . '">Intervenant 1</a>';
                    $hdate = '<a href="' . mkurl($path . '/' . $program, $xcomm . $sousurl, $xpatr, 'xord=D') . '">Dates</a>';
                    $hfemm = '<b>Intervenant 2</b>';
                    $htype = '<b>Document</b>';
                    $baselink = mkurl($path . '/' . $program, $xcomm . $sousurl, $xpatr, 'xord=F');
                } else {
                    $order = "LADATE, act.NOM, C_NOM, LIBELLE";
                    $hnoms = '<a href="' . mkurl($path . '/' . $program, $xcomm . $sousurl, $xpatr, 'xord=N') . '">Intervenant 1</a>';
                    $hdate = '<b>Dates</b>';
                    $hfemm = '<a href="' . mkurl($path . '/' . $program, $xcomm . $sousurl, $xpatr, 'xord=F') . '">Intervenant 2</a>';
                    $htype = '<b>Document</b>';
                    $baselink = mkurl($path . '/' . $program, $xcomm . $sousurl, $xpatr, 'xord=D');
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

                $sql = "SELECT act.NOM, act.PRE, C_NOM, C_PRE, DATETXT, act.ID, act.LIBELLE, act.DEPOSANT"
                    . " FROM " . $config->get('EA_DB') . "_div3 AS act"
                    . " WHERE COMMUNE = '" . sql_quote($Commune) . "'" . $condDep
                    . " " . $soustype . $condit
                    . " ORDER BY " . $order;

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
                    echo '<table summary="Liste des patronymes">';
                    echo '<tr class="rowheader">';
                    echo '<th> Tri : </th>';
                    echo '<th>' . $hdate . '</th>';
                    echo '<th>' . $hnoms . '</th>';
                    echo '<th>' . $hfemm . '</th>';
                    echo '<th>' . $htype . '</th>';
                    echo '<th>&nbsp;</th>';
                    if (ADM == 10) {
                        echo '<th>Déposant</th>';
                    }
                    echo '</tr>' . "\n";

                    $xpatr = remove_accent($xpatr);
                    while ($ligne = EA_sql_fetch_row($result)) {
                        echo '<tr class="row' . (fmod($i, 2)) . '">' . "\n";
                        echo '<td>' . $i . '. </td>' . "\n";
                        echo '<td>&nbsp;' . annee_seulement($ligne[4]) . '&nbsp;</td>' . "\n";
                        if (remove_accent($ligne[0]) == $xpatr) {
                            echo '<td>&nbsp;<b>' . $ligne[0] . ' ' . $ligne[1] . '</b></td>' . "\n";
                        } else {
                            echo '<td>&nbsp;' . $ligne[0] . ' ' . $ligne[1] . '</td>' . "\n";
                        }
                        if (remove_accent($ligne[2]) == $xpatr) {
                            echo '<td>&nbsp;<b>' . $ligne[2] . ' ' . $ligne[3] . '</b></td>' . "\n";
                        } else {
                            echo '<td>&nbsp;' . $ligne[2] . ' ' . $ligne[3] . '</td>' . "\n";
                        }
                        echo '<td>&nbsp;' . $ligne[6] . '</td>';
                        echo '<td>&nbsp;<a href="' . $path . '/acte_bans.php?xid=' . $ligne[5] . '&amp;xct=' . ctrlxid($ligne[0], $ligne[1]) . '">' . "Détails" . '</a>&nbsp;</td>' . "\n";
                        if (ADM == 10) {
                            actions_deposant($session->get('user')['ID'], $ligne[7], $ligne[5], 'V');
                        }
                        echo '</tr>' . "\n";
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
