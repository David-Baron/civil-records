<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only

if (!$userAuthorizer->isGranted(9)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

$page = 1;
$xord  = $request->get('xord', 'C'); // C = Commune, D = departement, S = Statut
$page  = $request->get('pg');
$init  = $request->get('init', null);

$limit = '';
$pagination = '';
$alphabet = '';
$url_params = '';
$geoloc_modes = ['M' => 'Manuelle', 'N' => 'Non définie', 'A' => 'Automatique'];
$menu_data_active = 'L';

if ($init !== null) {
    $url_params = '&init=' . $init;
}

// $sql = "SELECT DISTINCT upper(left(COMMUNE,1)) AS init FROM ".EA_DB."_geoloc ORDER BY init";
// Sélectionner et grouper sur initiale de commune et ascii(initiale), ordonner code ascii ascendant pour avoir + grand code (accentué) en dernier
$sql = "SELECT alphabet.init FROM (SELECT upper(left(COMMUNE, 1)) AS init,ascii(upper(left(COMMUNE, 1))) AS oo FROM " . $config->get('EA_DB') . "_geoloc GROUP BY init,oo  ORDER BY init , oo ASC) AS alphabet GROUP BY init";
$result = EA_sql_query($sql);

while ($letter = EA_sql_fetch_row($result)) {
    if ($letter[0] == $init) {
        $alphabet .= '<b>' . $letter[0] . '</b> ';
    } else {
        $alphabet .= '<a href="' . $root . '/admin/listgeolocs.php?xord=' . $xord . '&init=' . $letter[0] . '">' . $letter[0] . '</a> ';
    }
}

$hcommune = '<a href="' . $root . '/admin/localities.php?xord=C' . $url_params . '">Commune</a>';
$hdepart  = '<a href="' . $root . '/admin/localities.php?xord=D' . $url_params . '">Département</a>';
$hgeoloc  = '<a href="' . $root . '/admin/localities.php?xord=S' . $url_params . '">Géolocalisation</a>';
$baselink = $root . '/admin/localities.php?xord=' . $xord . $url_params;

if ($xord == "C") {
    $order = "COMMUNE, DEPART";
    $hcommune = '<b>Commune</b>';
} elseif ($xord == "D") {
    $order = "DEPART, COMMUNE";
    $hdepart = '<b>Département</b>';
} elseif ($xord == "S") {
    $order = "find_in_set(STATUT,'N, M, A')";
    $hgeoloc = '<b>Géolocalisation</b>';
} else {
    $order = "COMMUNE, DEPART";
    $hcommune = '<b>Commune</b>';
}
if ($init == "") {
    $condit = "";
} else {
    $condit = " WHERE COMMUNE LIKE '" . $init . "%' ";
}

$sql = "SELECT ID, COMMUNE, DEPART, LON, LAT, STATUT FROM " . $config->get('EA_DB') . "_geoloc " . $condit . " ORDER BY " . $order;
$result = EA_sql_query($sql);
$nbtot = EA_sql_num_rows($result);
if ($limit <> "") {
    $sql = $sql . $limit;
    $result = EA_sql_query($sql, $a_db);
    $nb = EA_sql_num_rows($result);
} else {
    $nb = $nbtot;
}

$pagination = pagination($nbtot, $page, $baselink, $pagination, $limit);

ob_start();
open_page($config->get('SITENAME') . " : Liste des localités (communes et paroisses)", $root); ?>
<div class="main">
    <?php zone_menu(10, $session->get('user')['level']); ?>
    <div class="main-col-center text-center">
        <?php
        navadmin($root, "Liste des localités");
 
        require(__DIR__ . '/../templates/admin/_menu-data.php');

        echo '<h2>Localités connues du site ' . $config->get('SITENAME') . '</h2>';
        echo '<p align="center">' . $alphabet . '</p>';

        if ($nb > 0) {
            $i = 1 + ($page - 1) * $config->get('MAX_PAGE_ADM');
            echo '<p>' . $pagination . '</p>';
            echo '<table class="m-auto" summary="Liste des localités">';
            echo '<tr class="rowheader">';
            echo '<th> Tri : </th>';
            echo '<th>' . $hcommune . '</th>';
            echo '<th>' . $hdepart . '</th>';
            echo '<th>Latitude</th>';
            echo '<th>Longitude</th>';
            echo '<th>' . $hgeoloc . '</th>';
            echo '</tr>';

            while ($geoloc = EA_sql_fetch_array($result)) {
                echo '<tr>';
                echo '<td>' . $i . '. </td>';
                echo '<td><a href="' . $root . '/admin/gestgeoloc.php?id=' . $geoloc['ID'] . '">' . ($geoloc['COMMUNE'] ?? '&lt;non précisé&gt;') . '</a></td>';
                echo '<td>' . $geoloc['DEPART'] . '</td>';
                echo '<td>' . $geoloc['LAT'] . '</td>';
                echo '<td>' . $geoloc['LON'] . '</td>';
                echo '<td>' . $geoloc_modes[$geoloc['STATUT']] . '</td>';
                echo '</tr>';
                $i++;
            }
            echo '</table>';
            echo '<p>' . $pagination . '</p>';
        } else {
            echo '<p>Aucune localité trouvée</p>';
        } ?>
    </div>
</div>
<?php include(__DIR__ . '/../templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
