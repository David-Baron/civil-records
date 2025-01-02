<?php

$Ref_Serveur = str_replace(array(':','.'), '-', $_SERVER['SERVER_NAME']);
if (strtoupper(substr($Ref_Serveur, 0, 4)) == 'WWW-') {
    $Ref_Serveur = substr($Ref_Serveur, 4);
}

if (file_exists(__DIR__ . '/../_config/' . 'BD-' . $Ref_Serveur . '-connect.inc.php')) {
    include_once(__DIR__ . '/../_config/' . 'BD-' . $Ref_Serveur . '-connect.inc.php'); // Compatibility only
} else {
    include_once(__DIR__ . '/../_config/connect.inc.php'); // Compatibility only
}
include_once __DIR__ . '/../tools/function.php'; // Compatibility only
include_once __DIR__ . '/../tools/adlcutils.php'; // Compatibility only
include_once __DIR__ . '/../tools/actutils.php'; // Compatibility only

/* if (!in_array(basename($_SERVER['PHP_SELF']), ['eclair.php', 'rss.php', 'p_info.php'])) {
    include_once __DIR__ . '/../tools/loginutils.php';
}
 */
// Cette partie gère l'existence d'un script modifié localement, son nom doit alors être LOCAL-[Nom complet d'origine]
// $EA_Ce_Script = str_replace('LOCAL-', '', basename($_SERVER['PHP_SELF']));
