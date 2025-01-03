<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

$session->set('previous_url', $request->server->get('REQUEST_URI')); // Usefull for redirecting user

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

        liste_patro_1('actes/naissances', $path, $xcomm, $xpatr, "Naissances / Baptêmes", $config->get('EA_DB') . "_nai3", $gid, $note);
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
                navigation($root, 3, 'N', $xcomm, $xpatr);
                echo '<h2>Actes de naissance/baptême</h2>';
                echo '<p>';

                echo 'Commune/Paroisse : <a href="' . $root . '/actes/naissances?xcomm=' . $xcomm . '"><b>' . $xcomm . '</b></a>' . geoUrl($gid) . '<br />';
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
                    $hdate = '<a href="' . $root . '/actes/naissances?xcomm=' . $xcomm . '&xpatr=' . $xpatr . '&xord=D">Dates</a>';
                    $baselink = $root . '/actes/naissances?xcomm=' . $xcomm . '&xpatr=' . $xpatr . '&xord=N';
                    $hnoms = '<b>' . $nameorder . '</b>';
                } else {
                    $order = "LADATE, " . $preorder;
                    $hnoms = '<a href="' . $root . '/actes/naissances?xcomm=' . $xcomm . '&xpatr=' . $xpatr . '&xord=N">' . $nameorder . '</a>';
                    $baselink = $root . '/actes/naissances?xcomm=' . $xcomm . '&xpatr=' . $xpatr . '&xord=D';
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

                $limit = '';
                $pagination = '';
                $pagination = pagination($nbtot, $page, $baselink, $pagination, $limit);

                if ($limit <> "") {
                    $sql = $sql . $limit;
                    $result = EA_sql_query($sql);
                    $nb = EA_sql_num_rows($result);
                } else {
                    $nb = $nbtot;
                }

                if ($nb > 0) {
                    $i = 1 + ($page - 1) * $config->get('MAX_PAGE');
                    echo '<p>' . $pagination . '</p>';
                    echo '<table class="m-auto" summary="Liste des patronymes">';
                    echo '<tr class="rowheader">';
                    echo '<th> Tri : </th>';
                    echo '<th>' . $hdate . '</th>';
                    echo '<th>' . $hnoms . '</th>';
                    if ($userAuthorizer->isGranted(6)) {
                        echo '<th>Déposant</th><th></th>';
                    }
                    echo '</tr>';

                    while ($ligne = EA_sql_fetch_row($result)) {
                        echo '<tr class="row' . (fmod($i, 2)) . '">';
                        echo '<td>' . $i . '. </td>';
                        echo '<td>' . annee_seulement($ligne[2]) . '</td>';
                        echo '<td><a href="' . $path . '/actes/naissances/acte_details?xid=' . $ligne[3] . '&amp;xct=' . ctrlxid($ligne[0], $ligne[1]) . '$xcomm=' . $xcomm . '&xpatr=' . $xpatr . '">' . $ligne[0] . ' ' . $ligne[1] . '</a></td>';
                        if ($userAuthorizer->isGranted(6)) {
                            actions_deposant($session->get('user')['ID'], $ligne[4], $ligne[3], 'N');
                        }
                        echo '</tr>';
                        $i++;
                    }
                    echo '</table>';
                    echo '<p>' . $pagination . '</p>';
                } else {
                    echo '<p>Aucun acte trouvé</p>';
                }
            }
            echo '</div>';
            echo '</div>';
            include(__DIR__ . '/../templates/front/_footer.php');
            return (ob_get_clean());