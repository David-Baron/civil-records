<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

$session->set('previous_url', $request->server->get('REQUEST_URI')); // Usefull for redirecting user

$xcomm = $request->get('xcomm');
$xpatr = $request->get('xpatr', '');
$xord  = $request->get('xord', 'D'); // N = Nom, D = dates, F = Femme
$page  = $request->get('page', 1);

$comdep  = html_entity_decode($xcomm, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
$Commune = communede($comdep);
$Depart  = departementde($comdep);

$xannee = "";
if (mb_substr($xpatr, 0, 1) == "!") {
    $xannee = mb_substr($xpatr, 1);
}

$gid = 0;
$note = geoNote($Commune, $Depart, 'M');


if ($xpatr == "" || mb_substr($xpatr, 0, 1) == "_") {
    // Lister les patronymes avec groupements si trop nombreux
    if ($config->get('PUBLIC_LEVEL') < 3 && !$userAuthorizer->isGranted(2)) {
        $session->getFlashBag()->add('warning', 'Vous n\'êtes pas connecté ou vous n\'avez pas les autorisations nécessaires!');
        $response = new RedirectResponse("$root/");
        $response->send();
        exit();
    }

    ob_start();
    open_page($xcomm . " : Mariages", $root); ?>
    <div class="main">
        <?php zone_menu(0, $session->get('user', ['level' => 0])['level']); ?>
        <div class="main-col-center text-center">
        <?php
        navigation($root, 2, 'M', $xcomm);

        liste_patro_2('actes/mariages', $root, $xcomm, $xpatr, "Mariages", $config->get('EA_DB') . "_mar3", "", $gid, $note);
    } else {
        if ($config->get('PUBLIC_LEVEL') < 3 && !$userAuthorizer->isGranted(3)) {
            $session->getFlashBag()->add('warning', 'Vous n\'êtes pas connecté ou vous n\'avez pas les autorisations nécessaires!');
            $response = new RedirectResponse("$root/");
            $response->send();
            exit();
        }

        ob_start();
        open_page($xcomm . " : Table des mariages", $root); ?>
            <div class="main">
                <?php zone_menu(0, $session->get('user', ['level' => 0])['level']); ?>
                <div class="main-col-center text-center">
                <?php
                navigation($root, 3, 'M', $xcomm, $xpatr);
                echo '<h2>Actes de mariage</h2>';

                echo '<p>';
                echo 'Commune/Paroisse : <a href="' . $root . '/actes/mariages?xcomm=' . $xcomm . '"><b>' . $xcomm . '</b></a>';
                if ($gid > 0 && $config->get('GEO_LOCALITE') > 0 && $userAuthorizer->isGranted(1)) {
                    echo ' <a href="' . $root . '/admin/geolocalizations/detail?id=' . $gid . '"><img src="' . $root . '/themes/img/boussole.png" alt="Localité détails" title="Localité détails"></a><br>';
                }
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

                // $baselink = $root . '/actes/mariages?xcomm=' . $xcomm . '&xpatr=' . $xpatr;
                if ($xord == "N") {
                    $order = "act.NOM, PRE, LADATE";
                    $hdate = '<a href="' . $root . '/actes/mariages?xcomm=' . $xcomm . '&xpatr=' . $xpatr . '&xord=D">Dates</a>';
                    $hnoms = '<b>Epoux</b>';
                    $hfemm = '<a href="' . $root . '/actes/mariages?xcomm=' . $xcomm . '&xpatr=' . $xpatr . '&xord=F">Epouses</a>';
                    $baselink = $root . '/actes/mariages?xcomm=' . $xcomm . '&xpatr=' . $xpatr . '&xord=N';
                } elseif ($xord == "F") {
                    $order = "C_NOM, C_PRE, LADATE";
                    $hnoms = '<a href="' . $root . '/actes/mariages?xcomm=' . $xcomm . '&xpatr=' . $xpatr . '&xord=N">Epoux</a>';
                    $hdate = '<a href="' . $root . '/actes/mariages?xcomm=' . $xcomm . '&xpatr=' . $xpatr . '&xord=D">Dates</a>';
                    $hfemm = '<b>Epouses</b>';
                    $baselink = $root . '/actes/mariages?xcomm=' . $xcomm . '&xpatr=' . $xpatr . '&xord=F';
                } else {
                    $order = "LADATE, act.NOM, C_NOM";
                    $hnoms = '<a href="' . $root . '/actes/mariages?xcomm=' . $xcomm . '&xpatr=' . $xpatr . '&xord=N">Epoux</a>';
                    $hdate = '<b>Dates</b>';
                    $hfemm = '<a href="' . $root . '/actes/mariages?xcomm=' . $xcomm . '&xpatr=' . $xpatr . '&xord=F">Epouses</a>';
                    $baselink = $root . '/actes/mariages?xcomm=' . $xcomm . '&xpatr=' . $xpatr . '&xord=D';
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
                    echo '<th>' . $hfemm . '</th>';
                    echo '<th></th>';
                    if ($userAuthorizer->isGranted(6)) {
                        echo '<th>Déposant</th><th></th>';
                    }
                    echo '</tr>';

                    $xpatr = remove_accent($xpatr);
                    while ($ligne = EA_sql_fetch_row($result)) {
                        echo '<tr class="row' . (fmod($i, 2)) . '">';
                        echo '<td>' . $i . '. </td>';
                        echo '<td>' . annee_seulement($ligne[4]) . '</td>';
                        if (remove_accent($ligne[0]) == $xpatr) {
                            echo '<td><b>' . $ligne[0] . ' ' . $ligne[1] . '</b></td>';
                        } else {
                            echo '<td>' . $ligne[0] . ' ' . $ligne[1] . '</td>';
                        }
                        if (remove_accent($ligne[2]) == $xpatr) {
                            echo '<td><b>' . $ligne[2] . ' ' . $ligne[3] . '</b></td>';
                        } else {
                            echo '<td>' . $ligne[2] . ' ' . $ligne[3] . '</td>';
                        }

                        echo '<td><a href="' . $root . '/actes/mariages/acte_details?xid=' . $ligne[5] . '&xct=' . ctrlxid($ligne[0], $ligne[1]) . '$xcomm=' . $xcomm . '&xpatr=' . $xpatr . '">Détails</a></td>';
                        if ($userAuthorizer->isGranted(6)) {
                            actions_deposant($session->get('user')['ID'], $ligne[6], $ligne[5], 'M');
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
