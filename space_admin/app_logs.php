<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

if (!$userAuthorizer->isGranted(9)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

$MT0 = microtime_float();
$xcomm = "";
$xpatr = "";
$page = 1;
$xord  = getparam('xord', 'D');// N = Nom
$page  = getparam('pg');
$xdel  = getparam('xdel');
$xfilter = getparam('xfilter');

$menu_software_active = 'J';
$limit = '';
$pagination = '';

ob_start();
open_page($config->get('SITENAME') . " : Activité du site", $root); ?>
<div class="main">
    <?php zone_menu(10, $session->get('user')['level']); ?>
    <div class="main-col-center text-center">
        <?php 
navadmin($root, "Activité du site");

require(__DIR__ . '/../templates/admin/_menu-software.php');

// Suppression des informations anciennes
if ($xdel > 31) {
    $sql = "DELETE FROM " . $config->get('EA_DB') . "_log WHERE datediff(curdate(),DATE)>" . $xdel;
    $result = EA_sql_query($sql);
    $nb = EA_sql_affected_rows();
    echo $nb . " ligne(s) suprimée(s)."; // .$datedel;
}
echo '<p><a href="?xdel=365">' . "Supprimer les événements âgés de plus d'un an</a></p>";
// Lister les actions
echo '<h2>Activité sur les données du site ' . $config->get('SITENAME') . '</h2>';

echo '<center><form method="post">';
echo '<input type="text" name="xfilter" value="">';
echo '<button type="submit" class="btn">Filtrer</button></td>';
echo '</form></center>';

$baselink = $root . '/admin/application/logs?xfilter=' . $xfilter;
if ($xord == "N") {
    $order = "NOM, PRENOM, DATE DESC";
    $hdate = '<a href="' . $root . '/admin/application/logs?xfilter=' . $xfilter . '&xord=D">Date et heure</a>';
    $hcomm = '<a href="' . $root . '/admin/application/logs?xfilter=' . $xfilter . '&xord=C">Commune/Paroisse</a>';
    $baselink = $root . '/admin/application/logs?xfilter=' . $xfilter . '&xord=N';
    $hnoms = '<b>Utilisateur</b>';
} elseif ($xord == "D") {
    $order = "DATE DESC";
    $hcomm = '<a href="' . $root . '/admin/application/logs?xfilter=' . $xfilter . '&xord=C">Commune/Paroisse</a>';
    $hnoms = '<a href="' . $root . '/admin/application/logs?xfilter=' . $xfilter . '&xord=N">Utilisateur</a>';
    $baselink = $root . '/admin/application/logs?xfilter=' . $xfilter . '&xord=D';
    $hdate = '<b>Date et heure</b>';
} else {
    $order = "COMMUNE, DATE DESC";
    $hdate = '<a href="' . $root . '/admin/application/logs?xfilter=' . $xfilter . '&xord=D">Date et heure</a>';
    $hnoms = '<a href="' . $root . '/admin/application/logs?xfilter=' . $xfilter . '&xord=N">Utilisateur</a>';
    $baselink = $root . '/admin/application/logs?xfilter=' . $xfilter . '&xord=L';
    $hcomm = '<b>Commune/Paroisse</b>';
}

$sql = "CREATE TEMPORARY TABLE temp_user3 (ID int(11), nom varchar(30), prenom varchar(30), PRIMARY KEY (ID))";
$result = EA_sql_query($sql);

$sql = "SELECT ID,NOM,PRENOM FROM " . $config->get('EA_UDB') . "_user3";
$result = EA_sql_query($sql, $u_db);
while ($ligne = EA_sql_fetch_row($result)) {
    $treq = "INSERT INTO temp_user3 VALUES (" . sql_quote($ligne[0]) . ",'" . sql_quote($ligne[1]) . "','" . sql_quote($ligne[2]) . "')";
    $tres = EA_sql_query($treq);
    //echo "<br>".$treq;
}

$sql = "SELECT NOM, PRENOM, ID, DATE, ACTION, COMMUNE, NB_ACTES"
            . " FROM " . $config->get('EA_DB') . "_log left JOIN temp_user3 ON (temp_user3.id=" . $config->get('EA_DB') . "_log.user)";
if ($xfilter <> "") {
    $sql .= " WHERE COMMUNE LIKE '%" . $xfilter . "%' or ACTION LIKE '%" . $xfilter . "%' or NOM LIKE '%" . $xfilter . "%'";
}
$sql .= " ORDER BY " . $order;

optimize($sql);

$result = EA_sql_query($sql);
$nbtot = EA_sql_num_rows($result);

if ($limit <> "") {
    $sql = $sql . $limit;
    $result = EA_sql_query($sql);
    $nb = EA_sql_num_rows($result);
} else {
    $nb = $nbtot;
}

$pagination = pagination($nbtot, $page, $baselink, $pagination, $limit);

if ($nb > 0) {
    $i = 1 + ($page - 1) * $config->get('MAX_PAGE');
    echo '<p>' . $pagination . '</p>';
    echo '<table class="m-auto" summary="Liste des actions">';
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
    echo '<p>' . $pagination . '</p>';
} else {
    echo '<p>Aucune action enregistrée</p>';
} ?>

<p>Durée du traitement  : <?= round(microtime_float() - $MT0, 3); ?> sec.</p>

</div>
</div>

<?php include(__DIR__ . '/../templates/front/_footer.php');
return (ob_get_clean());
