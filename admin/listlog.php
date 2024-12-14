<?php
define('ADM', 10); // Compatibility only
$admtxt = 'Gestion '; // Compatibility only
require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only

//define ("OPTIMIZE",1);
$MT0 = microtime_float();
$root = "";
$path = "";
$xcomm = "";
$xpatr = "";
$page = 1;

pathroot($root, $path, $xcomm, $xpatr, $page);

$xord  = getparam('xord');
if ($xord == "") {
    $xord = "D";
}   // N = Nom
$page  = getparam('pg');
$xdel  = getparam('xdel');
$xfilter = getparam('xfilter');

$userlogin = "";
$userlevel = logonok(9);
while ($userlevel < 9) {
    login($root);
}

ob_start();
open_page(SITENAME . " : Activité du site", $root);
navadmin($root, "Activité du site");
zone_menu(ADM, $userlevel, array());//ADMIN STANDARD
echo '<div id="col_main_adm">';
menu_software('J');

// Suppression des informations anciennes
if ($xdel > 31) {
    $request = "DELETE FROM " . EA_DB . "_log WHERE datediff(curdate(),DATE)>" . $xdel;
    $result = EA_sql_query($request);
    $nb = EA_sql_affected_rows();
    echo $nb . " ligne(s) suprimée(s)."; // .$datedel;
}
echo '<p><a href="?xdel=365">' . "Supprimer les événements âgés de plus d'un an</a></p>";
// Lister les actions
echo '<h2>Activité sur les données du site ' . SITENAME . '</h2>';

echo '<center><form method="post" action="">' . "\n";
echo '<input type="text" name="xfilter" value="" />' . "\n";
echo '&nbsp; &nbsp;<input type="submit" value="FILTRER" /></td>' . "\n";
echo '</form></center>';

$baselink = $root . '/admin/listlog.php' . "?xfilter=" . $xfilter;
if ($xord == "N") {
    $order = "NOM, PRENOM, DATE DESC";
    $hdate = '<a href="' . $baselink . '&amp;xord=D">Date et heure</a>';
    $hcomm = '<a href="' . $baselink . '&amp;xord=C">Commune/Paroisse</a>';
    $baselink = $baselink . '&amp;xord=N';
    $hnoms = '<b>Utilisateur</b>';
} elseif ($xord == "D") {
    $order = "DATE DESC";
    $hcomm = '<a href="' . $baselink . '&amp;xord=C">Commune/Paroisse</a>';
    $hnoms = '<a href="' . $baselink . '&amp;xord=N">Utilisateur</a>';
    $baselink = $baselink . '&amp;xord=D';
    $hdate = '<b>Date et heure</b>';
} else {
    $order = "COMMUNE, DATE DESC";
    $hdate = '<a href="' . $baselink . '&amp;xord=D">Date et heure</a>';
    $hnoms = '<a href="' . $baselink . '&amp;xord=N">Utilisateur</a>';
    $baselink = $baselink . '&amp;xord=L';
    $hcomm = '<b>Commune/Paroisse</b>';
}
$baselink .= "&amp;xfilter=" . $xfilter;

$request = "CREATE TEMPORARY TABLE temp_user3 (ID int(11), nom varchar(30), prenom varchar(30), PRIMARY KEY (ID))";
$result = EA_sql_query($request);

$request = "SELECT ID,NOM,PRENOM FROM " . EA_UDB . "_user3";
$result = EA_sql_query($request, $u_db);
while ($ligne = EA_sql_fetch_row($result)) {
    $treq = "INSERT INTO temp_user3 VALUES (" . sql_quote($ligne[0]) . ",'" . sql_quote($ligne[1]) . "','" . sql_quote($ligne[2]) . "')";
    $tres = EA_sql_query($treq);
    //echo "<br>".$treq;
}

$request = "SELECT NOM, PRENOM, ID, DATE, ACTION, COMMUNE, NB_ACTES"
            . " FROM " . EA_DB . "_log left JOIN temp_user3 ON (temp_user3.id=" . EA_DB . "_log.user)";
if ($xfilter <> "") {
    $request .= " WHERE COMMUNE LIKE '%" . $xfilter . "%' or ACTION LIKE '%" . $xfilter . "%' or NOM LIKE '%" . $xfilter . "%'";
}
$request .= " ORDER BY " . $order;

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
    echo '<p>' . $listpages . '</p>';
    $i = 1 + ($page - 1) * MAX_PAGE;
    echo '<table summary="Liste des actions">';
    echo '<tr class="rowheader">';
    // echo '<th> Tri : </th>';
    echo '<th>' . $hdate . '</th>';
    echo '<th>' . $hnoms . '</th>';
    echo '<th>' . $hcomm . '</th>';
    echo '<th>Action</th>';
    echo '<th>Actes</th>';
    echo '</tr>';

    while ($ligne = EA_sql_fetch_row($result)) {
        echo '<tr class="row' . (fmod($i, 2)) . '">';
        //		echo '<td>'.$i.'. </td>';
        echo '<td>' . $ligne[3] . ' </td>';
        echo '<td class="log_np">' . $ligne[0] . ' ' . $ligne[1] . '</td>';
        echo '<td class="log_com">' . $ligne[5] . '</td>';
        echo '<td class="log_action">' . $ligne[4] . ' </td>';
        echo '<td>' . $ligne[6] . ' </td>';
        echo '</tr>';
        $i++;
    }
    echo '</table>';
    echo '<p>' . $listpages . '</p>';
} else {
    msg('Aucune action enregistrée');
}

echo '</div>';
echo '<p>Durée du traitement  : ' . round(microtime_float() - $MT0, 3) . ' sec.</p>' . "\n";
include(__DIR__ . '/../templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
