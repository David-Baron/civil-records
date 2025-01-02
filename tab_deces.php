<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/src/bootstrap.php');

$session->set('previous_url', $request->server->get('REQUEST_URI')); // Usefull for redirecting user

$xcomm = $request->get('xcomm', '');
$xpatr = $request->get('xpatr', '');
$xord  = $request->get('xord', 'D'); // N = Nom, D = date
$xannee = $request->get('xannee', null);
$page = $request->get('page', 1);

$comdep  = html_entity_decode($xcomm, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
$Commune = communede($comdep);
$Depart  = departementde($comdep);

$gid = 0;
$note = geoNote($Commune, $Depart, 'D');

if ($xpatr == "" or mb_substr($xpatr, 0, 1) == "_") {
    // Lister les patronymes avec groupements si trop nombreux
    if (!$config->get('PUBLIC_LEVEL') >= 3 || !$userAuthorizer->isGranted(2)) {
        $session->getFlashBag()->add('warning', 'Vous n\'êtes pas connecté ou vous n\'avez pas les autorisations nécessaires!');
        $response = new RedirectResponse("$root/");
        $response->send();
        exit();
    }

    ob_start();
    open_page($xcomm . " : Décès/Sépultures", $root); ?>
    <div class="main">
        <?php zone_menu(0, $session->get('user', ['level' => 0])['level']); ?>
        <div class="main-col-center text-center">
        <?php
        navigation($root, 2, 'D', $xcomm);
        liste_patro_1('tab_deces.php', $path, $xcomm, $xpatr, "Décès / Sépultures", $config->get('EA_DB') . "_dec3", $gid, $note);
    } else {
        if (!$config->get('PUBLIC_LEVEL') >= 3 || !$userAuthorizer->isGranted(3)) {
            $session->getFlashBag()->add('warning', 'Vous n\'êtes pas connecté ou vous n\'avez pas les autorisations nécessaires!');
            $response = new RedirectResponse("$root/");
            $response->send();
            exit();
        }

        ob_start();
        open_page($xcomm . " : Table des décès/sépultures", $root); ?>
            <div class="main">
                <?php zone_menu(0, $session->get('user', ['level' => 0])['level']); ?>
                <div class="main-col-center text-center">
                <?php
                navigation($root, 3, 'D', $xcomm, $xpatr);
                // Lister les actes
                echo '<h2>Actes de décès/sépulture</h2>';

                echo '<p>';
                echo 'Commune/Paroisse : <a href="' . $root . '/tab_deces.php?xcomm=' . $xcomm . '"><b>' . $xcomm . '</b></a>' . geoUrl($gid) . '<br>';
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

                $baselink = $root . '/tab_deces.php?xcomm=' . $xcomm . '&xpatr=' . $xpatr;
                if ($xord == "N") {
                    $order = $preorder . ", LADATE";
                    $hdate = '<a href="' . $root . '/tab_deces.php?xcomm=' . $xcomm . '&xpatr=' . $xpatr . 'xord=D">Dates</a>';
                    $baselink = $root . '/tab_deces.php?xcomm=' . $xcomm . '&xpatr=' . $xpatr . 'xord=N';
                    $hnoms = '<b>' . $nameorder . '</b>';
                } else {
                    $order = "LADATE, " . $preorder;
                    $hnoms = '<a href="' . $root . '/tab_deces.php?xcomm=' . $xcomm . '&xpatr=' . $xpatr . 'xord=N' . '">' . $nameorder . '</a>';
                    $baselink = $root . '/tab_deces.php?xcomm=' . $xcomm . '&xpatr=' . $xpatr . 'xord=D';
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

                //	$sql = "SELECT act.NOM, act.PRE, DATETXT, act.ID, P_NOM, dep.NOM, dep.PRENOM, LOGIN, dep.ID, ORI, T1_NOM, COM, COTE"
                $sql = "SELECT act.NOM, act.PRE, DATETXT, act.ID, act.DEPOSANT"
                    . " FROM " . $config->get('EA_DB') . "_dec3 AS act"
                    . " WHERE COMMUNE = '" . sql_quote($Commune) . "'" . $condDep
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
                        echo '<td>&nbsp;' . annee_seulement($ligne[2]) . '&nbsp;</td>';
                        echo '<td>&nbsp;<a href="' . $root . '/acte_deces.php?xid=' . $ligne[3] . '&xct=' . ctrlxid($ligne[0], $ligne[1]) . '&xcomm=' . $xcomm . '&xpatr=' . $xpatr . '">' . $ligne[0] . ' ' . $ligne[1] . '</a></td>';
                        if ($userAuthorizer->isGranted(6)) {
                            actions_deposant($userid, $ligne[4], $ligne[3], 'D');
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
            include(__DIR__ . '/templates/front/_footer.php');
            $response->setContent(ob_get_clean());
            $response->send();
