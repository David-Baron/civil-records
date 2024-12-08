<?php

$root = "";
$path = "";
$admtxt = '';

function dd(mixed $data)
{
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    exit();
}

if (!file_exists(__DIR__ . '/../_config/connect.inc.php')) {
    echo '<p class="erreur">Lancer le script d\'installation pour avoir acc&egrave;s &agrave; l\'application<br />';
    echo 'Pour des raisons de s&eacute;curit&eacute; il n\'a pas &eacute;t&eacute; fait de lien direct.<br /></p>';
    exit;
}

$Ref_Serveur = str_replace(array(':', '.'), '-', $_SERVER['SERVER_NAME']);
if (strtoupper(substr($Ref_Serveur, 0, 4)) == 'WWW-') {
    $Ref_Serveur = substr($Ref_Serveur, 4);
}

if (file_exists(__DIR__ . '/../_config/' . 'BD-' . $Ref_Serveur . '-connect.inc.php')) {
    include_once(__DIR__ . '/../_config/' . 'BD-' . $Ref_Serveur . '-connect.inc.php');
} else {
    include_once(__DIR__ . '/../_config/connect.inc.php');
}

require(__DIR__ . '/EA_sql.inc.php');
require(__DIR__ . '/function.php');
require(__DIR__ . '/adlcutils.php');
require(__DIR__ . '/actutils.php');

if (!check_version(EA_VERSION, EA_VERSION_PRG)) { // si version mémorisée < version du programme
    header("Location: " . $root . "/install/update.php");
    die();
}

// p_info.php n'utilise pas adlcutils.php
if (! in_array(basename($_SERVER['PHP_SELF']), array('eclair.php', 'rss.php', 'p_info.php'))) {
    require(__DIR__ . '/loginutils.php');
}

// Cette partie gère l'existence d'un script modifié localement, son nom doit alors être LOCAL-[Nom complet d'origine]
$EA_Ce_Script = str_replace('LOCAL-', '', basename($_SERVER['PHP_SELF']));
