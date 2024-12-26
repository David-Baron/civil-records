<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only

if (!$userAuthorizer->isGranted(9)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

function init_page()
{
    global $root,$session,$htmlpage;

    open_page("Export des paramètres ", $root);
    ?>
    <div class="main">
        <?php zone_menu(10, $session->get('user')['level'], array()); ?>
        <div class="main-col-center text-center">
            <?php 
    navadmin($root, "Export des paramètres");
    $htmlpage = true;
}

// include(__DIR__ ."/../install/instutils.php");

$enclosed = '"';  // ou '"'
$separator = ';';
$htmlpage = false;
$missingargs = false;
$oktype = false;
$Destin = 'T'; // Toujours vers fichier (T) (sauf pour debug .. D )
/* 
pathroot($root, $path, $xcomm, $xpatr, $page);

my_ob_start_affichage_continu();

$filename = "ea_params_" . gmdate('Ymd') . '.xml';
$mime_type = 'text/xml';
if ($Destin == 'T') {
    // Download
    header('Content-Type: ' . $mime_type);
    header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    // lem9 & loic1: IE need specific headers
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') === true) {
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
    } else {
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
    }
} else {
    // HTML
    init_page();
    echo '<pre>' . "\n";
    //$row = EA_sql_fetch_array($result);
    //{ print '<pre>';  print_r($row); echo '</pre>'; }
} // end download

$doc  = "<?xml version='1.0' encoding='UTF-8'?>\n";
$doc .= "<!-- Export des parametres du " . gmdate('d M Y') . " -->\n";
$doc .= "<expoactes>\n";

$nb = 0;
// Export des paramètres de configuration principaux
$table = EA_DB . "_params";
$zones = array('param','groupe','ordre','type','valeur','listval','libelle','aide');
$doc .= xml_write_table($table, $zones, $nb);

// Export des étiquettes des zones
$table = EA_DB . "_metadb";
$zones = array('ZID', 'dtable', 'zone', 'groupe', 'bloc', 'typ', 'taille', 'OV2', 'OV3', 'oblig', 'affich');
$doc .= xml_write_table($table, $zones, $nb);

// Export des libellés étiquettes des zones
$table = EA_DB . "_metalg";
$zones = array('ZID','lg','etiq','aide');
$doc .= xml_write_table($table, $zones, $nb);

// Export des étiquettes des groupes
$table = EA_DB . "_mgrplg";
$zones = array('grp','dtable','lg','sigle','getiq');
$doc .= xml_write_table($table, $zones, $nb);

$doc .= "</expoactes>\n";
echo $doc;
$list_backups = get_last_backups();
$list_backups["P"] = today();
set_last_backups($list_backups);
writelog('Backup des paramètres', "PARAMS", $nb); */

if ($htmlpage) {
    echo '</div>';
    echo '</div>';
    include(__DIR__ . '/../templates/front/_footer.php');
}
