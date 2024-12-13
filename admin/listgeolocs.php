<?php

if (file_exists('tools/_COMMUN_env.inc.php')) {
    $EA_Appel_dOu = '';
} else {
    $EA_Appel_dOu = '../';
}
include($EA_Appel_dOu . 'tools/_COMMUN_env.inc.php');

$root = "";
$path = "";
$xcomm = "";
$xpatr = "";
$page = 1;

pathroot($root, $path, $xcomm, $xpatr, $page);

$xord  = getparam('xord');
if ($xord == "") {
    $xord = "N";
}   // N = Nom
$page  = getparam('pg');
$init  = getparam('init');


$userlogin = "";
$userlevel = logonok(9);
while ($userlevel < 9) {
    login($root);
}

open_page(SITENAME . " : Liste des localités (communes et paroisses)", $root);

navadmin($root, "Liste des localités");

zone_menu(ADM, $userlevel, array());//ADMIN STANDARD

echo '<div id="col_main_adm">';

// Lister les actes

menu_datas('L');

echo '<h2>Localités connues du site ' . SITENAME . '</h2>';

$baselink = $root . '/admin/listgeolocs.php';
// $request = "SELECT DISTINCT upper(left(COMMUNE,1)) AS init FROM ".EA_DB."_geoloc ORDER BY init";
// Sélectionner et grouper sur initiale de commune et ascii(initiale), ordonner code ascii ascendant pour avoir + grand code (accentué) en dernier
$request = "SELECT  alphabet.init  FROM ( SELECT upper(left(COMMUNE,1)) AS init,ascii(upper(left(COMMUNE,1)))  AS oo FROM " . EA_DB . "_geoloc GROUP BY init,oo  ORDER BY init , oo ASC) AS alphabet GROUP BY init";

$result = EA_sql_query($request);
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


$request = "SELECT ID,COMMUNE,DEPART,LON,LAT,STATUT"
            . " FROM " . EA_DB . "_geoloc "
            . $condit
            . " ORDER BY " . $order;
//echo $request;
$result = EA_sql_query($request);
$nbtot = EA_sql_num_rows($result);

$limit = "";
$listpages = "";
pagination($nbtot, $page, $baselink, $listpages, $limit);

if ($limit <> "") {
    $request = $request . $limit;
    $result = EA_sql_query($request, $a_db);
    $nb = EA_sql_num_rows($result);
} else {
    $nb = $nbtot;
}

if ($nb > 0) {
    if ($listpages <> "") {
        echo '<p>' . $listpages . '</p>';
    }
    $i = 1 + ($page - 1) * MAX_PAGE_ADM;
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
        $ast = array("M" => "Manuelle", "N" => "Non définie","A" => "Auto");
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
}

echo '</div>';

close_page(1);
