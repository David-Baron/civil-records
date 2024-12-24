<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

define('ADM', 10); // Compatibility only
$admtxt = 'Gestion '; // Compatibility only
require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only

if (!$userAuthorizer->isGranted(9)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

pathroot($root, $path, $xcomm, $xpatr, $page);

$xcomm = "";
$xpatr = "";
$page = 1;
$xord  = getparam('xord', 'N'); // N = Nom
$page  = getparam('pg');
$init  = getparam('init');

$menu_data_active = 'L';

ob_start();
open_page($config->get('SITENAME') . " : Liste des localités (communes et paroisses)", $root); ?>
<div class="main">
    <?php zone_menu(10, $session->get('user')['level']); ?>
    <div class="main-col-center text-center">
        <?php
        navadmin($root, "Liste des localités");

        require(__DIR__ . '/../templates/admin/_menu-data.php');

        echo '<h2>Localités connues du site ' . $config->get('SITENAME') . '</h2>';

        $baselink = $root . '/admin/listgeolocs.php';
        // $sql = "SELECT DISTINCT upper(left(COMMUNE,1)) AS init FROM ".EA_DB."_geoloc ORDER BY init";
        // Sélectionner et grouper sur initiale de commune et ascii(initiale), ordonner code ascii ascendant pour avoir + grand code (accentué) en dernier
        $sql = "SELECT  alphabet.init  FROM ( SELECT upper(left(COMMUNE,1)) AS init,ascii(upper(left(COMMUNE,1)))  AS oo FROM " . $config->get('EA_DB') . "_geoloc GROUP BY init,oo  ORDER BY init , oo ASC) AS alphabet GROUP BY init";

        $result = EA_sql_query($sql);
        $alphabet = "";
        while ($row = EA_sql_fetch_row($result)) {
            if ($row[0] == $init) {
                $alphabet .= '<b>' . $row[0] . '</b> ';
            } else {
                $alphabet .= '<a href="' . $baselink . '?xord=' . $xord . '&amp;init=' . $row[0] . '">' . $row[0] . '</a> ';
            }
        }
        echo '<p align="center">' . $alphabet . '</p>';

        if ($init == "") {
            $initiale = '';
        } else {
            $initiale = '&amp;init=' . $init;
        }

        $hcommune = '<a href="' . $baselink . '?xord=C' . $initiale . '">Commune</a>';
        $hdepart  = '<a href="' . $baselink . '?xord=D' . $initiale . '">Département</a>';
        $hgeoloc  = '<a href="' . $baselink . '?xord=S' . $initiale . '">Géolocalisation</a>';
        $baselink = $baselink . '?xord=' . $xord . $initiale;

        if ($xord == "C") {
            $order = "COMMUNE,DEPART";
            $hcommune = '<b>Commune</b>';
        } elseif ($xord == "D") {
            $order = "DEPART, COMMUNE";
            $hdepart = '<b>Département</b>';
        } elseif ($xord == "S") {
            $order = "find_in_set(STATUT,'N,M,A')";
            $hgeoloc = '<b>Géolocalisation</b>';
        } else {
            $order = "COMMUNE,DEPART";
            $hcommune = '<b>Commune</b>';
        }
        if ($init == "") {
            $condit = "";
        } else {
            $condit = " WHERE COMMUNE LIKE '" . $init . "%' ";
        }


        $sql = "SELECT ID,COMMUNE,DEPART,LON,LAT,STATUT"
            . " FROM " . $config->get('EA_DB') . "_geoloc "
            . $condit
            . " ORDER BY " . $order;
        $result = EA_sql_query($sql);
        $nbtot = EA_sql_num_rows($result);

        $limit = "";
        $listpages = "";
        pagination($nbtot, $page, $baselink, $listpages, $limit);

        if ($limit <> "") {
            $sql = $sql . $limit;
            $result = EA_sql_query($sql, $a_db);
            $nb = EA_sql_num_rows($result);
        } else {
            $nb = $nbtot;
        }

        if ($nb > 0) {
            if ($listpages <> "") {
                echo '<p>' . $listpages . '</p>';
            }
            $i = 1 + ($page - 1) * $config->get('MAX_PAGE_ADM');
            echo '<table summary="Liste des localités">';
            echo '<tr class="rowheader">';
            echo '<th> Tri : </th>';
            echo '<th>' . $hcommune . '</th>';
            echo '<th>' . $hdepart . '</th>';
            echo '<th>' . $hgeoloc . '</th>';
            echo '</tr>';


            while ($ligne = EA_sql_fetch_array($result)) {
                echo '<tr class="row' . (fmod($i, 2)) . '">';
                echo '<td>' . $i . '. </td>';
                $lenom = $ligne['COMMUNE'];
                if (trim($lenom) == "") {
                    $lenom = '&lt;non précisé&gt;';
                }
                echo '<td><a href="' . $root . '/admin/gestgeoloc.php?id=' . $ligne['ID'] . '">' . $lenom . '</a> </td>';
                echo '<td>' . $ligne['DEPART'] . ' </td>';
                $ast = array("M" => "Manuelle", "N" => "Non définie", "A" => "Auto");
                echo '<td align="center">' . $ast[$ligne['STATUT']] . '</td>';
                echo '</tr>';
                $i++;
            }
            echo '</table>';
            if ($listpages <> "") {
                echo '<p>' . $listpages . '</p>';
            }
        } else {
            msg('Aucune localité géocodée');
        } ?>
    </div>
</div>
<?php include(__DIR__ . '/../templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
