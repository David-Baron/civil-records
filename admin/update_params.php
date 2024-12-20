<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

define('ADM', 10); // Compatibility only
$admtxt = 'Gestion '; // Compatibility only
require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only
// require(__DIR__ . '/../install/instutils.php');

if (!$userAuthorizer->isGranted(9)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

pathroot($root, $path, $xcomm, $xpatr, $page);

$missingargs = true;
//$message    = getparam('Message');
$xaction    = getparam('action');

$menu_software_active = 'P';

ob_start();
open_page("Mise à jour des paramètres", $root);
navadmin($root, "Mise à jour des paramètres");
zone_menu(ADM, $session->get('user')['level'], array());//ADMIN STANDARD
echo '<div id="col_main_adm">';
require(__DIR__ . '/../templates/admin/_menu-software.php');

echo '<h2>Backup / Restauration</h2>';
echo '<p align="center"><strong>Actions sur les paramètres : </strong>';
echo ' <a href="expparams.php"><b>Sauvegarder</b></a>';
echo ' | Restaurer';
echo ' || <a href="gest_params.php">Retour</a>';
echo '</p>';

if ($xaction == 'submitted') {
    if(!empty($_FILES['params']['tmp_name'])) { // fichier de paramètres
        if(strtolower(mb_substr($_FILES['params']['name'], -4)) == ".xml") { //Vérifie que l'extension est bien '.XML'
            // type XML
            $filename = $_FILES['params']['tmp_name'];
            $missingargs = false;
            // paramètres généraux
            update_params($filename, 1);
            // définitions des zones
            $table = EA_DB . "_metadb";
            $tabdata = xml_readDatabase($filename, $table);
            $tabkeys = array('ZID');
            update_metafile($tabdata, $tabkeys, $table, $par_add, $par_mod);
            // textes des étiquettes
            $table = EA_DB . "_metalg";
            $tabdata = xml_readDatabase($filename, $table);
            $tabkeys = array('ZID','lg');
            update_metafile($tabdata, $tabkeys, $table, $par_add, $par_mod);
            // etiquettes des groupes
            $table = EA_DB . "_mgrplg";
            $tabdata = xml_readDatabase($filename, $table);
            $tabkeys = array('grp','dtable','lg','sigle');
            update_metafile($tabdata, $tabkeys, $table, $par_add, $par_mod);

            writelog('Restauration des paramètres', "PARAMS", ($par_mod + $par_add));

            if ($par_add > 0) {
                echo "<p>" . $par_add . " paramètres ajoutés.</p>";
            }
            if ($par_mod > 0) {
                echo "<p>" . $par_mod . " paramètres modifiés.</p>";
            }
            if ($par_add + $par_mod == 0) {
                echo "<p>Aucune modification nécessaire.</p>";
            }
        } else {
            msg("Type de fichier incorrect !");
        }
    }
}

//Si pas tout les arguments nécessaire, on affiche le formulaire
if($missingargs) {
    echo '<form method="post" enctype="multipart/form-data" action="">' . "\n";
    echo '<h2 align="center">Restauration de paramètres sauvegardés</h2>';
    echo '<table cellspacing="2" cellpadding="0" border="0" align="center" summary="Formulaire">' . "\n";
    echo '<tr><td align="right">Dernier backup : &nbsp;</td><td>';
    echo show_last_backup("P");
    echo "</td></tr>";
    echo " <tr><td colspan=\"2\">&nbsp;</td></tr>\n";
    echo " <tr>\n";
    echo '  <td align="right">Fichier XML de paramètres : &nbsp;</td>' . "\n";
    echo '  <td><input type="file" size="62" name="params" />' . "</td>\n";
    echo " </tr>\n";
    echo " <tr><td colspan=\"2\">&nbsp;</td></tr>\n";
    echo " <tr><td colspan=\"2\" align=\"center\">\n<br />";
    echo '  <input type="hidden" name="action" value="submitted" />';
    echo '  <input type="reset" value="Annuler" />' . "\n";
    echo '  <input type="submit" value=" >> CHARGER >> " />' . "\n";
    echo " </td></tr>\n";
    echo "</table>\n";
    echo "</form>\n";
}
echo '</div>';
include(__DIR__ . '/../templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
