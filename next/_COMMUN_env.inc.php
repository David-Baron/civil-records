<?php

$Ref_Serveur = str_replace(array(':','.'), '-', $_SERVER['SERVER_NAME']);
if (strtoupper(substr($Ref_Serveur, 0, 4)) == 'WWW-') {
    $Ref_Serveur = substr($Ref_Serveur, 4);
}

if (!file_exists(__DIR__ . '/../_config/connect.inc.php')) {
    echo '<p class="erreur">Lancer le script d\'installation pour avoir acc&egrave;s &agrave; l\'application<br />';
    echo 'Pour des raisons de s&eacute;curit&eacute; il n\'a pas &eacute;t&eacute; fait de lien direct.<br /></p>';
    exit;
}

/** TODO: code for install/update from origin who need to be added to setup */
/* if (!check_version(EA_VERSION, '3.2.2')) { // si version mémorisée < 3.2.2
    echo '<p class="erreur">Vous utilisez ExpoActes.<br></p>';
    echo '<p class="erreur">La mont&eacute;e de version d\'ExpoActes n\'est possible que depuis la version 3.2.2.<br /></p>';
    echo 'Installer la version 3.2.2 pour pouvoir faire cette mise &agrave; jour.<br /></p>';
    exit;
}
if (!check_version(EA_VERSION, EA_VERSION_PRG)) { // si version mémorisée < version du programme
    header("Location: " . $root . "/install/update.php");
    die();
} */
/** END TODO */

if (file_exists(__DIR__ . '/../_config/' . 'BD-' . $Ref_Serveur . '-connect.inc.php')) {
    include_once(__DIR__ . '/../_config/' . 'BD-' . $Ref_Serveur . '-connect.inc.php'); // Compatibility only
} else {
    include_once(__DIR__ . '/../_config/connect.inc.php'); // Compatibility only
}
include_once __DIR__ . '/../tools/function.php'; // Compatibility only
include_once __DIR__ . '/../tools/adlcutils.php'; // Compatibility only
include_once __DIR__ . '/../tools/actutils.php'; // Compatibility only

if (!in_array(basename($_SERVER['PHP_SELF']), ['eclair.php', 'rss.php', 'p_info.php'])) {
    include_once __DIR__ . '/../tools/loginutils.php';
}

// Cette partie gère l'existence d'un script modifié localement, son nom doit alors être LOCAL-[Nom complet d'origine]
$EA_Ce_Script = str_replace('LOCAL-', '', basename($_SERVER['PHP_SELF']));
