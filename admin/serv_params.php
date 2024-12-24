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

function paspoint($string)
{
    $x = strpos($string, ":");
    if ($x > 0) {
        return mb_substr($string, $x + 1);
    } 
    
    return "";
}

pathroot($root, $path, $xcomm, $xpatr, $page);

$action = getparam('maint');
if ($action <> "") {
    if ($action == "SET") {
        $sql = "UPDATE " . $config->get('EA_DB') . "_params SET valeur = '1' WHERE param = 'EA_MAINTENANCE'";
        $result = EA_sql_query($sql);
    }
    if ($action == "UNSET") {
        $sql = "UPDATE " . $config->get('EA_DB') . "_params SET valeur = '0' WHERE param = 'EA_MAINTENANCE'";
        $result = EA_sql_query($sql);
    }
}

$menu_software_active = 'E';
ob_start();
open_page("Paramètres serveur", $root); ?>
<div class="main">
    <?php zone_menu(10, $session->get('user')['level']); ?>
    <div class="main-col-center text-center">
        <?php 
navadmin($root, "Paramètres serveur");

require(__DIR__ . '/../templates/admin/_menu-software.php');

$sql = "SELECT valeur FROM " . $config->get('EA_DB') . "_params WHERE param = 'EA_MAINTENANCE'";
$result = EA_sql_query($sql);
$row = EA_sql_fetch_array($result);
if ($row[0] == 1) {
    echo '<p><font color="#FF0000"><b>Mode MAINTENANCE : l\'accès limité aux administrateurs.</b></font></p>';
    echo '<p><a href="?maint=UNSET"><b>Basculer en mode NORMAL</b></a></p>';
} else {
    echo '<p><font color="#009900"><b>Mode NORMAL : Le site est ouvert à la consultation.</b></font></p>';
    echo '<p><a href="?maint=SET"><b>Basculer en mode MAINTENANCE</b></a></p>';
}

echo '<h2>Informations sur le serveur web (site)</h2>';

echo "<p>Version du serveur PHP : <b>" . phpversion() . "</b></p>";
echo "<p>Type du serveur : <b>" . php_uname() . "</b></p>";

echo '<h2>Informations sur le serveur MySQL (base de données)</h2>';

$db = con_db();  // avec affichage de l état de la connexion

echo "<p>Version du serveur MySQL : <b>" . EA_sql_get_server_info() . "</b></p>";

// paramètres du serveur MySQL
$status = explode('  ', EA_sql_stat($db));

echo '<h3>Etat du serveur</h3>';
echo "<p>Serveur MySQL en fonctionnement depuis : " . heureminsec(paspoint($status[0])) . "</p>";
if (isset($status[7])) {
    echo "<p>Nombre moyen de requêtes par sec (tous clients confondus) :" . paspoint($status[7]) . "</p>";
}

echo '<h3>Paramètres du serveur</h3>';
echo "<p>Temps limite pour l'exécution des requêtes (sec) : " . val_var_mysql('wait_timeout') . "</p>";
echo "<p>Temps limite pour les lectures (sec) : " . val_var_mysql('net_read_timeout') . "</p>";
echo "<p>Temps limite pour les écritures (sec) : " . val_var_mysql('net_write_timeout') . "</p>";
$maxcon = val_var_mysql('max_user_connections');
if ($maxcon == 0) {
    $maxcon = val_var_mysql('max_connections');
}
echo "<p>Nombre maximal de connexions simultannées globalement : " . val_var_mysql('max_connections') . "</p>";
echo "<p>Nombre maximal de connexions simultannées pour vous : " . $maxcon . "</p>";

if (file_exists('serv_params_accents.inc.php')) {
    include('serv_params_accents.inc.php');
}

echo '<h2>Informations sur le géocodage (via Google Maps)</h2>';
test_geocodage(true);
echo '</div>';
echo '</div>';
include(__DIR__ . '/../templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
